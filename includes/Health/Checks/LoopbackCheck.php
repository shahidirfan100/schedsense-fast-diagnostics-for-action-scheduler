<?php
/**
 * Local loopback request check.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class LoopbackCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		$url      = site_url( 'wp-cron.php' );
		$body     = array( 'site-health' => 'loopback-test' );
		$cookies  = wp_unslash( $_COOKIE );
		$timeout  = 10;
		$headers  = array( 'Cache-Control' => 'no-cache' );
		if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Forward Basic Auth to this site's own Site Health-style loopback request; do not store or display it.
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
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
				__( 'Loopback request', 'queue-health-monitor' ),
				'warning',
				'medium',
				__( 'The local wp-cron.php request failed.', 'queue-health-monitor' ),
				sanitize_text_field( $response->get_error_message() ),
				__( 'Check server loopback policy, DNS, firewall, authentication, and security-plugin rules.', 'queue-health-monitor' ),
				array(
					'response_ms' => $elapsed,
					'error_code'  => sanitize_key( $response->get_error_code() ),
				)
			);
		}

		$code = absint( wp_remote_retrieve_response_code( $response ) );
		if ( 200 === $code ) {
			/* translators: 1: HTTP status code, 2: Response time in milliseconds. */
			$evidence = sprintf( __( 'HTTP %1$d in %2$d ms.', 'queue-health-monitor' ), $code, $elapsed );
			return new HealthResult(
				'loopback',
				__( 'Loopback request', 'queue-health-monitor' ),
				'pass',
				'none',
				__( 'The local wp-cron.php endpoint responded.', 'queue-health-monitor' ),
				$evidence,
				'',
				array(
					'http_code'   => $code,
					'response_ms' => $elapsed,
				)
			);
		}

		/* translators: %d: HTTP response code. */
		$summary = sprintf( __( 'The local wp-cron.php request returned HTTP %d.', 'queue-health-monitor' ), $code );
		return new HealthResult(
			'loopback',
			__( 'Loopback request', 'queue-health-monitor' ),
			'warning',
			'medium',
			$summary,
			__( 'A firewall, CDN, security plugin, authentication rule, or server configuration may be blocking internal requests.', 'queue-health-monitor' ),
			__( 'Review the layer returning this status; Queue Health Monitor cannot attribute it to a specific vendor without direct evidence.', 'queue-health-monitor' ),
			array(
				'http_code'   => $code,
				'response_ms' => $elapsed,
			)
		);
	}
}
