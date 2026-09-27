<?php
/**
 * Small PSR-4 style autoloader for SchedSense classes.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor;

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
