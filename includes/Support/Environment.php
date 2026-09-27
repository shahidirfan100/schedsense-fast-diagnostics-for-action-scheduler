<?php
/**
 * Environment facts used by diagnostics and reports.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Support;

defined( 'ABSPATH' ) || exit;

final class Environment {
	/**
	 * Collect non-sensitive environment facts.
	 *
	 * @return array
	 */
	public function collect() {
		global $wpdb;

		return array(
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
			'database_version'  => method_exists( $wpdb, 'db_version' ) ? $wpdb->db_version() : __( 'Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'memory_limit'      => ini_get( 'memory_limit' ),
			'max_execution'     => ini_get( 'max_execution_time' ),
			'timezone'          => wp_timezone_string(),
			'multisite'         => is_multisite(),
			'woocommerce'       => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'disable_wp_cron'   => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'alternate_wp_cron' => defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON,
			'cron_lock_timeout' => defined( 'WP_CRON_LOCK_TIMEOUT' ) ? WP_CRON_LOCK_TIMEOUT : 60,
		);
	}
}
