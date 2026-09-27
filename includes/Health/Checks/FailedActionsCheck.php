<?php
/**
 * Failed action check.
 *
 * @package SchedSense
 */

namespace SchedSense\Health\Checks;

use SchedSense\Health\HealthCheckInterface;
use SchedSense\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class FailedActionsCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		if ( empty( $context['queue']['available'] ) || empty( $context['queue']['data_available'] ) ) {
			return new HealthResult( 'failed-actions', __( 'Failed actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'unavailable', 'none', __( 'Queue data is unavailable.', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		$count = absint( $context['queue']['counts']['failed'] );
		if ( 0 === $count ) {
			return new HealthResult( 'failed-actions', __( 'Failed actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'pass', 'none', __( 'No failed actions were reported.', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		/* translators: %d: Number of failed scheduled actions. */
		$summary = sprintf( _n( '%d failed action is recorded.', '%d failed actions are recorded.', $count, 'schedsense-fast-diagnostics-for-action-scheduler' ), $count );
		$status  = $count >= 20 ? 'critical' : 'warning';
		return new HealthResult( 'failed-actions', __( 'Failed actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), $status, $count >= 20 ? 'high' : 'medium', $summary, __( 'A failed action may reflect its callback rather than a scheduler-wide failure.', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Inspect whether failures are concentrated in one hook or source plugin.', 'schedsense-fast-diagnostics-for-action-scheduler' ), array( 'failed' => $count ) );
	}
}
