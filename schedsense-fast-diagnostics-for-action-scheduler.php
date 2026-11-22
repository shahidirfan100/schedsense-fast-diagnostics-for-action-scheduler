<?php
/**
 * Plugin Name:       SchedSense: Fast Diagnostics for Action Scheduler
 * Plugin URI:        https://github.com/shahidirfan100/schedsense-fast-diagnostics-for-action-scheduler
 * Description:       Diagnose overdue, failed, and stuck Action Scheduler jobs, WP-Cron issues, loopback failures, and queue health.
 * Version:           1.0.7
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Shahid Irfan
 * Author URI:        https://profiles.wordpress.org/shahidirfan100/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       schedsense-fast-diagnostics-for-action-scheduler
 * Domain Path:       /languages
 *
 * @package SchedSense
 */

defined( 'ABSPATH' ) || exit;

define( 'SCHEDSENSE_VERSION', '1.0.7' );
define( 'SCHEDSENSE_PLUGIN_FILE', __FILE__ );
define( 'SCHEDSENSE_PLUGIN_DIR', plugin_dir_path( SCHEDSENSE_PLUGIN_FILE ) );
define( 'SCHEDSENSE_PLUGIN_URL', plugin_dir_url( SCHEDSENSE_PLUGIN_FILE ) );
define( 'SCHEDSENSE_ADMIN_PAGE_SLUG', 'schedsense_diagnostics' );

$schedsense_autoloader = SCHEDSENSE_PLUGIN_DIR . 'includes/class-autoloader.php';
if ( is_readable( $schedsense_autoloader ) ) {
	require_once $schedsense_autoloader;
}

register_deactivation_hook(
	__FILE__,
	static function () {
		if ( class_exists( SchedSense\Deactivator::class ) && class_exists( SchedSense\Support\Cache::class ) ) {
			SchedSense\Deactivator::deactivate();
		}
	}
);

if ( ! class_exists( SchedSense\Plugin::class ) || ! SchedSense\Plugin::instance()->register() ) {
	add_action(
		'admin_notices',
		static function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				wp_admin_notice(
					esc_html__( 'SchedSense could not start because its installation is incomplete. Reinstall SchedSense using a complete plugin ZIP.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
					array( 'type' => 'error' )
				);
			}
		}
	);
}
