<?php
/**
 * WP-Cron configuration check.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Health\Checks;

use QueueHealthMonitor\Health\HealthCheckInterface;
use QueueHealthMonitor\Health\HealthResult;

defined( 'ABSPATH' ) || exit;

final class CronCheck implements HealthCheckInterface {
	/** @inheritDoc */
	public function run( array $context ) {
		$environment = $context['environment'];
		if ( $environment['disable_wp_cron'] ) {
			return new HealthResult(
				'wp-cron',
				__( 'WP-Cron configuration', 'queue-health-monitor' ),
				'informational',
				'low',
				__( "WordPress's traffic-triggered cron is disabled.", 'queue-health-monitor' ),
				__( 'This can be correct when a server-level cron invokes wp-cron.php or WP-CLI. PHP cannot reliably prove that every external scheduler exists.', 'queue-health-monitor' ),
				__( 'If the queue is stale, verify the server-level cron with the hosting provider.', 'queue-health-monitor' ),
				array(
					'disable_wp_cron'   => true,
					'alternate_wp_cron' => $environment['alternate_wp_cron'],
				)
			);
		}

		return new HealthResult( 'wp-cron', __( 'WP-Cron configuration', 'queue-health-monitor' ), 'pass', 'none', __( "WordPress's traffic-triggered cron is enabled.", 'queue-health-monitor' ), $environment['alternate_wp_cron'] ? __( 'ALTERNATE_WP_CRON is enabled.', 'queue-health-monitor' ) : __( 'Standard cron spawning is configured.', 'queue-health-monitor' ) );
	}
}
