<?php
/**
 * Queue Health Monitor admin controller.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Admin;

use QueueHealthMonitor\Diagnosis\DiagnosisEngine;
use QueueHealthMonitor\Health\HealthScanner;
use QueueHealthMonitor\Reports\ReportBuilder;
use QueueHealthMonitor\Scheduler\ActionSchedulerAdapter;
use QueueHealthMonitor\Scheduler\SourceResolver;
use QueueHealthMonitor\Support\Cache;
use QueueHealthMonitor\Support\Capabilities;

defined( 'ABSPATH' ) || exit;

final class AdminController {
	/** @var ActionSchedulerAdapter */
	private $adapter;

	/** @var string */
	private $page_hook = '';

	/**
	 * Constructor.
	 *
	 * @param ActionSchedulerAdapter $adapter Scheduler adapter.
	 */
	public function __construct( ActionSchedulerAdapter $adapter ) {
		$this->adapter = $adapter;
	}

	/** Register admin hooks. */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_qhm_refresh', array( $this, 'handle_refresh' ) );
		add_action( 'admin_post_qhm_clear_cache', array( $this, 'handle_clear_cache' ) );
		add_action( 'admin_post_qhm_spawn_cron', array( $this, 'handle_spawn_cron' ) );
		add_action( 'admin_post_qhm_delete_failed_action', array( $this, 'handle_delete_failed_action' ) );
	}

	/** Add Tools submenu. */
	public function add_menu() {
		$this->page_hook = add_management_page(
			__( 'Queue Health Monitor', 'queue-health-monitor' ),
			__( 'Queue Health Monitor', 'queue-health-monitor' ),
			Capabilities::required(),
			'qhm',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue local assets only on Queue Health Monitor.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( $this->page_hook !== $hook ) {
			return;
		}
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'qhm-admin', QUEUE_HEALTH_MONITOR_PLUGIN_URL . 'admin/css/queue-health-monitor-admin.css', array(), QUEUE_HEALTH_MONITOR_VERSION );
		wp_enqueue_script( 'qhm-admin', QUEUE_HEALTH_MONITOR_PLUGIN_URL . 'admin/js/queue-health-monitor-admin.js', array(), QUEUE_HEALTH_MONITOR_VERSION, true );
		wp_localize_script(
			'qhm-admin',
			'qhmAdmin',
			array(
				'copied'     => __( 'Diagnostic report copied.', 'queue-health-monitor' ),
				'copyFailed' => __( 'Copy failed. Select the report and copy it manually.', 'queue-health-monitor' ),
			)
		);
	}

	/** Render the requested tab. */
	public function render_page() {
		if ( ! Capabilities::current_user_can_access() ) {
			wp_die( esc_html__( 'You do not have permission to access Queue Health Monitor.', 'queue-health-monitor' ) );
		}

		$tab       = $this->request_tab();
		$snapshot  = $this->get_snapshot();
		$diagnosis = ( new DiagnosisEngine() )->diagnose( $snapshot );
		$notice    = $this->request_notice();

		if ( 'failures' === $tab ) {
			$failure_data = $this->get_failure_data();
		}
		if ( 'report' === $tab ) {
			$report = ( new ReportBuilder() )->build( $snapshot, $diagnosis );
		}

		require QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'admin/views/page.php';
	}

	/** Handle explicit diagnostic refresh. */
	public function handle_refresh() {
		$this->authorize_action( 'qhm_refresh_diagnostics' );
		Cache::clear();
		$this->redirect_with_notice( 'refreshed' );
	}

	/** Handle explicit cache clear. */
	public function handle_clear_cache() {
		$this->authorize_action( 'qhm_clear_cache' );
		Cache::clear();
		$this->redirect_with_notice( 'cache-cleared' );
	}

	/** Trigger one normal WP-Cron spawn attempt. */
	public function handle_spawn_cron() {
		$this->authorize_action( 'qhm_spawn_cron' );
		require_once ABSPATH . WPINC . '/cron.php';
		$spawned = spawn_cron( time() );
		Cache::clear();
		$this->redirect_with_notice( $spawned ? 'cron-spawned' : 'cron-not-spawned' );
	}

	/** Delete one action only when Action Scheduler still reports it as failed. */
	public function handle_delete_failed_action() {
		$this->authorize_action( 'qhm_delete_failed_action' );

		// The nonce is verified above; absint() constrains the identifier to a positive integer.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- authorize_action() verifies the nonce; input is unslashed and passed through absint().
		$raw_action_id = isset( $_POST['action_id'] ) ? wp_unslash( $_POST['action_id'] ) : 0;
		$action_id     = is_scalar( $raw_action_id ) ? absint( $raw_action_id ) : 0;
		$notice        = 'action-delete-error';

		if ( ! $action_id ) {
			$notice = 'action-no-longer-failed';
		} elseif ( ! class_exists( 'ActionScheduler' ) || ! method_exists( 'ActionScheduler', 'store' ) ) {
			$notice = 'action-delete-unavailable';
		} else {
			try {
				$store = \ActionScheduler::store();
				if ( ! is_object( $store ) || ! method_exists( $store, 'get_status' ) || ! method_exists( $store, 'delete_action' ) ) {
					$notice = 'action-delete-unavailable';
				} elseif ( 'failed' !== $store->get_status( $action_id ) ) {
					$notice = 'action-no-longer-failed';
				} else {
					$store->delete_action( $action_id );
					$notice = 'action-deleted';
				}
			} catch ( \Throwable $throwable ) {
				$notice = 'action-delete-error';
			}
		}

		Cache::clear();
		$this->redirect_with_notice(
			$notice,
			array(
				'tab'    => 'failures',
				'status' => 'failed',
				'hook'   => $this->request_post_text( 'hook' ),
				'group'  => $this->request_post_text( 'group' ),
			)
		);
	}

	/** @return array */
	private function get_snapshot() {
		$snapshot = Cache::get_snapshot();
		if ( false !== $snapshot ) {
			return $snapshot;
		}
		$snapshot = ( new HealthScanner( $this->adapter ) )->scan();
		Cache::set_snapshot( $snapshot );
		return $snapshot;
	}

	/** @return array */
	private function get_failure_data() {
		$this->adapter->reset_query_error();
		$per_page = 25;
		$status   = $this->request_enum( 'status', array( 'failed', 'pending', 'in-progress', 'complete' ), 'failed' );
		$age      = $this->request_enum( 'age', array( 'under_1h', 'one_to_6h', 'six_to_24h', 'one_to_7d', 'overdue_7d', 'overdue_1h', 'overdue_24h', 'stuck' ), '' );
		$order    = strtoupper( $this->request_enum( 'order', array( 'asc', 'desc' ), 'desc' ) );
		$orderby  = $this->request_enum( 'orderby', array( 'date', 'hook', 'group', 'modified' ), 'date' );
		$hook     = $this->request_text( 'hook' );
		$group    = $this->request_text( 'group' );
		if ( ( 'stuck' === $age && 'in-progress' !== $status ) || ( 'stuck' !== $age && '' !== $age && 'pending' !== $status ) ) {
			$age = '';
		}
		$filters  = array( 'status' => $status );
		if ( '' !== $hook ) {
			$filters['hook'] = $hook;
		}
		if ( '' !== $group ) {
			$filters['group'] = $group;
		}

		if ( ! $this->adapter->is_available() ) {
			return $this->failure_data_unavailable( $status, $hook, $group, $age, 'scheduler-unavailable' );
		}

		$now   = time();
		$total = $this->count_actions_for_age( $filters, $age, $now );
		if ( $this->adapter->has_query_error() ) {
			return $this->failure_data_unavailable( $status, $hook, $group, $age, 'query-failed' );
		}

		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		$page        = min( $this->request_positive_int( 'paged', 1 ), $total_pages );
		$age_query   = $this->get_age_query( $age, $now );
		$offset      = ( $page - 1 ) * $per_page;
		$query_limit = $per_page;
		if ( ! empty( $age_query['force_date_order'] ) ) {
			$orderby = 'date';
			$order   = 'ASC';
			$query_limit = min( $per_page, max( 1, $total - $offset ) );
			unset( $age_query['force_date_order'] );
		}
		$query       = array_merge(
			$filters,
			$age_query,
			array(
				'per_page' => $query_limit,
				'offset'   => $offset,
				'orderby'  => $orderby,
				'order'    => $order,
			)
		);
		$actions = $total > $offset ? $this->adapter->query( $query ) : array();
		if ( $this->adapter->has_query_error() ) {
			return $this->failure_data_unavailable( $status, $hook, $group, $age, 'query-failed' );
		}
		$resolver = new SourceResolver();
		foreach ( $actions as &$action ) {
			$action['source'] = $resolver->resolve( $action['hook'] );
		}
		unset( $action );

		return array(
			'available' => true,
			'reason'    => '',
			'actions'   => $actions,
			'total'     => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'status'   => $status,
			'orderby'  => $orderby,
			'order'    => $order,
			'hook'     => $hook,
			'group'    => $group,
			'age'      => $age,
		);
	}

	/**
	 * Count actions in a supported age range without loading an unbounded result set.
	 *
	 * @param array  $filters Base action filters.
	 * @param string $age     Requested age filter.
	 * @param int    $now     Shared timestamp for stable range boundaries.
	 * @return int
	 */
	private function count_actions_for_age( array $filters, $age, $now ) {
		if ( 'under_1h' === $age ) {
			return max( 0, $this->count_pending_before( $filters, $now ) - $this->count_pending_before( $filters, $now - HOUR_IN_SECONDS ) );
		}
		if ( 'one_to_6h' === $age ) {
			return max( 0, $this->count_pending_before( $filters, $now - HOUR_IN_SECONDS ) - $this->count_pending_before( $filters, $now - ( 6 * HOUR_IN_SECONDS ) ) );
		}
		if ( 'six_to_24h' === $age ) {
			return max( 0, $this->count_pending_before( $filters, $now - ( 6 * HOUR_IN_SECONDS ) ) - $this->count_pending_before( $filters, $now - DAY_IN_SECONDS ) );
		}
		if ( 'one_to_7d' === $age ) {
			return max( 0, $this->count_pending_before( $filters, $now - DAY_IN_SECONDS ) - $this->count_pending_before( $filters, $now - ( 7 * DAY_IN_SECONDS ) ) );
		}

		return $this->adapter->count( array_merge( $filters, $this->get_age_query( $age, $now ) ) );
	}

	/**
	 * Count pending actions scheduled before a UTC timestamp.
	 *
	 * @param array $filters    Base action filters.
	 * @param int   $timestamp  Unix timestamp.
	 * @return int
	 */
	private function count_pending_before( array $filters, $timestamp ) {
		return $this->adapter->count(
			array_merge(
				$filters,
				array(
					'date'         => $this->utc_date( $timestamp ),
					'date_compare' => '<=',
				)
			)
		);
	}

	/**
	 * Build an allowlisted Action Scheduler date or last-attempt filter.
	 *
	 * @param string $age Requested age filter.
	 * @param int    $now Shared timestamp for stable range boundaries.
	 * @return array
	 */
	private function get_age_query( $age, $now ) {
		$age_filters = array(
			'under_1h'    => array( 'date' => $this->utc_date( $now - HOUR_IN_SECONDS ), 'date_compare' => '>', 'force_date_order' => true ),
			'one_to_6h'   => array( 'date' => $this->utc_date( $now - ( 6 * HOUR_IN_SECONDS ) ), 'date_compare' => '>', 'force_date_order' => true ),
			'six_to_24h'  => array( 'date' => $this->utc_date( $now - DAY_IN_SECONDS ), 'date_compare' => '>', 'force_date_order' => true ),
			'one_to_7d'   => array( 'date' => $this->utc_date( $now - ( 7 * DAY_IN_SECONDS ) ), 'date_compare' => '>', 'force_date_order' => true ),
			'overdue_1h'  => array( 'date' => $this->utc_date( $now - HOUR_IN_SECONDS ), 'date_compare' => '<=' ),
			'overdue_24h' => array( 'date' => $this->utc_date( $now - DAY_IN_SECONDS ), 'date_compare' => '<=' ),
			'overdue_7d'  => array( 'date' => $this->utc_date( $now - ( 7 * DAY_IN_SECONDS ) ), 'date_compare' => '<=' ),
			'stuck'       => array( 'modified' => $this->utc_date( $now - HOUR_IN_SECONDS ), 'modified_compare' => '<=' ),
		);

		return isset( $age_filters[ $age ] ) ? $age_filters[ $age ] : array();
	}

	/**
	 * Create an Action Scheduler UTC date value.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return \DateTime
	 */
	private function utc_date( $timestamp ) {
		$date = new \DateTime( '@' . absint( $timestamp ) );
		$date->setTimezone( new \DateTimeZone( 'UTC' ) );
		return $date;
	}

	/** @return array */
	private function failure_data_unavailable( $status, $hook, $group, $age, $reason ) {
		return array(
			'available' => false,
			'reason'    => $reason,
			'actions'   => array(),
			'total'     => 0,
			'page'      => 1,
			'per_page'  => 25,
			'status'    => $status,
			'orderby'   => 'date',
			'order'     => 'DESC',
			'hook'      => $hook,
			'group'     => $group,
			'age'       => $age,
		);
	}

	/** @return string */
	private function request_tab() {
		return $this->request_enum( 'tab', array( 'overview', 'failures', 'sources', 'report' ), 'overview' );
	}

	/** @return string */
	private function request_notice() {
		return $this->request_enum( 'qhm_notice', array( 'refreshed', 'cache-cleared', 'cron-spawned', 'cron-not-spawned', 'action-deleted', 'action-no-longer-failed', 'action-delete-unavailable', 'action-delete-error' ), '' );
	}

	/** @return string */
	private function request_text( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only table filter; no state changes occur.
		if ( ! isset( $_GET[ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only value is unslashed, scalar-checked, and sanitized before use.
		$value = wp_unslash( $_GET[ $key ] );
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/** Read and sanitize a scalar POST value after nonce verification. */
	private function request_post_text( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Called after nonce verification; input is unslashed and sanitized below.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/** @return int */
	private function request_positive_int( $key, $fallback ) {
		$value = absint( $fallback );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination filter; no state changes occur.
		if ( isset( $_GET[ $key ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only value is unslashed and scalar-checked before absint().
			$raw = wp_unslash( $_GET[ $key ] );
			if ( is_scalar( $raw ) ) {
				$value = absint( $raw );
			}
		}
		return max( 1, $value );
	}

	/** @return string */
	private function request_enum( $key, array $allowed, $fallback ) {
		$value = $fallback;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter; no state changes occur.
		if ( isset( $_GET[ $key ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only value is unslashed, scalar-checked, and sanitized with sanitize_key() before use.
			$raw = wp_unslash( $_GET[ $key ] );
			if ( is_scalar( $raw ) ) {
				$value = sanitize_key( (string) $raw );
			}
		}
		if ( in_array( $value, $allowed, true ) ) {
			return $value;
		}
		return $fallback;
	}

	/** Authorize a state-changing admin-post request. */
	private function authorize_action( $nonce_action ) {
		if ( ! Capabilities::current_user_can_access() ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'queue-health-monitor' ) );
		}
		check_admin_referer( $nonce_action );
	}

	/** Redirect to Queue Health Monitor after an action. */
	private function redirect_with_notice( $notice, array $context = array() ) {
		$url = add_query_arg(
			array_merge(
				$context,
				array(
					'page'       => 'qhm',
					'qhm_notice' => sanitize_key( $notice ),
				)
			),
			admin_url( 'tools.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
