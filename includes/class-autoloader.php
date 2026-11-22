<?php
/**
 * Load SchedSense classes from WordPress-style filenames.
 *
 * @package SchedSense
 */

namespace SchedSense;

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative  = strtolower( str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) );
		$directory = dirname( $relative );
		$file      = __DIR__ . '/classes/' . ( '.' === $directory ? '' : $directory . '/' ) . 'class-' . basename( $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
