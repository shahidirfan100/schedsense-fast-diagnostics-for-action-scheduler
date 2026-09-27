<?php
/**
 * Sanitized request context for local health checks.
 *
 * @package SchedSense
 */

namespace SchedSense\Support;

defined( 'ABSPATH' ) || exit;

final class RequestContext {
	/**
	 * Get sanitized cookies for a same-site HTTP request.
	 *
	 * @return array
	 */
	public static function cookies_for_site_request() {
		$cookies = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The cookie array is checked and each scalar name/value is sanitized before forwarding.
		if ( ! isset( $_COOKIE ) || ! is_array( $_COOKIE ) ) {
			return $cookies;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are scalar-checked and sanitized individually below.
		foreach ( wp_unslash( $_COOKIE ) as $name => $value ) {
			if ( ! is_string( $name ) || ! is_scalar( $value ) ) {
				continue;
			}

			$name = sanitize_text_field( $name );
			if ( '' === $name ) {
				continue;
			}

			$cookies[ $name ] = sanitize_text_field( (string) $value );
		}

		return $cookies;
	}

	/**
	 * Get a sanitized Basic Authentication header for same-site requests.
	 *
	 * @return string
	 */
	public static function basic_authorization_header() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Both server values are type-checked before being unslashed and sanitized below.
		if ( ! isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) || ! is_string( $_SERVER['PHP_AUTH_USER'] ) || ! is_string( $_SERVER['PHP_AUTH_PW'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Type validation is above; values are unslashed and sanitized before use.
		$username = sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Type validation is above; values are unslashed and sanitized before use.
		$password = sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		if ( '' === $username && '' === $password ) {
			return '';
		}

		return 'Basic ' . base64_encode( $username . ':' . $password );
	}
}
