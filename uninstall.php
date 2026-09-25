<?php
/**
 * Queue Health Monitor uninstall cleanup.
 *
 * @package QueueHealthMonitor
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'qhm_diagnostic_snapshot' );
delete_transient( 'qhm_source_map' );
