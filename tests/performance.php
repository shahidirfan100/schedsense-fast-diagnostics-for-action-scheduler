<?php
/**
 * Seed and measure disposable WordPress/MariaDB CI installations.
 */

use SchedSense\Admin\AdminController;
use SchedSense\Scheduler\ActionSchedulerAdapter;
use SchedSense\Support\Cache;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'SCHEDSENSE_BENCHMARK' ) || 'local' !== wp_get_environment_type() ) {
	exit( 1 );
}

global $wpdb;
list( $command, $size ) = $args;
$size = (int) $size;

if ( 'seed' === $command ) {
	$action_id = as_schedule_single_action( time() - 2 * HOUR_IN_SECONDS, 'ci_perf_fixture', array(), 'schedsense-performance' );
	$table     = $wpdb->actionscheduler_actions;
	$prototype = $wpdb->get_row( $wpdb->prepare( 'SELECT schedule, group_id FROM %i WHERE action_id = %d', $table, $action_id ) );
	$remaining = $size - (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
	if ( ! $prototype || $remaining <= 0 ) {
		WP_CLI::error( 'Action Scheduler fixture cannot be seeded.' );
	}

	$inserted = $wpdb->query(
		$wpdb->prepare(
			"INSERT INTO %i (hook, status, scheduled_date_gmt, scheduled_date_local, args, schedule, group_id, last_attempt_gmt, last_attempt_local)
			SELECT CONCAT('ci_perf_', MOD(seq, 300)),
				CASE WHEN MOD(seq, 100) < 70 THEN 'complete'
					WHEN MOD(seq, 100) < 90 THEN 'pending'
					WHEN MOD(seq, 100) < 98 THEN 'failed' ELSE 'in-progress' END,
				UTC_TIMESTAMP() - INTERVAL (120 + MOD(seq, 10000)) MINUTE,
				UTC_TIMESTAMP() - INTERVAL (120 + MOD(seq, 10000)) MINUTE,
				'[]', %s, %d, UTC_TIMESTAMP() - INTERVAL 2 HOUR, UTC_TIMESTAMP() - INTERVAL 2 HOUR
			FROM %i",
			$table,
			$prototype->schedule,
			$prototype->group_id,
			'seq_1_to_' . $remaining
		)
	);
	if ( $inserted !== $remaining ) {
		WP_CLI::error( 'The fixture has an unexpected action count.' );
	}
	return;
}

list( , , $variant, $view, $state ) = $args;

require_once ABSPATH . 'wp-admin/includes/admin.php';
wp_set_current_user( 1 );

$http = 0;
add_filter(
	'pre_http_request',
	static function ( $pre ) use ( &$http ) {
		++$http;
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => '{"capabilities":{}}',
		);
	}
);

$actions = 'overview' !== $view;
$_GET    = array( '_wpnonce' => wp_create_nonce( 'schedsense_view' ) );
if ( $actions ) {
	$_GET += array( 'tab' => 'failures', 'status' => 'pending', 'group' => 'schedsense-performance' );
	if ( 'deep' === $view ) {
		$_GET['paged'] = (int) floor( $size * 0.18 / 25 );
	}
}
$_SERVER['REQUEST_URI'] = '/wp-admin/tools.php?page=schedsense_diagnostics&' . http_build_query( $_GET );

if ( 'cold' === $state ) {
	Cache::clear();
}

$controller  = new AdminController( new ActionSchedulerAdapter() );
$query_start = $wpdb->num_queries;
$start       = hrtime( true );
ob_start();
$controller->render_page();
$markup = ob_get_clean();
$ms     = ( hrtime( true ) - $start ) / 1e6;
$token  = $actions ? 'ci_perf_' : 'schedsense-queue-title';
if ( false === strpos( $markup, $token ) ) {
	WP_CLI::error( sprintf( 'The %s %s %s run did not render the %d-action queue fixture.', $variant, $state, $view, $size ) );
}

printf( "%s,%d,%s,%s,%d,%d,%.3f\n", $variant, $size, $view, $state, $wpdb->num_queries - $query_start, $http, $ms );
