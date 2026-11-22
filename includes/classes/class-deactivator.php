<?php
/**
 * Deactivation tasks.
 *
 * @package SchedSense
 */

namespace SchedSense;

defined( 'ABSPATH' ) || exit;

/** Clear transient diagnostics when the plugin is deactivated. */
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
