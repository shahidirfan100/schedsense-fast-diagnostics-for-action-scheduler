<?php
/**
 * Privacy-conscious report redaction.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Reports;

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

		$roots = array_filter(
			array(
				defined( 'ABSPATH' ) ? wp_normalize_path( ABSPATH ) : '',
				defined( 'WP_CONTENT_DIR' ) ? wp_normalize_path( dirname( WP_CONTENT_DIR ) ) : '',
			)
		);
		foreach ( $roots as $root ) {
			$value = str_replace( trailingslashit( $root ), '/', wp_normalize_path( $value ) );
		}
		return $value;
	}
}
