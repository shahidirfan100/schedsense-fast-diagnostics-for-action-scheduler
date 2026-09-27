<?php
/** @package SchedSense */
defined( 'ABSPATH' ) || exit;
$schedsense_age_labels = array(
	'under_1h' => __( 'Under 1 hour', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'1_to_6h'  => __( '1–6 hours', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'6_to_24h' => __( '6–24 hours', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'over_24h' => __( 'Over 24 hours', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'over_7d'  => __( 'Over 7 days', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'not_due'  => __( 'Not due yet', 'schedsense-fast-diagnostics-for-action-scheduler' ),
);
$schedsense_age_options = array( '' => __( 'All ages', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
if ( 'pending' === $failure_data['status'] ) {
	$schedsense_age_options = array_merge(
		$schedsense_age_options,
		array(
			'under_1h'    => __( 'Under 1 hour overdue', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'one_to_6h'   => __( '1–6 hours overdue', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'six_to_24h'  => __( '6–24 hours overdue', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'one_to_7d'   => __( '1–7 days overdue', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'overdue_7d'  => __( 'Over 7 days overdue', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'overdue_1h'  => __( 'Over 1 hour overdue', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'overdue_24h' => __( 'Over 24 hours overdue', 'schedsense-fast-diagnostics-for-action-scheduler' ),
		)
	);
} elseif ( 'in-progress' === $failure_data['status'] ) {
	$schedsense_age_options['stuck'] = __( 'No update for over 1 hour', 'schedsense-fast-diagnostics-for-action-scheduler' );
}
if ( empty( $failure_data['available'] ) ) {
	$schedsense_message = 'scheduler-unavailable' === $failure_data['reason']
		? __( 'Action Scheduler is not available on this site, so its actions cannot be listed.', 'schedsense-fast-diagnostics-for-action-scheduler' )
		: __( 'The Action Scheduler query failed. No empty result is shown because the action list could not be verified.', 'schedsense-fast-diagnostics-for-action-scheduler' );
	?>
	<div class="notice notice-warning inline"><p><?php echo esc_html( $schedsense_message ); ?></p></div>
	<?php
	return;
}
?>
<section class="schedsense-panel">
	<div class="schedsense-section-heading"><div><h2><?php echo esc_html__( 'Scheduled actions', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h2><p><?php echo esc_html__( 'A bounded, server-side page. Action arguments are intentionally omitted for privacy.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></p></div></div>
	<form method="get" class="schedsense-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( SCHEDSENSE_ADMIN_PAGE_SLUG ); ?>"><input type="hidden" name="tab" value="failures">
		<?php wp_nonce_field( 'schedsense_view', '_wpnonce', false ); ?>
		<label><span><?php echo esc_html__( 'Status', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></span><select name="status"><option value="failed" <?php selected( $failure_data['status'], 'failed' ); ?>><?php echo esc_html__( 'Failed', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></option><option value="pending" <?php selected( $failure_data['status'], 'pending' ); ?>><?php echo esc_html__( 'Pending', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></option><option value="in-progress" <?php selected( $failure_data['status'], 'in-progress' ); ?>><?php echo esc_html__( 'In progress', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></option><option value="complete" <?php selected( $failure_data['status'], 'complete' ); ?>><?php echo esc_html__( 'Completed', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></option></select></label>
		<label><span><?php echo esc_html__( 'Age range', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></span><select name="age">
			<?php foreach ( $schedsense_age_options as $schedsense_age_option => $schedsense_age_label ) : ?>
				<option value="<?php echo esc_attr( $schedsense_age_option ); ?>" <?php selected( $failure_data['age'], $schedsense_age_option ); ?>><?php echo esc_html( $schedsense_age_label ); ?></option>
			<?php endforeach; ?>
		</select></label>
		<label><span><?php echo esc_html__( 'Exact hook', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></span><input type="text" name="hook" value="<?php echo esc_attr( $failure_data['hook'] ); ?>" placeholder="<?php echo esc_attr__( 'For example: woocommerce_cleanup_sessions', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?>"></label>
		<label><span><?php echo esc_html__( 'Exact group', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></span><input type="text" name="group" value="<?php echo esc_attr( $failure_data['group'] ); ?>"></label>
		<button class="button" type="submit"><?php echo esc_html__( 'Filter', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></button>
	</form>
	<p class="schedsense-results-count">
		<?php
		/* translators: 1: Number of actions on the current page, 2: Total number of matching actions. */
		echo esc_html( sprintf( __( 'Showing %1$s of %2$s matching actions.', 'schedsense-fast-diagnostics-for-action-scheduler' ), number_format_i18n( count( $failure_data['actions'] ) ), number_format_i18n( $failure_data['total'] ) ) );
		?>
	</p>
	<div class="schedsense-table-wrap"><table class="widefat striped schedsense-table"><caption class="screen-reader-text"><?php echo esc_html__( 'Action Scheduler actions', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></caption><thead><tr><th><?php echo esc_html__( 'Action', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Hook', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Source', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Group', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Scheduled', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Status', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Recurrence', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Age', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Indicator', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Next step', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th></tr></thead><tbody>
	<?php
	if ( empty( $failure_data['actions'] ) ) :
		?>
		<tr><td colspan="10"><?php echo esc_html__( 'No matching actions found.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></td></tr><?php endif; ?>
	<?php foreach ( $failure_data['actions'] as $action ) : ?>
		<?php
		$schedsense_scheduled_in_future = $action['scheduled'] > time();
		$schedsense_age_display         = ! $action['scheduled'] ? '—' : human_time_diff( $schedsense_scheduled_in_future ? time() : $action['scheduled'], $schedsense_scheduled_in_future ? $action['scheduled'] : time() );
		if ( $schedsense_scheduled_in_future && $action['scheduled'] ) {
			/* translators: %s: Time until the action is scheduled. */
			$schedsense_age_display = sprintf( __( 'In %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $schedsense_age_display );
		} elseif ( $action['scheduled'] ) {
			/* translators: %s: Time since the action was scheduled. */
			$schedsense_age_display = sprintf( __( '%s ago', 'schedsense-fast-diagnostics-for-action-scheduler' ), $schedsense_age_display );
		}
		$schedsense_next_step = 'failed' === $action['status']
			? __( 'Review callback failure', 'schedsense-fast-diagnostics-for-action-scheduler' )
			: ( 'not_due' === $action['age_bucket']
				? __( 'No action needed yet', 'schedsense-fast-diagnostics-for-action-scheduler' )
				: ( 'in-progress' === $action['status']
					? __( 'Check worker activity', 'schedsense-fast-diagnostics-for-action-scheduler' )
					: __( 'Check queue source and cron', 'schedsense-fast-diagnostics-for-action-scheduler' ) ) );
		?>
		<tr><td class="schedsense-action-cell">#<?php echo esc_html( $action['id'] ); ?>
			<?php if ( 'failed' === $action['status'] ) : ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="schedsense-delete-form" data-schedsense-confirm="<?php echo esc_attr__( 'Delete this failed action record? This cannot be undone and will not fix or retry its callback.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?>">
					<input type="hidden" name="action" value="schedsense_delete_failed_action">
					<input type="hidden" name="action_id" value="<?php echo esc_attr( $action['id'] ); ?>">
					<input type="hidden" name="hook" value="<?php echo esc_attr( $failure_data['hook'] ); ?>">
					<input type="hidden" name="group" value="<?php echo esc_attr( $failure_data['group'] ); ?>">
					<?php wp_nonce_field( 'schedsense_delete_failed_action' ); ?>
					<?php
					/* translators: %s: ID of the failed scheduled action. */
					$schedsense_delete_label = __( 'Delete failed action #%s', 'schedsense-fast-diagnostics-for-action-scheduler' );
					?>
					<button type="submit" class="schedsense-delete-button button-link-delete" aria-label="<?php echo esc_attr( sprintf( $schedsense_delete_label, $action['id'] ) ); ?>"><?php echo esc_html__( 'Delete', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></button>
				</form>
			<?php endif; ?>
		</td><td><code><?php echo esc_html( $action['hook'] ); ?></code></td><td><?php echo esc_html( $action['source']['name'] ); ?><small><?php echo esc_html( ucfirst( $action['source']['confidence'] ) ); ?></small></td><td><?php echo esc_html( ! empty( $action['group'] ) ? $action['group'] : '—' ); ?></td><td><?php echo esc_html( $action['scheduled'] ? wp_date( 'Y-m-d H:i:s', $action['scheduled'] ) : '—' ); ?></td><td><span class="schedsense-pill"><?php echo esc_html( $action['status'] ); ?></span></td><td><?php echo esc_html( $action['recurring'] ? __( 'Recurring', 'schedsense-fast-diagnostics-for-action-scheduler' ) : __( 'One-time', 'schedsense-fast-diagnostics-for-action-scheduler' ) ); ?></td><td><?php echo esc_html( $schedsense_age_display ); ?></td><td><?php echo esc_html( isset( $schedsense_age_labels[ $action['age_bucket'] ] ) ? $schedsense_age_labels[ $action['age_bucket'] ] : __( 'Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ) ); ?></td><td><?php echo esc_html( $schedsense_next_step ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table></div>
	<?php
	$schedsense_total_pages = (int) ceil( $failure_data['total'] / $failure_data['per_page'] );
	if ( $schedsense_total_pages > 1 ) {
		echo wp_kses_post(
			paginate_links(
				array(
					'base'    => wp_nonce_url( add_query_arg( 'paged', '%#%' ), 'schedsense_view' ),
					'format'  => '',
					'current' => $failure_data['page'],
					'total'   => $schedsense_total_pages,
					'type'    => 'list',
				)
			)
		);
	}
	?>
</section>
