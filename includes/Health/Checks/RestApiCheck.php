<?php
/**
 * Local REST API check.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class RestApiCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		$url      = add_query_arg( 'context', 'edit', rest_url( 'wp/v2/types/post' ) );
		$cookies  = wp_unslash( $_COOKIE );
		$headers  = array(
			'Cache-Control' => 'no-cache',
			'X-WP-Nonce'    => wp_create_nonce( 'wp_rest' ),
		);
		$timeout  = 10;
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is the WordPress core Site Health SSL filter; its name must remain unchanged.
		$sslverify = apply_filters( 'https_local_ssl_verify', false, $url );
		if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Forward Basic Auth to this site's own Site Health-style REST request; do not store or display it.
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		$started  = microtime( true );
		$response = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout', 'sslverify' ) );
		$elapsed  = round( ( microtime( true ) - $started ) * 1000 );

		if ( is_wp_error( $response ) ) {
			return new HealthResult(
				'rest-api',
				__( 'REST API', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'warning',
				'low',
				__( 'The local REST API request failed.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				sanitize_text_field( $response->get_error_message() ),
				__( 'Review REST authentication, firewall, and server loopback settings.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				array(
					'response_ms' => $elapsed,
					'error_code'  => sanitize_key( $response->get_error_code() ),
				)
			);
		}

		$code = absint( wp_remote_retrieve_response_code( $response ) );
		if ( 200 !== $code ) {
			/* translators: %d: HTTP response code. */
			$summary = sprintf( __( 'The local REST API returned HTTP %d.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $code );
			return new HealthResult(
				'rest-api',
				__( 'REST API', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'warning',
				'low',
				$summary,
				__( 'Authentication or security policy may be restricting the REST endpoint.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Confirm whether this response is intentional before changing security rules.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				array(
					'http_code'   => $code,
					'response_ms' => $elapsed,
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['capabilities'] ) ) {
			return new HealthResult(
				'rest-api',
				__( 'REST API', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'warning',
				'low',
				__( 'The REST API response did not include the expected post-type data.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'The endpoint responded, but its JSON structure did not match WordPress core.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Review REST API filters and security rules before changing them.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				array(
					'http_code'   => $code,
					'response_ms' => $elapsed,
				)
			);
		}

		/* translators: %d: Response time in milliseconds. */
		$evidence = sprintf( __( 'HTTP %1$d; response in %2$d ms.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $code, $elapsed );
		return new HealthResult(
			'rest-api',
			__( 'REST API', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'pass',
			'none',
			__( 'The local REST API post-type endpoint responded with valid data.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			$evidence,
			'',
			array(
				'http_code'   => $code,
				'response_ms' => $elapsed,
			)
		);
	}
}
