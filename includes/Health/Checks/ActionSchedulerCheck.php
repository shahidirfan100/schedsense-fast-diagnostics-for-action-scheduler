<?php
/**
 * Action Scheduler availability check.
 *
 * @package SchedSense
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
				__( 'Action Scheduler', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'unavailable',
				'none',
				__( 'Action Scheduler is not currently available on this site.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'SchedSense did not find initialized Action Scheduler public APIs.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Diagnostics become available when a compatible plugin loads Action Scheduler.', 'schedsense-fast-diagnostics-for-action-scheduler' )
			);
		}

		if ( empty( $context['queue']['data_available'] ) ) {
			return new HealthResult(
				'action-scheduler',
				__( 'Action Scheduler', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'unavailable',
				'none',
				__( 'Action Scheduler is initialized, but its queue could not be read.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'At least one supported queue query failed or returned an invalid result.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Check the site database and Action Scheduler logs, then refresh diagnostics.', 'schedsense-fast-diagnostics-for-action-scheduler' )
			);
		}

		return new HealthResult(
			'action-scheduler',
			__( 'Action Scheduler', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'pass',
			'none',
			__( 'Action Scheduler is initialized and queryable.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			(string) $context['action_scheduler_version']
		);
	}
}
