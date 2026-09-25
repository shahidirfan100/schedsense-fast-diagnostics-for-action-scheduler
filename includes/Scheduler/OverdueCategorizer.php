<?php
/**
 * Pure overdue-age categorization.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Scheduler;

defined( 'ABSPATH' ) || exit;

final class OverdueCategorizer {
	/**
	 * Categorize seconds overdue using conservative buckets.
	 *
	 * @param int $seconds Seconds overdue.
	 * @return string
	 */
	public function categorize( $seconds ) {
		$seconds = max( 0, (int) $seconds );
		if ( $seconds >= 7 * DAY_IN_SECONDS ) {
			return 'over_7d';
		}
		if ( $seconds >= DAY_IN_SECONDS ) {
			return 'over_24h';
		}
		if ( $seconds >= 6 * HOUR_IN_SECONDS ) {
			return '6_to_24h';
		}
		if ( $seconds >= HOUR_IN_SECONDS ) {
			return '1_to_6h';
		}
		return 'under_1h';
	}
}
