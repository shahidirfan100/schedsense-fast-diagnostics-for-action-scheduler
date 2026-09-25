<?php
/**
 * Action Scheduler availability check.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class ActionSchedulerCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		if ( empty( $context['queue']['available'] ) ) {
			return new HealthResult(
				'action-scheduler',
				__( 'Action Scheduler', 'queue-health-monitor' ),
				'unavailable',
				'none',
				__( 'Action Scheduler is not currently available on this site.', 'queue-health-monitor' ),
				__( 'Queue Health Monitor did not find initialized Action Scheduler public APIs.', 'queue-health-monitor' ),
				__( 'Diagnostics become available when a compatible plugin loads Action Scheduler.', 'queue-health-monitor' )
			);
		}

		if ( empty( $context['queue']['data_available'] ) ) {
			return new HealthResult(
				'action-scheduler',
				__( 'Action Scheduler', 'queue-health-monitor' ),
				'unavailable',
				'none',
				__( 'Action Scheduler is initialized, but its queue could not be read.', 'queue-health-monitor' ),
				__( 'At least one supported queue query failed or returned an invalid result.', 'queue-health-monitor' ),
				__( 'Check the site database and Action Scheduler logs, then refresh diagnostics.', 'queue-health-monitor' )
			);
		}

		return new HealthResult(
			'action-scheduler',
			__( 'Action Scheduler', 'queue-health-monitor' ),
			'pass',
			'none',
			__( 'Action Scheduler is initialized and queryable.', 'queue-health-monitor' ),
			(string) $context['action_scheduler_version']
		);
	}
}
