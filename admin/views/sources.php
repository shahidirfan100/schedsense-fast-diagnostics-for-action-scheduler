<?php
/** @package SchedSense */
defined( 'ABSPATH' ) || exit;
?>
<section class="qhm-panel"><h2><?php echo esc_html__( 'Top queue producers', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></h2><p><?php echo esc_html__( 'Counts are from bounded samples of up to 100 failed, 100 overdue pending, and 100 potentially stuck in-progress actions. They are sampled counts, not full-table totals.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></p><div class="qhm-table-wrap"><table class="widefat striped qhm-table"><caption class="screen-reader-text"><?php echo esc_html__( 'Sampled problem queue sources', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></caption><thead><tr><th><?php echo esc_html__( 'Source', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Overdue pending sampled', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Failed sampled', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Potentially stuck sampled', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Confidence', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th><th><?php echo esc_html__( 'Top hook', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></th></tr></thead><tbody>
	<?php
	if ( empty( $snapshot['queue']['data_available'] ) ) :
		?>
		<tr><td colspan="6"><?php echo esc_html__( 'Queue data could not be read, so source samples are unavailable.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></td></tr>
	<?php elseif ( empty( $snapshot['queue']['sources'] ) ) : ?>
		<tr><td colspan="6"><?php echo esc_html__( 'No failed, overdue, or potentially stuck actions were found in the bounded sample.', 'schedsense-fast-diagnostics-for-action-scheduler' ); ?></td></tr>
	<?php endif; ?>
	<?php
	foreach ( $snapshot['queue']['sources'] as $queue_health_monitor_source ) :
		arsort( $queue_health_monitor_source['hooks'] );
		$queue_health_monitor_top_hook = key( $queue_health_monitor_source['hooks'] );
		?>
		<tr><td><strong><?php echo esc_html( $queue_health_monitor_source['name'] ); ?></strong></td><td><?php echo esc_html( $queue_health_monitor_source['past_due'] ); ?></td><td><?php echo esc_html( $queue_health_monitor_source['failed'] ); ?></td><td><?php echo esc_html( $queue_health_monitor_source['stuck'] ); ?></td><td><?php echo esc_html( ucfirst( $queue_health_monitor_source['confidence'] ) ); ?></td><td><code><?php echo esc_html( ! empty( $queue_health_monitor_top_hook ) ? $queue_health_monitor_top_hook : '—' ); ?></code></td></tr><?php endforeach; ?>
	</tbody></table></div></section>
