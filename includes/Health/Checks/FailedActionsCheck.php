<?php
/**
 * Failed action check.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class FailedActionsCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		if ( empty( $context['queue']['available'] ) || empty( $context['queue']['data_available'] ) ) {
			return new HealthResult( 'failed-actions', __( 'Failed actions', 'queue-health-monitor' ), 'unavailable', 'none', __( 'Queue data is unavailable.', 'queue-health-monitor' ) );
		}

		$count = absint( $context['queue']['counts']['failed'] );
		if ( 0 === $count ) {
			return new HealthResult( 'failed-actions', __( 'Failed actions', 'queue-health-monitor' ), 'pass', 'none', __( 'No failed actions were reported.', 'queue-health-monitor' ) );
		}

		/* translators: %d: Number of failed scheduled actions. */
		$summary = sprintf( _n( '%d failed action is recorded.', '%d failed actions are recorded.', $count, 'queue-health-monitor' ), $count );
		$status  = $count >= 20 ? 'critical' : 'warning';
		return new HealthResult( 'failed-actions', __( 'Failed actions', 'queue-health-monitor' ), $status, $count >= 20 ? 'high' : 'medium', $summary, __( 'A failed action may reflect its callback rather than a scheduler-wide failure.', 'queue-health-monitor' ), __( 'Inspect whether failures are concentrated in one hook or source plugin.', 'queue-health-monitor' ), array( 'failed' => $count ) );
	}
}
