<?php
/** @package QueueHealthMonitor */
defined( 'ABSPATH' ) || exit;
$queue_health_monitor_age_labels = array(
	'under_1h' => __( 'Under 1 hour', 'queue-health-monitor' ),
	'1_to_6h'  => __( '1–6 hours', 'queue-health-monitor' ),
	'6_to_24h' => __( '6–24 hours', 'queue-health-monitor' ),
	'over_24h' => __( 'Over 24 hours', 'queue-health-monitor' ),
	'over_7d'  => __( 'Over 7 days', 'queue-health-monitor' ),
	'not_due'  => __( 'Not due yet', 'queue-health-monitor' ),
);
$queue_health_monitor_age_options = array( '' => __( 'All ages', 'queue-health-monitor' ) );
if ( 'pending' === $failure_data['status'] ) {
	$queue_health_monitor_age_options = array_merge(
		$queue_health_monitor_age_options,
		array(
			'under_1h'    => __( 'Under 1 hour overdue', 'queue-health-monitor' ),
			'one_to_6h'   => __( '1–6 hours overdue', 'queue-health-monitor' ),
			'six_to_24h'  => __( '6–24 hours overdue', 'queue-health-monitor' ),
			'one_to_7d'   => __( '1–7 days overdue', 'queue-health-monitor' ),
			'overdue_7d'  => __( 'Over 7 days overdue', 'queue-health-monitor' ),
			'overdue_1h'  => __( 'Over 1 hour overdue', 'queue-health-monitor' ),
			'overdue_24h' => __( 'Over 24 hours overdue', 'queue-health-monitor' ),
		)
	);
} elseif ( 'in-progress' === $failure_data['status'] ) {
	$queue_health_monitor_age_options['stuck'] = __( 'No update for over 1 hour', 'queue-health-monitor' );
}
if ( empty( $failure_data['available'] ) ) {
	$queue_health_monitor_message = 'scheduler-unavailable' === $failure_data['reason']
		? __( 'Action Scheduler is not available on this site, so its actions cannot be listed.', 'queue-health-monitor' )
		: __( 'The Action Scheduler query failed. No empty result is shown because the action list could not be verified.', 'queue-health-monitor' );
	?>
	<div class="notice notice-warning inline"><p><?php echo esc_html( $queue_health_monitor_message ); ?></p></div>
	<?php
	return;
}
?>
<section class="qhm-panel">
	<div class="qhm-section-heading"><div><h2><?php echo esc_html__( 'Scheduled actions', 'queue-health-monitor' ); ?></h2><p><?php echo esc_html__( 'A bounded, server-side page. Action arguments are intentionally omitted for privacy.', 'queue-health-monitor' ); ?></p></div></div>
	<form method="get" class="qhm-filters">
		<input type="hidden" name="page" value="qhm"><input type="hidden" name="tab" value="failures">
		<label><span><?php echo esc_html__( 'Status', 'queue-health-monitor' ); ?></span><select name="status"><option value="failed" <?php selected( $failure_data['status'], 'failed' ); ?>><?php echo esc_html__( 'Failed', 'queue-health-monitor' ); ?></option><option value="pending" <?php selected( $failure_data['status'], 'pending' ); ?>><?php echo esc_html__( 'Pending', 'queue-health-monitor' ); ?></option><option value="in-progress" <?php selected( $failure_data['status'], 'in-progress' ); ?>><?php echo esc_html__( 'In progress', 'queue-health-monitor' ); ?></option><option value="complete" <?php selected( $failure_data['status'], 'complete' ); ?>><?php echo esc_html__( 'Completed', 'queue-health-monitor' ); ?></option></select></label>
		<label><span><?php echo esc_html__( 'Age range', 'queue-health-monitor' ); ?></span><select name="age">
			<?php foreach ( $queue_health_monitor_age_options as $queue_health_monitor_age_option => $queue_health_monitor_age_label ) : ?>
				<option value="<?php echo esc_attr( $queue_health_monitor_age_option ); ?>" <?php selected( $failure_data['age'], $queue_health_monitor_age_option ); ?>><?php echo esc_html( $queue_health_monitor_age_label ); ?></option>
			<?php endforeach; ?>
		</select></label>
		<label><span><?php echo esc_html__( 'Exact hook', 'queue-health-monitor' ); ?></span><input type="text" name="hook" value="<?php echo esc_attr( $failure_data['hook'] ); ?>" placeholder="<?php echo esc_attr__( 'For example: woocommerce_cleanup_sessions', 'queue-health-monitor' ); ?>"></label>
		<label><span><?php echo esc_html__( 'Exact group', 'queue-health-monitor' ); ?></span><input type="text" name="group" value="<?php echo esc_attr( $failure_data['group'] ); ?>"></label>
		<button class="button" type="submit"><?php echo esc_html__( 'Filter', 'queue-health-monitor' ); ?></button>
	</form>
	<p class="qhm-results-count">
		<?php
		/* translators: 1: Number of actions on the current page, 2: Total number of matching actions. */
		echo esc_html( sprintf( __( 'Showing %1$s of %2$s matching actions.', 'queue-health-monitor' ), number_format_i18n( count( $failure_data['actions'] ) ), number_format_i18n( $failure_data['total'] ) ) );
		?>
	</p>
	<div class="qhm-table-wrap"><table class="widefat striped qhm-table"><caption class="screen-reader-text"><?php echo esc_html__( 'Action Scheduler actions', 'queue-health-monitor' ); ?></caption><thead><tr><th><?php echo esc_html__( 'Action', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Hook', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Source', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Group', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Scheduled', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Status', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Recurrence', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Age', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Indicator', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Next step', 'queue-health-monitor' ); ?></th></tr></thead><tbody>
	<?php
	if ( empty( $failure_data['actions'] ) ) :
		?>
		<tr><td colspan="10"><?php echo esc_html__( 'No matching actions found.', 'queue-health-monitor' ); ?></td></tr><?php endif; ?>
	<?php foreach ( $failure_data['actions'] as $action ) : ?>
		<?php
		$queue_health_monitor_scheduled_in_future = $action['scheduled'] > time();
		$queue_health_monitor_age_display         = ! $action['scheduled'] ? '—' : human_time_diff( $queue_health_monitor_scheduled_in_future ? time() : $action['scheduled'], $queue_health_monitor_scheduled_in_future ? $action['scheduled'] : time() );
		if ( $queue_health_monitor_scheduled_in_future && $action['scheduled'] ) {
			/* translators: %s: Time until the action is scheduled. */
			$queue_health_monitor_age_display = sprintf( __( 'In %s', 'queue-health-monitor' ), $queue_health_monitor_age_display );
		} elseif ( $action['scheduled'] ) {
			/* translators: %s: Time since the action was scheduled. */
			$queue_health_monitor_age_display = sprintf( __( '%s ago', 'queue-health-monitor' ), $queue_health_monitor_age_display );
		}
		$queue_health_monitor_next_step = 'failed' === $action['status']
			? __( 'Review callback failure', 'queue-health-monitor' )
			: ( 'not_due' === $action['age_bucket']
				? __( 'No action needed yet', 'queue-health-monitor' )
				: ( 'in-progress' === $action['status']
					? __( 'Check worker activity', 'queue-health-monitor' )
					: __( 'Check queue source and cron', 'queue-health-monitor' ) ) );
		?>
		<tr><td class="qhm-action-cell">#<?php echo esc_html( $action['id'] ); ?>
			<?php if ( 'failed' === $action['status'] ) : ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="qhm-delete-form" data-qhm-confirm="<?php echo esc_attr__( 'Delete this failed action record? This cannot be undone and will not fix or retry its callback.', 'queue-health-monitor' ); ?>">
					<input type="hidden" name="action" value="qhm_delete_failed_action">
					<input type="hidden" name="action_id" value="<?php echo esc_attr( $action['id'] ); ?>">
					<input type="hidden" name="hook" value="<?php echo esc_attr( $failure_data['hook'] ); ?>">
					<input type="hidden" name="group" value="<?php echo esc_attr( $failure_data['group'] ); ?>">
					<?php wp_nonce_field( 'qhm_delete_failed_action' ); ?>
					<button type="submit" class="qhm-delete-button button-link-delete" aria-label="<?php echo esc_attr( sprintf( __( 'Delete failed action #%s', 'queue-health-monitor' ), $action['id'] ) ); ?>"><?php echo esc_html__( 'Delete', 'queue-health-monitor' ); ?></button>
				</form>
			<?php endif; ?>
		</td><td><code><?php echo esc_html( $action['hook'] ); ?></code></td><td><?php echo esc_html( $action['source']['name'] ); ?><small><?php echo esc_html( ucfirst( $action['source']['confidence'] ) ); ?></small></td><td><?php echo esc_html( ! empty( $action['group'] ) ? $action['group'] : '—' ); ?></td><td><?php echo esc_html( $action['scheduled'] ? wp_date( 'Y-m-d H:i:s', $action['scheduled'] ) : '—' ); ?></td><td><span class="qhm-pill"><?php echo esc_html( $action['status'] ); ?></span></td><td><?php echo esc_html( $action['recurring'] ? __( 'Recurring', 'queue-health-monitor' ) : __( 'One-time', 'queue-health-monitor' ) ); ?></td><td><?php echo esc_html( $queue_health_monitor_age_display ); ?></td><td><?php echo esc_html( isset( $queue_health_monitor_age_labels[ $action['age_bucket'] ] ) ? $queue_health_monitor_age_labels[ $action['age_bucket'] ] : __( 'Unavailable', 'queue-health-monitor' ) ); ?></td><td><?php echo esc_html( $queue_health_monitor_next_step ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table></div>
	<?php
	$queue_health_monitor_total_pages = (int) ceil( $failure_data['total'] / $failure_data['per_page'] );
	if ( $queue_health_monitor_total_pages > 1 ) {
		echo wp_kses_post(
			paginate_links(
				array(
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'current' => $failure_data['page'],
					'total'   => $queue_health_monitor_total_pages,
					'type'    => 'list',
				)
			)
		);
	}
	?>
</section>
