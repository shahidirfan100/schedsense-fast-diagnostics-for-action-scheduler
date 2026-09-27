<?php
/** @package SchedSense */
defined( 'ABSPATH' ) || exit;

$queue_health_monitor_messages = array(
	'refreshed'        => __( 'Diagnostics refreshed.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'cache-cleared'    => __( 'SchedSense caches cleared.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'cron-spawned'     => __( 'WordPress accepted a standard cron spawn request. This does not prove that every queued callback completed.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'cron-not-spawned' => __( 'WordPress did not start another cron spawn. A cron process may already be running or spawning may be unavailable.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'action-deleted'   => __( 'Failed action record deleted. This does not repair or retry its callback.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'action-no-longer-failed' => __( 'This action is no longer failed, so it was not deleted.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'action-delete-unavailable' => __( 'Action Scheduler’s delete function is unavailable. No action was removed.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'action-delete-error' => __( 'The failed action could not be deleted. Refresh the list and check its current status.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
);
?>
<?php if ( isset( $queue_health_monitor_messages[ $notice ] ) ) : ?>
	<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $queue_health_monitor_messages[ $notice ] ); ?></p></div>
<?php endif; ?>
