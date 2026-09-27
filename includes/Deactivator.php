<?php
/**
 * Deactivation tasks.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor;

defined( 'ABSPATH' ) || exit;

final class Deactivator {
	/**
	 * Remove SchedSense's temporary data only.
	 *
	 * @return void
	 */
	public static function deactivate() {
		Support\Cache::clear();
	}
}
