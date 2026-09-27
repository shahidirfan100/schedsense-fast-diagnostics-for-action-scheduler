<?php
/**
 * Stale in-progress action check.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class StuckActionsCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		if ( empty( $context['queue']['available'] ) || empty( $context['queue']['data_available'] ) ) {
			return new HealthResult( 'stuck-actions', __( 'Stuck actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'unavailable', 'none', __( 'Queue data is unavailable.', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		$count = isset( $context['queue']['counts']['stuck_in_progress'] ) ? absint( $context['queue']['counts']['stuck_in_progress'] ) : 0;
		if ( 0 === $count ) {
			return new HealthResult( 'stuck-actions', __( 'Stuck actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'pass', 'none', __( 'No in-progress action has gone an hour without an update.', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		/* translators: %d: Number of in-progress actions without an update for over an hour. */
		$summary = sprintf( _n( '%d in-progress action has gone over an hour without an update.', '%d in-progress actions have gone over an hour without an update.', $count, 'schedsense-fast-diagnostics-for-action-scheduler' ), $count );
		$status  = $count >= 5 ? 'critical' : 'warning';
		return new HealthResult(
			'stuck-actions',
			__( 'Stuck actions', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			$status,
			$count >= 5 ? 'high' : 'medium',
			$summary,
			__( 'Action Scheduler reports these actions as in progress, with no recorded update for more than an hour.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			__( 'Check whether a worker stopped, a callback is taking unusually long, or an action claim needs investigation.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			array( 'stuck_in_progress' => $count )
		);
	}
}
