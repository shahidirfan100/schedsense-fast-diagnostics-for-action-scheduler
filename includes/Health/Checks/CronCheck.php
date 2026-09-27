<?php
/**
 * WP-Cron configuration check.
 *
 * @package SchedSense
 */

namespace SchedSense\Health\Checks;

use SchedSense\Health\HealthCheckInterface;
use SchedSense\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class CronCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		$environment = $context['environment'];
		if ( $environment['disable_wp_cron'] ) {
			return new HealthResult(
				'wp-cron',
				__( 'WP-Cron configuration', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'informational',
				'low',
				__( "WordPress's traffic-triggered cron is disabled.", 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'This can be correct when a server-level cron invokes wp-cron.php or WP-CLI. PHP cannot reliably prove that every external scheduler exists.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'If the queue is stale, verify the server-level cron with the hosting provider.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				array(
					'disable_wp_cron'   => true,
					'alternate_wp_cron' => $environment['alternate_wp_cron'],
				)
			);
		}

		return new HealthResult( 'wp-cron', __( 'WP-Cron configuration', 'schedsense-fast-diagnostics-for-action-scheduler' ), 'pass', 'none', __( "WordPress's traffic-triggered cron is enabled.", 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['alternate_wp_cron'] ? __( 'ALTERNATE_WP_CRON is enabled.', 'schedsense-fast-diagnostics-for-action-scheduler' ) : __( 'Standard cron spawning is configured.', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
	}
}
