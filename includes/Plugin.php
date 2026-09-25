<?php
/**
 * Plugin composition root.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor;

use QueueHealthMonitor\Admin\AdminController;
use QueueHealthMonitor\Scheduler\ActionSchedulerAdapter;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	/** @var self|null */
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
