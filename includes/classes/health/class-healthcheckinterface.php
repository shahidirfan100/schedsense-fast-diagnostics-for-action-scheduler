<?php
/**
 * Health check contract.
 *
 * @package SchedSense
 */

namespace SchedSense\Health;

defined( 'ABSPATH' ) || exit;

/** Define the input and result for a diagnostic check. */
interface HealthCheckInterface {
	/**
	 * Execute a single isolated check.
	 *
	 * @param array $context Previously collected scan context.
	 * @return HealthResult
	 */
	public function run( array $context );
}
