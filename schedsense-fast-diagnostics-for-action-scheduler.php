<?php
/**
 * Plugin Name:       SchedSense: Fast Diagnostics for Action Scheduler
 * Plugin URI:        https://github.com/shahidirfan100/schedsense-fast-diagnostics-for-action-scheduler
 * Description:       Diagnose overdue, failed, and stuck Action Scheduler jobs, WP-Cron issues, loopback failures, and queue health.
 * Version:           1.0.3
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

define( 'SCHEDSENSE_VERSION', '1.0.3' );
define( 'SCHEDSENSE_PLUGIN_FILE', __FILE__ );
define( 'SCHEDSENSE_PLUGIN_DIR', plugin_dir_path( SCHEDSENSE_PLUGIN_FILE ) );
define( 'SCHEDSENSE_PLUGIN_URL', plugin_dir_url( SCHEDSENSE_PLUGIN_FILE ) );
define( 'SCHEDSENSE_ADMIN_PAGE_SLUG', 'schedsense_diagnostics' );

require_once SCHEDSENSE_PLUGIN_DIR . 'includes/class-autoloader.php';

register_deactivation_hook( __FILE__, array( 'SchedSense\Deactivator', 'deactivate' ) );

SchedSense\Plugin::instance()->register();
