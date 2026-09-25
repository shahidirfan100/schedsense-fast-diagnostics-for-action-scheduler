<?php
/**
 * Conservative overdue queue check.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class OverdueActionsCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		if ( empty( $context['queue']['available'] ) || empty( $context['queue']['data_available'] ) ) {
			return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'queue-health-monitor' ), 'unavailable', 'none', __( 'Queue data is unavailable.', 'queue-health-monitor' ) );
		}

		$counts = $context['queue']['counts'];
		if ( $counts['overdue_24h'] >= 20 || $counts['overdue_7d'] > 0 ) {
			/* translators: 1: Number overdue by 24 hours, 2: Number overdue by seven days. */
			$summary = sprintf( __( '%1$d actions are over 24 hours late; %2$d are over seven days late.', 'queue-health-monitor' ), $counts['overdue_24h'], $counts['overdue_7d'] );
			return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'queue-health-monitor' ), 'critical', 'high', $summary, __( 'A materially stale queue is unlikely to be explained by normal traffic-triggered cron delay alone.', 'queue-health-monitor' ), __( 'Review WP-Cron and loopback results, then inspect the dominant queue sources.', 'queue-health-monitor' ), $counts );
		}

		if ( $counts['overdue_1h'] > 0 ) {
			/* translators: %d: Number of actions overdue by at least one hour. */
			$summary = sprintf( _n( '%d action is over one hour late.', '%d actions are over one hour late.', $counts['overdue_1h'], 'queue-health-monitor' ), $counts['overdue_1h'] );
			return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'queue-health-monitor' ), 'warning', 'medium', $summary, __( 'Minor delays can occur because normal WordPress cron depends on site traffic.', 'queue-health-monitor' ), __( 'Refresh later and check whether the oldest pending action advances.', 'queue-health-monitor' ), $counts );
		}

		return new HealthResult( 'overdue-actions', __( 'Overdue actions', 'queue-health-monitor' ), 'pass', 'none', __( 'No pending action is more than one hour overdue.', 'queue-health-monitor' ), '', '', $counts );
	}
}
