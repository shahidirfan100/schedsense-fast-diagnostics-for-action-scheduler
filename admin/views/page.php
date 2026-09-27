<?php
/**
 * Main SchedSense admin page.
 *
 * @var string $tab Current tab.
 * @var array  $snapshot Diagnostic snapshot.
 * @var array  $diagnosis Diagnosis result.
 * @var string $notice Notice key.
 *
 * @package SchedSense
 */

defined( 'ABSPATH' ) || exit;

$tabs = array(
	'overview' => __( 'Overview', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'failures' => __( 'Actions', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'sources'  => __( 'Sources', 'schedsense-fast-diagnostics-for-action-scheduler' ),
	'report'   => __( 'Report', 'schedsense-fast-diagnostics-for-action-scheduler' ),
);
?>
<div class="wrap qhm-wrap">
	<header class="qhm-header">
		<img class="qhm-brand" src="<?php echo esc_url( QUEUE_HEALTH_MONITOR_PLUGIN_URL . 'admin/images/schedsense-mark.png' ); ?>" alt="" aria-hidden="true">
		<div>
			<h1><?php echo esc_html__( 'SchedSense', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h1>
			<p><?php echo esc_html__( 'Fast diagnostics for Action Scheduler.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qhm-header__action">
			<input type="hidden" name="action" value="qhm_refresh">
			<?php wp_nonce_field( 'qhm_refresh_diagnostics' ); ?>
			<button type="submit" class="button button-primary"><span class="dashicons dashicons-update" aria-hidden="true"></span><?php echo esc_html__( 'Refresh diagnostics', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></button>
		</form>
	</header>

	<?php if ( $notice ) : ?>
		<?php require QUEUE_HEALTH_MONITOR_PLUGIN_DIR . 'admin/views/notice.php'; ?>
	<?php endif; ?>

	<nav class="nav-tab-wrapper" aria-label="<?php echo esc_attr__( 'SchedSense sections', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?>">
		<?php foreach ( $tabs as $queue_health_monitor_tab_key => $queue_health_monitor_tab_label ) : ?>
			<a class="nav-tab <?php echo esc_attr( $tab === $queue_health_monitor_tab_key ? 'nav-tab-active' : '' ); ?>" href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page' => 'schedsense',
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
		echo esc_html( sprintf( __( 'Snapshot generated %s. Queue source totals are a bounded sample of up to 300 relevant actions.', 'schedsense-fast-diagnostics-for-action-scheduler' ), wp_date( 'Y-m-d H:i:s T', $snapshot['generated_at'] ) ) );
		?>
	</footer>
</div>
