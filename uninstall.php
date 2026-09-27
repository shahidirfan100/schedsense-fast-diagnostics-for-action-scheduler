<?php
/**
 * SchedSense uninstall cleanup.
 *
 * @package SchedSense
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'qhm_diagnostic_snapshot' );
delete_transient( 'qhm_source_map' );
