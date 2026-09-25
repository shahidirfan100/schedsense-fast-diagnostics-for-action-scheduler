<?php
/**
 * Deactivation tasks.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor;

defined( 'ABSPATH' ) || exit;

final class Deactivator {
	/**
	 * Remove Queue Health Monitor's temporary data only.
	 *
	 * @return void
	 */
	public static function deactivate() {
		Support\Cache::clear();
	}
}
