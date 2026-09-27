<?php
/** @package SchedSense */
defined( 'ABSPATH' ) || exit;
$queue_health_monitor_counts = isset( $snapshot['queue']['counts'] ) ? $snapshot['queue']['counts'] : array();
?>
<section class="qhm-health qhm-health--<?php echo esc_attr( $diagnosis['status'] ); ?>" aria-labelledby="qhm-health-title">
	<div>
		<span class="qhm-kicker"><?php echo esc_html__( 'Scheduler health', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></span>
		<h2 id="qhm-health-title"><?php echo esc_html( $diagnosis['label'] ); ?></h2>
		<p><?php echo esc_html( $diagnosis['message'] ); ?></p>
	</div>
	<span class="qhm-confidence"><?php echo esc_html( $diagnosis['confidence'] ); ?></span>
</section>

<?php if ( empty( $snapshot['queue']['available'] ) ) : ?>
	<div class="qhm-empty"><span class="dashicons dashicons-clock" aria-hidden="true"></span><h2><?php echo esc_html__( 'Action Scheduler is not available', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h2><p><?php echo esc_html__( "SchedSense's Action Scheduler diagnostics will become available when a compatible plugin loads it.", 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></p></div>
<?php elseif ( empty( $snapshot['queue']['data_available'] ) ) : ?>
	<div class="qhm-empty"><span class="dashicons dashicons-warning" aria-hidden="true"></span><h2><?php echo esc_html__( 'Queue data could not be read', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h2><p><?php echo esc_html__( 'Action Scheduler is initialized, but a queue read failed. Counts are withheld instead of being shown as zero.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></p></div>
<?php else : ?>
	<section aria-labelledby="qhm-queue-title">
		<h2 id="qhm-queue-title"><?php echo esc_html__( 'Queue', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h2>
		<div class="qhm-metrics">
			<?php
			$queue_health_monitor_metrics = array(
				'pending'     => __( 'Pending', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'overdue_1h'  => __( 'Past due > 1 hour', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'overdue_24h' => __( 'Past due > 24 hours', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'failed'      => __( 'Failed', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'in_progress' => __( 'In progress', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'stuck_in_progress' => __( 'In progress > 1 hour', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				'complete'    => __( 'Completed', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			);
			$queue_health_monitor_metric_filters = array(
				'pending'           => array( 'status' => 'pending' ),
				'overdue_1h'        => array( 'status' => 'pending', 'age' => 'overdue_1h' ),
				'overdue_24h'       => array( 'status' => 'pending', 'age' => 'overdue_24h' ),
				'failed'            => array( 'status' => 'failed' ),
				'in_progress'       => array( 'status' => 'in-progress' ),
				'stuck_in_progress' => array( 'status' => 'in-progress', 'age' => 'stuck' ),
				'complete'          => array( 'status' => 'complete' ),
			);
			foreach ( $queue_health_monitor_metrics as $queue_health_monitor_key => $queue_health_monitor_label ) :
				$queue_health_monitor_metric_url = add_query_arg(
					array_merge(
						array(
							'page' => 'schedsense',
							'tab'  => 'failures',
						),
						$queue_health_monitor_metric_filters[ $queue_health_monitor_key ]
					),
					admin_url( 'tools.php' )
				);
				?>
				<a class="qhm-metric" href="<?php echo esc_url( $queue_health_monitor_metric_url ); ?>"><span><?php echo esc_html( $queue_health_monitor_label ); ?></span><strong><?php echo esc_html( number_format_i18n( isset( $queue_health_monitor_counts[ $queue_health_monitor_key ] ) ? $queue_health_monitor_counts[ $queue_health_monitor_key ] : 0 ) ); ?></strong></a>
			<?php endforeach; ?>
		</div>
		<div class="qhm-overdue-buckets" aria-label="<?php echo esc_attr__( 'Overdue age breakdown', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?>">
			<strong><?php echo esc_html__( 'Due-action age:', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></strong>
			<?php
			$queue_health_monitor_age_buckets = array(
				'under_1h'   => array( 'count' => 'under_1h', 'label' => __( 'Under 1 hour', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
				'one_to_6h'  => array( 'count' => 'one_to_6h', 'label' => __( '1–6 hours', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
				'six_to_24h' => array( 'count' => 'six_to_24h', 'label' => __( '6–24 hours', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
				'one_to_7d'  => array( 'count' => 'one_to_7d', 'label' => __( '1–7 days', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
				'overdue_7d' => array( 'count' => 'overdue_7d', 'label' => __( 'Over 7 days', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			);
			foreach ( $queue_health_monitor_age_buckets as $queue_health_monitor_age => $queue_health_monitor_bucket ) :
				$queue_health_monitor_age_url = add_query_arg(
					array(
						'page'   => 'schedsense',
						'tab'    => 'failures',
						'status' => 'pending',
						'age'    => $queue_health_monitor_age,
					),
					admin_url( 'tools.php' )
				);
				?>
				<a href="<?php echo esc_url( $queue_health_monitor_age_url ); ?>"><?php echo esc_html( $queue_health_monitor_bucket['label'] ); ?>: <?php echo esc_html( number_format_i18n( $queue_health_monitor_counts[ $queue_health_monitor_bucket['count'] ] ) ); ?></a>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<div class="qhm-grid qhm-grid--two">
	<section class="qhm-panel" aria-labelledby="qhm-checks-title">
		<h2 id="qhm-checks-title"><?php echo esc_html__( 'Environment checks', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h2>
		<ul class="qhm-checks">
			<?php foreach ( $snapshot['checks'] as $queue_health_monitor_check ) : ?>
				<li><span class="qhm-status qhm-status--<?php echo esc_attr( $queue_health_monitor_check['status'] ); ?>" aria-hidden="true"></span><div><strong><?php echo esc_html( $queue_health_monitor_check['title'] ); ?></strong><p><?php echo esc_html( $queue_health_monitor_check['summary'] ); ?></p>
				<?php
				if ( $queue_health_monitor_check['evidence'] ) :
					?>
					<small><?php echo esc_html( $queue_health_monitor_check['evidence'] ); ?></small><?php endif; ?></div></li>
			<?php endforeach; ?>
		</ul>
	</section>
	<section class="qhm-panel" aria-labelledby="qhm-environment-title">
		<h2 id="qhm-environment-title"><?php echo esc_html__( 'Environment', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h2>
		<dl class="qhm-definition">
			<dt><?php echo esc_html__( 'WordPress', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></dt><dd><?php echo esc_html( $snapshot['environment']['wordpress_version'] ); ?></dd>
			<dt><?php echo esc_html__( 'PHP', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></dt><dd><?php echo esc_html( $snapshot['environment']['php_version'] ); ?></dd>
			<dt><?php echo esc_html__( 'Action Scheduler', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></dt><dd><?php echo esc_html( ! empty( $snapshot['action_scheduler_version'] ) ? $snapshot['action_scheduler_version'] : __( 'Not detected', 'schedsense-fast-diagnostics-for-action-scheduler' ) ); ?></dd>
			<dt><?php echo esc_html__( 'WooCommerce', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></dt><dd><?php echo esc_html( ! empty( $snapshot['environment']['woocommerce'] ) ? $snapshot['environment']['woocommerce'] : __( 'Not detected', 'schedsense-fast-diagnostics-for-action-scheduler' ) ); ?></dd>
			<dt><?php echo esc_html__( 'Memory limit', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></dt><dd><?php echo esc_html( $snapshot['environment']['memory_limit'] ); ?></dd>
			<dt><?php echo esc_html__( 'Database', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></dt><dd><?php echo esc_html( $snapshot['environment']['database_version'] ); ?></dd>
		</dl>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qhm-safe-action">
			<input type="hidden" name="action" value="qhm_spawn_cron">
			<?php wp_nonce_field( 'qhm_spawn_cron' ); ?>
			<button type="submit" class="button"><?php echo esc_html__( 'Request one WP-Cron spawn', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></button>
			<p class="description"><?php echo esc_html__( "Requests WordPress's standard cron spawner. Due callbacks may run.", 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></p>
		</form>
	</section>
</div>
