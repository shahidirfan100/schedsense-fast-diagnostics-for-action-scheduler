<?php
/**
 * Capability policy.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Support;

defined( 'ABSPATH' ) || exit;

final class Capabilities {
	/**
	 * Get the required capability.
	 *
	 * @return string
	 */
	public static function required() {
		/**
		 * Filters the capability required to access SchedSense.
		 *
		 * @param string $capability Default capability.
		 */
		$capability = apply_filters( 'queue_health_monitor_required_capability', 'manage_options' );
		return is_string( $capability ) && '' !== $capability ? $capability : 'manage_options';
	}

	/**
	 * Determine whether the current user may access diagnostics.
	 *
	 * @return bool
	 */
	public static function current_user_can_access() {
		return current_user_can( self::required() );
	}
}
