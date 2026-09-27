<?php
/**
 * Plugin Name:       SchedSense: Fast Diagnostics for Action Scheduler
 * Plugin URI:        https://github.com/shahidirfan100/schedsense-fast-diagnostics-for-action-scheduler
 * Description:       Diagnose overdue, failed, and stuck Action Scheduler jobs, WP-Cron issues, loopback failures, and queue health.
 * Version:           1.0.2
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

define( 'QUEUE_HEALTH_MONITOR_VERSION', '1.0.2' );
define( 'QUEUE_HEALTH_MONITOR_PLUGIN_FILE', __FILE__ );
define( 'QUEUE_HEALTH_MONITOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUEUE_HEALTH_MONITOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'includes/class-autoloader.php';

register_deactivation_hook( __FILE__, array( 'QueueHealthMonitor\Deactivator', 'deactivate' ) );

QueueHealthMonitor\Plugin::instance()->register();
