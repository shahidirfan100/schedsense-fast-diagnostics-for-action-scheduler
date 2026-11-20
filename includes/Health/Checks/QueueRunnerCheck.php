<?php
/**
 * Evidence from the default Action Scheduler WordPress cron hook.
 *
 * @package SchedSense
 */

namespace SchedSense\Health\Checks;

use SchedSense\Health\HealthCheckInterface;
use SchedSense\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class QueueRunnerCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		$hook       = 'action_scheduler_run_queue';
		$title      = __( 'Action Scheduler WP-Cron runner', 'schedsense-fast-diagnostics-for-action-scheduler' );
		$registered = false !== has_action( $hook );
		$event      = wp_get_scheduled_event( $hook, array( 'WP Cron' ) );
		if ( ! $event ) {
			$event = wp_get_scheduled_event( $hook );
		}

		$guidance = __( 'The WordPress cron schedule cannot verify a server cron, WP-CLI worker, custom runner, or completed callback.', 'schedsense-fast-diagnostics-for-action-scheduler' );
		if ( ! $event ) {
			return new HealthResult( 'queue-runner', $title, 'informational', 'low', __( 'No default Action Scheduler event was found in the WordPress cron schedule.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $guidance );
		}

		$timestamp = (int) $event->timestamp;
		$interval  = isset( $event->interval ) ? max( MINUTE_IN_SECONDS, (int) $event->interval ) : MINUTE_IN_SECONDS;
		/* translators: %s: Date and time of the next scheduled WordPress cron event. */
		$next = sprintf( __( 'Scheduled time: %s.', 'schedsense-fast-diagnostics-for-action-scheduler' ), wp_date( 'Y-m-d H:i:s T', $timestamp ) );

		if ( ! $registered ) {
			return new HealthResult( 'queue-runner', $title, 'warning', 'medium', __( 'A default Action Scheduler event exists, but this request found no registered callback for its hook.', 'schedsense-fast-diagnostics-for-action-scheduler' ) . ' ' . $next, $guidance );
		}
		if ( time() - $timestamp >= $interval ) {
			return new HealthResult( 'queue-runner', $title, 'warning', 'medium', __( 'The default Action Scheduler event is overdue by at least one scheduling interval.', 'schedsense-fast-diagnostics-for-action-scheduler' ) . ' ' . $next, $guidance );
		}

		return new HealthResult( 'queue-runner', $title, 'informational', 'none', __( 'The default Action Scheduler event is scheduled and its hook has a callback.', 'schedsense-fast-diagnostics-for-action-scheduler' ) . ' ' . $next, $guidance );
	}
}
