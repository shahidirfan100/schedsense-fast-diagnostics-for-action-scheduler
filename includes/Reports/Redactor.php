<?php
/**
 * Privacy-conscious report redaction.
 *
 * @package SchedSense
 */

namespace SchedSense\Reports;

defined( 'ABSPATH' ) || exit;

final class Redactor {
	/**
	 * Redact secrets, emails, sensitive URL query values, and absolute paths.
	 *
	 * @param string $value Input text.
	 * @return string
	 */
	public function redact( $value ) {
		$value = (string) $value;
		$value = preg_replace( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted-email]', $value );
		$value = preg_replace( '/([?&](?:token|key|secret|password|auth|signature|nonce)=)[^&\s]+/i', '$1[redacted]', $value );
		$value = preg_replace( '/\b(api[_-]?key|password|secret|authorization)(\s*[:=]\s*)[^\s,;]+/i', '$1$2[redacted]', $value );

		$roots = array( plugin_dir_path( SCHEDSENSE_PLUGIN_FILE ) );
		$uploads = wp_upload_dir( null, false );
		if ( is_array( $uploads ) && isset( $uploads['basedir'] ) ) {
			$roots[] = $uploads['basedir'];
		}
		$theme_root = get_theme_root();
		if ( is_string( $theme_root ) && '' !== $theme_root ) {
			$roots[] = dirname( $theme_root );
		}
		if ( function_exists( 'get_home_path' ) ) {
			$roots[] = get_home_path();
		}

		$normalized_roots = array();
		foreach ( $roots as $root ) {
			$root = untrailingslashit( wp_normalize_path( (string) $root ) );
			if ( '' !== $root && '/' !== $root ) {
				$normalized_roots[] = $root;
			}
		}
		$roots = array_unique( $normalized_roots );
		usort(
			$roots,
			static function ( $left, $right ) {
				return strlen( $right ) - strlen( $left );
			}
		);
		$value = wp_normalize_path( $value );
		foreach ( $roots as $root ) {
			$pattern = '~' . preg_quote( $root, '~' ) . '(?:/|$)~i';
			$value   = preg_replace( $pattern, '/', $value );
		}
		return $value;
	}
}
