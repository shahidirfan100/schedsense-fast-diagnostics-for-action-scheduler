<?php
/** @package QueueHealthMonitor */
defined( 'ABSPATH' ) || exit;

$queue_health_monitor_messages = array(
	'refreshed'        => __( 'Diagnostics refreshed.', 'queue-health-monitor' ),
	'cache-cleared'    => __( 'Queue Health Monitor caches cleared.', 'queue-health-monitor' ),
	'cron-spawned'     => __( 'WordPress accepted a standard cron spawn request. This does not prove that every queued callback completed.', 'queue-health-monitor' ),
	'cron-not-spawned' => __( 'WordPress did not start another cron spawn. A cron process may already be running or spawning may be unavailable.', 'queue-health-monitor' ),
	'action-deleted'   => __( 'Failed action record deleted. This does not repair or retry its callback.', 'queue-health-monitor' ),
	'action-no-longer-failed' => __( 'This action is no longer failed, so it was not deleted.', 'queue-health-monitor' ),
	'action-delete-unavailable' => __( 'Action Scheduler’s delete function is unavailable. No action was removed.', 'queue-health-monitor' ),
	'action-delete-error' => __( 'The failed action could not be deleted. Refresh the list and check its current status.', 'queue-health-monitor' ),
);
?>
<?php if ( isset( $queue_health_monitor_messages[ $notice ] ) ) : ?>
	<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $queue_health_monitor_messages[ $notice ] ); ?></p></div>
<?php endif; ?>
