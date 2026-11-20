<?php
/**
 * Plugin composition root.
 *
 * @package SchedSense
 */

namespace SchedSense;

use SchedSense\Admin\AdminController;
use SchedSense\Scheduler\ActionSchedulerAdapter;

defined( 'ABSPATH' ) || exit;

/** Register the plugin's admin integration. */
final class Plugin {
	/**
	 * Plugin instance for the current request.
	 *
	 * @var self|null
	 */
	private static $instance;

	/**
	 * Get the plugin instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks. No queue work occurs on frontend requests.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! is_admin() ) {
			return;
		}

		$controller = new AdminController( new ActionSchedulerAdapter() );
		$controller->register();
	}

	/** Prevent direct construction. */
	private function __construct() {}
}
