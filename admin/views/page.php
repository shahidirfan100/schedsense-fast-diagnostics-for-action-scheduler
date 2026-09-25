<?php
/**
 * Main Queue Health Monitor admin page.
 *
 * @var string $tab Current tab.
 * @var array  $snapshot Diagnostic snapshot.
 * @var array  $diagnosis Diagnosis result.
 * @var string $notice Notice key.
 *
 * @package QueueHealthMonitor
 */

defined( 'ABSPATH' ) || exit;

$tabs = array(
	'overview' => __( 'Overview', 'queue-health-monitor' ),
	'failures' => __( 'Actions', 'queue-health-monitor' ),
	'sources'  => __( 'Sources', 'queue-health-monitor' ),
	'report'   => __( 'Report', 'queue-health-monitor' ),
);
?>
<div class="wrap qhm-wrap">
	<header class="qhm-header">
		<img class="qhm-brand" src="<?php echo esc_url( QUEUE_HEALTH_MONITOR_PLUGIN_URL . 'admin/images/queue-health-monitor-mark.png' ); ?>" alt="" aria-hidden="true">
		<div>
			<h1><?php echo esc_html__( 'Queue Health Monitor', 'queue-health-monitor' ); ?></h1>
			<p><?php echo esc_html__( 'Action Scheduler diagnostics with evidence, not guesswork.', 'queue-health-monitor' ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qhm-header__action">
			<input type="hidden" name="action" value="qhm_refresh">
			<?php wp_nonce_field( 'qhm_refresh_diagnostics' ); ?>
			<button type="submit" class="button button-primary"><span class="dashicons dashicons-update" aria-hidden="true"></span><?php echo esc_html__( 'Refresh diagnostics', 'queue-health-monitor' ); ?></button>
		</form>
	</header>

	<?php if ( $notice ) : ?>
		<?php require QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'admin/views/notice.php'; ?>
	<?php endif; ?>

	<nav class="nav-tab-wrapper" aria-label="<?php echo esc_attr__( 'Queue Health Monitor sections', 'queue-health-monitor' ); ?>">
		<?php foreach ( $tabs as $queue_health_monitor_tab_key => $queue_health_monitor_tab_label ) : ?>
			<a class="nav-tab <?php echo esc_attr( $tab === $queue_health_monitor_tab_key ? 'nav-tab-active' : '' ); ?>" href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page' => 'qhm',
						'tab'  => $queue_health_monitor_tab_key,
					),
					admin_url( 'tools.php' )
				)
			);
			?>
								"><?php echo esc_html( $queue_health_monitor_tab_label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<main class="qhm-main">
		<?php
		if ( 'failures' === $tab ) {
			require QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'admin/views/failures.php';
		} elseif ( 'sources' === $tab ) {
			require QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'admin/views/sources.php';
		} elseif ( 'report' === $tab ) {
			require QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'admin/views/report.php';
		} else {
			require QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'admin/views/overview.php';
		}
		?>
	</main>
	<footer class="qhm-footer">
		<?php
		/* translators: %s: Date and time when the diagnostic snapshot was generated. */
		echo esc_html( sprintf( __( 'Snapshot generated %s. Queue source totals are a bounded sample of up to 300 relevant actions.', 'queue-health-monitor' ), wp_date( 'Y-m-d H:i:s T', $snapshot['generated_at'] ) ) );
		?>
	</footer>
</div>
