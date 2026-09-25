<?php
/**
 * Bounded diagnostic caches.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Support;

defined( 'ABSPATH' ) || exit;

final class Cache {
	const SNAPSHOT_KEY = 'qhm_diagnostic_snapshot';
	const SOURCE_KEY   = 'qhm_source_map';

	/**
	 * Get the cached snapshot.
	 *
	 * @return array|false
	 */
	public static function get_snapshot() {
		$value = get_transient( self::SNAPSHOT_KEY );
		return is_array( $value ) ? $value : false;
	}

	/**
	 * Cache a snapshot for a conservative period.
	 *
	 * @param array $snapshot Snapshot data.
	 * @return void
	 */
	public static function set_snapshot( array $snapshot ) {
		set_transient( self::SNAPSHOT_KEY, $snapshot, 180 );
	}

	/**
	 * Clear all Queue Health Monitor caches.
	 *
	 * @return void
	 */
	public static function clear() {
		delete_transient( self::SNAPSHOT_KEY );
		delete_transient( self::SOURCE_KEY );
	}
}
