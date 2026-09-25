<?php
/**
 * Testable rules-based diagnosis engine.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Diagnosis;

defined( 'ABSPATH' ) || exit;

final class DiagnosisEngine {
	/**
	 * Evaluate highest-signal rules first.
	 *
	 * @param array $snapshot Diagnostic snapshot.
	 * @return array
	 */
	public function diagnose( array $snapshot ) {
		if ( empty( $snapshot['queue']['available'] ) ) {
			return $this->result(
				'informational',
				__( 'Action Scheduler Not Detected', 'queue-health-monitor' ),
				__( 'Action Scheduler diagnostics will become available after a compatible provider initializes it.', 'queue-health-monitor' ),
				__( 'Confirmed', 'queue-health-monitor' )
			);
		}

		if ( empty( $snapshot['queue']['data_available'] ) ) {
			return $this->result(
				'informational',
				__( 'Queue Data Unavailable', 'queue-health-monitor' ),
				__( 'Action Scheduler is present, but the plugin could not verify queue data. Counts are withheld so a query failure is not mistaken for an empty or healthy queue.', 'queue-health-monitor' ),
				__( 'Confirmed', 'queue-health-monitor' )
			);
		}

		$counts   = $snapshot['queue']['counts'];
		$loopback = $this->check_by_id( $snapshot['checks'], 'loopback' );
		$disabled = ! empty( $snapshot['environment']['disable_wp_cron'] );

		if ( $counts['overdue_24h'] > 0 && $loopback && in_array( $loopback['status'], array( 'warning', 'critical' ), true ) ) {
			/* translators: %d: Number of actions overdue by at least 24 hours. */
			$message = sprintf( __( '%d actions are more than 24 hours overdue and the local cron loopback test failed. The loopback problem is likely contributing to queue delay.', 'queue-health-monitor' ), $counts['overdue_24h'] );
			return $this->result( 'critical', __( 'Attention Needed', 'queue-health-monitor' ), $message, __( 'Likely', 'queue-health-monitor' ) );
		}

		if ( $disabled && $counts['overdue_24h'] > 0 ) {
			return $this->result( 'critical', __( 'Attention Needed', 'queue-health-monitor' ), __( 'Traffic-triggered WP-Cron is disabled and the queue contains actions overdue by more than 24 hours. Verify that a server-level cron is configured and running.', 'queue-health-monitor' ), __( 'Likely', 'queue-health-monitor' ) );
		}

		if ( $counts['stuck_in_progress'] > 0 ) {
			$count  = $counts['stuck_in_progress'];
			$status = $count >= 5 ? 'critical' : 'warning';
			/* translators: %d: Number of in-progress actions without an update for over an hour. */
			$message = sprintf( _n( '%d in-progress action has gone over an hour without an update. Check the worker and callback before treating it as stuck.', '%d in-progress actions have gone over an hour without an update. Check the worker and callbacks before treating them as stuck.', $count, 'queue-health-monitor' ), $count );
			return $this->result( $status, __( 'Attention Needed', 'queue-health-monitor' ), $message, __( 'Possible', 'queue-health-monitor' ) );
		}

		$concentration = $this->failure_concentration( $snapshot['queue']['sources'] );
		if ( $counts['failed'] >= 5 && $concentration >= 0.8 && 0 === $counts['overdue_24h'] ) {
			return $this->result( 'warning', __( 'Attention Needed', 'queue-health-monitor' ), __( 'Most sampled failures come from one source while the pending queue remains current. The scheduler appears operational; the owning callback is the more likely fault domain.', 'queue-health-monitor' ), __( 'Likely', 'queue-health-monitor' ) );
		}

		if ( $counts['failed'] > 0 && 0 === $counts['overdue_1h'] ) {
			/* translators: %1$d: Number of failed actions. */
			$message = sprintf(
				_n(
					'%1$d failed action is recorded, but no pending action is more than one hour overdue. Review its action log and source plugin callback. Deleting the failed entry does not repair or retry its callback.',
					'%1$d failed actions are recorded, but no pending actions are more than one hour overdue. Review their action logs and source plugin callbacks. Deleting failed entries does not repair or retry their callbacks.',
					$counts['failed'],
					'queue-health-monitor'
				),
				$counts['failed']
			);
			return $this->result( 'warning', __( 'Attention Needed', 'queue-health-monitor' ), $message, __( 'Possible', 'queue-health-monitor' ) );
		}

		if ( 0 === $counts['overdue_24h'] && $counts['failed'] < 5 ) {
			return $this->result( 'healthy', __( 'Healthy', 'queue-health-monitor' ), __( 'No significant scheduler problem was detected. A few recent or slightly delayed actions can be normal on traffic-triggered cron.', 'queue-health-monitor' ), __( 'Informational', 'queue-health-monitor' ) );
		}

		return $this->result( 'warning', __( 'Attention Needed', 'queue-health-monitor' ), __( 'The queue has warning signals, but the available evidence does not isolate one cause. Compare overdue age, failed hooks, and environment checks before making changes.', 'queue-health-monitor' ), __( 'Possible', 'queue-health-monitor' ) );
	}

	/** @return array|null */
	private function check_by_id( array $checks, $id ) {
		foreach ( $checks as $check ) {
			if ( isset( $check['id'] ) && $id === $check['id'] ) {
				return $check;
			}
		}
		return null;
	}

	/** @return float */
	private function failure_concentration( array $sources ) {
		$total = 0;
		$max   = 0;
		foreach ( $sources as $source ) {
			$count  = isset( $source['failed'] ) ? absint( $source['failed'] ) : 0;
			$total += $count;
			$max    = max( $max, $count );
		}
		return $total > 0 ? $max / $total : 0.0;
	}

	/** @return array */
	private function result( $status, $label, $message, $confidence ) {
		return array(
			'status'     => sanitize_key( $status ),
			'label'      => (string) $label,
			'message'    => (string) $message,
			'confidence' => (string) $confidence,
		);
	}
}
