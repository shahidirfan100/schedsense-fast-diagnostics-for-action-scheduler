<?php
/**
 * Health check contract.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Health;

defined( 'ABSPATH' ) || exit;

interface HealthCheckInterface {
	/**
	 * Execute a single isolated check.
	 *
	 * @param array $context Previously collected scan context.
	 * @return HealthResult
	 */
	public function run( array $context );
}
