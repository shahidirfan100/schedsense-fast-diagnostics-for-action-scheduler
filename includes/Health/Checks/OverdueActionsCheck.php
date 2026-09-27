<?php
/**
 * Conservative overdue queue check.
 *
 * @package SchedSense
 */

namespace SchedSense\Health\Checks;

use SchedSense\Health\HealthCheckInterface;
use SchedSense\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class OverdueActionsCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		if ( empty( $context['queue']['available'] ) || empty( $context['queue']['data_available'] ) ) {
			return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'unavailable', 'none', __( 'Queue data is unavailable.', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		$counts = $context['queue']['counts'];
		if ( $counts['overdue_24h'] >= 20 || $counts['overdue_7d'] > 0 ) {
			/* translators: 1: Number overdue by 24 hours, 2: Number overdue by seven days. */
			$summary = sprintf( __( '%1$d actions are over 24 hours late; %2$d are over seven days late.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $counts['overdue_24h'], $counts['overdue_7d'] );
			return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'critical', 'high', $summary, __( 'A materially stale queue is unlikely to be explained by normal traffic-triggered cron delay alone.', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Review WP-Cron and loopback results, then inspect the dominant queue sources.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $counts );
		}

		if ( $counts['overdue_1h'] > 0 ) {
			/* translators: %d: Number of actions overdue by at least one hour. */
			$summary = sprintf( _n( '%d action is over one hour late.', '%d actions are over one hour late.', $counts['overdue_1h'], 'schedsense-fast-diagnostics-for-action-scheduler' ), $counts['overdue_1h'] );
			return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'warning', 'medium', $summary, __( 'Minor delays can occur because normal WordPress cron depends on site traffic.', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Refresh later and check whether the oldest pending action advances.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $counts );
		}

		return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'pass', 'none', __( 'No pending action is more than one hour overdue.', 'schedsense-fast-diagnostics-for-action-scheduler' ), '', '', $counts );
	}
}
