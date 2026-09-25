<?php
/**
 * Stale in-progress action check.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class StuckActionsCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		if ( empty( $context['queue']['available'] ) || empty( $context['queue']['data_available'] ) ) {
			return new HealthResult( 'stuck-actions', __( 'Stuck actions', 'queue-health-monitor' ), 'unavailable', 'none', __( 'Queue data is unavailable.', 'queue-health-monitor' ) );
		}

		$count = isset( $context['queue']['counts']['stuck_in_progress'] ) ? absint( $context['queue']['counts']['stuck_in_progress'] ) : 0;
		if ( 0 === $count ) {
			return new HealthResult( 'stuck-actions', __( 'Stuck actions', 'queue-health-monitor' ), 'pass', 'none', __( 'No in-progress action has gone an hour without an update.', 'queue-health-monitor' ) );
		}

		/* translators: %d: Number of in-progress actions without an update for over an hour. */
		$summary = sprintf( _n( '%d in-progress action has gone over an hour without an update.', '%d in-progress actions have gone over an hour without an update.', $count, 'queue-health-monitor' ), $count );
		$status  = $count >= 5 ? 'critical' : 'warning';
		return new HealthResult(
			'stuck-actions',
			__( 'Stuck actions', 'queue-health-monitor' ),
			$status,
			$count >= 5 ? 'high' : 'medium',
			$summary,
			__( 'Action Scheduler reports these actions as in progress, with no recorded update for more than an hour.', 'queue-health-monitor' ),
			__( 'Check whether a worker stopped, a callback is taking unusually long, or an action claim needs investigation.', 'queue-health-monitor' ),
			array( 'stuck_in_progress' => $count )
		);
	}
}
