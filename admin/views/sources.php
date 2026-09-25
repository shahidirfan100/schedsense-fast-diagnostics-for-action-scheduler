<?php
/** @package QueueHealthMonitor */
defined( 'ABSPATH' ) || exit;
?>
<section class="qhm-panel"><h2><?php echo esc_html__( 'Top queue producers', 'queue-health-monitor' ); ?></h2><p><?php echo esc_html__( 'Counts are from bounded samples of up to 100 failed, 100 overdue pending, and 100 potentially stuck in-progress actions. They are sampled counts, not full-table totals.', 'queue-health-monitor' ); ?></p><div class="qhm-table-wrap"><table class="widefat striped qhm-table"><caption class="screen-reader-text"><?php echo esc_html__( 'Sampled problem queue sources', 'queue-health-monitor' ); ?></caption><thead><tr><th><?php echo esc_html__( 'Source', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Overdue pending sampled', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Failed sampled', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Potentially stuck sampled', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Confidence', 'queue-health-monitor' ); ?></th><th><?php echo esc_html__( 'Top hook', 'queue-health-monitor' ); ?></th></tr></thead><tbody>
	<?php
	if ( empty( $snapshot['queue']['data_available'] ) ) :
		?>
		<tr><td colspan="6"><?php echo esc_html__( 'Queue data could not be read, so source samples are unavailable.', 'queue-health-monitor' ); ?></td></tr>
	<?php elseif ( empty( $snapshot['queue']['sources'] ) ) : ?>
		<tr><td colspan="6"><?php echo esc_html__( 'No failed, overdue, or potentially stuck actions were found in the bounded sample.', 'queue-health-monitor' ); ?></td></tr>
	<?php endif; ?>
	<?php
	foreach ( $snapshot['queue']['sources'] as $queue_health_monitor_source ) :
		arsort( $queue_health_monitor_source['hooks'] );
		$queue_health_monitor_top_hook = key( $queue_health_monitor_source['hooks'] );
		?>
		<tr><td><strong><?php echo esc_html( $queue_health_monitor_source['name'] ); ?></strong></td><td><?php echo esc_html( $queue_health_monitor_source['past_due'] ); ?></td><td><?php echo esc_html( $queue_health_monitor_source['failed'] ); ?></td><td><?php echo esc_html( $queue_health_monitor_source['stuck'] ); ?></td><td><?php echo esc_html( ucfirst( $queue_health_monitor_source['confidence'] ) ); ?></td><td><code><?php echo esc_html( ! empty( $queue_health_monitor_top_hook ) ? $queue_health_monitor_top_hook : '—' ); ?></code></td></tr><?php endforeach; ?>
	</tbody></table></div></section>
