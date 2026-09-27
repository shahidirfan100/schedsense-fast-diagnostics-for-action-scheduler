<?php
/**
 * SchedSense uninstall cleanup.
 *
 * @package SchedSense
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'schedsense_diagnostic_snapshot' );
delete_transient( 'schedsense_source_map' );
