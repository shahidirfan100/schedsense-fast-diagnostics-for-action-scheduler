<?php
/**
 * Plugin Name:       Queue Health Monitor
 * Description:       Diagnose overdue, failed, and stuck Action Scheduler jobs, WP-Cron issues, loopback failures, and queue health.
 * Version:           1.0.1
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Shahid Irfan
 * Author URI:        https://profiles.wordpress.org/shahidirfan100/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       queue-health-monitor
 * Domain Path:       /languages
 *
 * @package QueueHealthMonitor
 */

defined( 'ABSPATH' ) || exit;

define( 'QUEUE_HEALTH_MONITOR_VERSION', '1.0.1' );
define( 'QUEUE_HEALTH_MONITOR_PLUGIN_FILE', __FILE__ );
define( 'QUEUE_HEALTH_MONITOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUEUE_HEALTH_MONITOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'includes/class-autoloader.php';

register_deactivation_hook( __FILE__, array( 'QueueHealthMonitor\Deactivator', 'deactivate' ) );

QueueHealthMonitor\Plugin::instance()->register();
