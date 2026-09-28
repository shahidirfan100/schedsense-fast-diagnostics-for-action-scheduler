<?php
/**
 * Local loopback request check.
 *
 * @package SchedSense
 */

namespace SchedSense\Health\Checks;

use SchedSense\Health\HealthCheckInterface;
use SchedSense\Health\HealthResult;
use SchedSense\Support\RequestContext;

defined( 'ABSPATH' ) || exit;

final class LoopbackCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		// WordPress core's spawn_cron() uses this same API and endpoint; it preserves subdirectory installs.
		$url      = site_url( 'wp-cron.php' );
		$body     = array( 'site-health' => 'loopback-test' );
		$cookies  = RequestContext::cookies_for_site_request();
		$timeout  = 10;
		$headers  = array( 'Cache-Control' => 'no-cache' );
		$authorization = RequestContext::basic_authorization_header();
		if ( '' !== $authorization ) {
			$headers['Authorization'] = $authorization;
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is the WordPress core Site Health SSL filter; its name must remain unchanged.
		$sslverify = apply_filters( 'https_local_ssl_verify', false, $url );
		$started   = microtime( true );
		$response = wp_remote_post(
			$url,
			compact( 'body', 'cookies', 'headers', 'timeout', 'sslverify' )
		);
		$elapsed  = round( ( microtime( true ) - $started ) * 1000 );

		if ( is_wp_error( $response ) ) {
			return new HealthResult(
				'loopback',
				__( 'Loopback request', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'warning',
				'medium',
				__( 'The local wp-cron.php request failed.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				sanitize_text_field( $response->get_error_message() ),
				__( 'Check server loopback policy, DNS, firewall, authentication, and security-plugin rules.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				array(
					'response_ms' => $elapsed,
					'error_code'  => sanitize_key( $response->get_error_code() ),
				)
			);
		}

		$code = absint( wp_remote_retrieve_response_code( $response ) );
		if ( 200 === $code ) {
			/* translators: 1: HTTP status code, 2: Response time in milliseconds. */
			$evidence = sprintf( __( 'HTTP %1$d in %2$d ms.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $code, $elapsed );
			return new HealthResult(
				'loopback',
				__( 'Loopback request', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'pass',
				'none',
				__( 'The local wp-cron.php endpoint responded.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				$evidence,
				'',
				array(
					'http_code'   => $code,
					'response_ms' => $elapsed,
				)
			);
		}

		/* translators: %d: HTTP response code. */
		$summary = sprintf( __( 'The local wp-cron.php request returned HTTP %d.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $code );
		return new HealthResult(
			'loopback',
			__( 'Loopback request', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'warning',
			'medium',
			$summary,
			__( 'A firewall, CDN, security plugin, authentication rule, or server configuration may be blocking internal requests.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			__( 'Review the layer returning this status; SchedSense cannot attribute it to a specific vendor without direct evidence.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			array(
				'http_code'   => $code,
				'response_ms' => $elapsed,
			)
		);
	}
}
