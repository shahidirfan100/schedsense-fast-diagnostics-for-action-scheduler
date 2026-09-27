<?php
/**
 * Compatibility boundary for Action Scheduler.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Scheduler;

use Throwable;

defined( 'ABSPATH' ) || exit;

final class ActionSchedulerAdapter {
	/** @var bool Whether a queue read failed during the current operation. */
	private $query_error = false;

	/**
	 * Determine whether initialized Action Scheduler APIs are available.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( 'ActionScheduler' ) ) {
			return false;
		}

		return did_action( 'action_scheduler_init' ) > 0 || ( method_exists( 'ActionScheduler', 'is_initialized' ) && \ActionScheduler::is_initialized() );
	}

	/**
	 * Get the loaded version without assuming a provider.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( class_exists( 'ActionScheduler_Versions' ) && method_exists( 'ActionScheduler_Versions', 'instance' ) ) {
			try {
				$versions = \ActionScheduler_Versions::instance();
				if ( method_exists( $versions, 'latest_version' ) ) {
					return (string) $versions->latest_version();
				}
			} catch ( Throwable $throwable ) {
				return __( 'Detected; version unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' );
			}
		}

		return $this->is_available() ? __( 'Detected; version unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ) : '';
	}

	/**
	 * Check whether an Action Scheduler queue query failed.
	 *
	 * @return bool
	 */
	public function has_query_error() {
		return $this->query_error;
	}

	/** Reset query state before starting a new read operation.
	 *
	 * @return void
	 */
	public function reset_query_error() {
		$this->query_error = false;
	}

	/**
	 * Count actions via Action Scheduler's store query interface.
	 *
	 * @param array $query Query filters.
	 * @return int
	 */
	public function count( array $query ) {
		if ( ! $this->is_available() ) {
			return 0;
		}

		try {
			$count = \ActionScheduler::store()->query_actions( $query, 'count' );
			if ( ! is_numeric( $count ) ) {
				$this->query_error = true;
				return 0;
			}
			return absint( $count );
		} catch ( Throwable $throwable ) {
			$this->query_error = true;
			return 0;
		}
	}

	/**
	 * Query a bounded action page and normalize it for presentation.
	 *
	 * @param array $query Query filters.
	 * @return array
	 */
	public function query( array $query ) {
		if ( ! $this->is_available() ) {
			return array();
		}

		$per_page          = isset( $query['per_page'] ) ? absint( $query['per_page'] ) : 25;
		$query['per_page'] = min( 100, max( 1, $per_page ) );
		$query['orderby']  = isset( $query['orderby'] ) && in_array( $query['orderby'], array( 'date', 'hook', 'group', 'modified', 'none' ), true ) ? $query['orderby'] : 'date';
		$query['order']    = isset( $query['order'] ) && 'DESC' === strtoupper( $query['order'] ) ? 'DESC' : 'ASC';

		try {
			$actions = as_get_scheduled_actions( $query, 'OBJECT' );
		} catch ( Throwable $throwable ) {
			$this->query_error = true;
			return array();
		}

		if ( ! is_array( $actions ) ) {
			$this->query_error = true;
			return array();
		}

		$query_status = isset( $query['status'] ) && is_string( $query['status'] ) ? sanitize_key( $query['status'] ) : '';
		if ( ! in_array( $query_status, array( 'pending', 'in-progress', 'failed', 'complete', 'canceled' ), true ) ) {
			$query_status = '';
		}

		$normalized = array();
		foreach ( $actions as $action_id => $action ) {
			$item = $this->normalize_action( absint( $action_id ), $action, $query_status );
			if ( $item ) {
				$normalized[] = $item;
			}
		}
		return $normalized;
	}

	/**
	 * Normalize an Action Scheduler object without exposing arguments.
	 *
	 * @param int    $action_id Action ID.
	 * @param object $action    Action object.
	 * @param string $query_status Status constraint, when known.
	 * @return array
	 */
	private function normalize_action( $action_id, $action, $query_status ) {
		if ( ! is_object( $action ) || ! method_exists( $action, 'get_hook' ) || ! method_exists( $action, 'get_schedule' ) ) {
			$this->query_error = true;
			return array();
		}

		try {
			$schedule     = $action->get_schedule();
			$date         = is_object( $schedule ) && method_exists( $schedule, 'get_date' ) ? $schedule->get_date() : null;
			$timestamp    = $date instanceof \DateTimeInterface ? $date->getTimestamp() : 0;
			$recurring    = is_object( $schedule ) && method_exists( $schedule, 'is_recurring' ) ? (bool) $schedule->is_recurring() : false;
			$status       = $query_status ? $query_status : \ActionScheduler::store()->get_status( $action_id );
			$age          = $timestamp ? max( 0, time() - $timestamp ) : 0;
			$age_bucket   = $timestamp && $timestamp > time() ? 'not_due' : ( new OverdueCategorizer() )->categorize( $age );
			$normalized   = array(
				'id'            => $action_id,
				'hook'          => (string) $action->get_hook(),
				'group'         => method_exists( $action, 'get_group' ) ? (string) $action->get_group() : '',
				'scheduled'     => $timestamp,
				'scheduled_iso' => $timestamp ? gmdate( 'c', $timestamp ) : '',
				'status'        => sanitize_key( $status ),
				'recurring'     => $recurring,
				'age'           => $age,
				'age_bucket'    => $age_bucket,
			);
			return $normalized;
		} catch ( Throwable $throwable ) {
			$this->query_error = true;
			return array();
		}
	}
}
