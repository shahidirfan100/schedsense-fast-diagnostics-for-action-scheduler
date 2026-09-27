<?php
/**
 * Testable rules-based diagnosis engine.
 *
 * @package SchedSense
 */

namespace SchedSense\Diagnosis;

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
				__( 'Action Scheduler Not Detected', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Action Scheduler diagnostics will become available after a compatible provider initializes it.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Confirmed', 'schedsense-fast-diagnostics-for-action-scheduler' )
			);
		}

		if ( empty( $snapshot['queue']['data_available'] ) ) {
			return $this->result(
				'informational',
				__( 'Queue Data Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Action Scheduler is present, but the plugin could not verify queue data. Counts are withheld so a query failure is not mistaken for an empty or healthy queue.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				__( 'Confirmed', 'schedsense-fast-diagnostics-for-action-scheduler' )
			);
		}

		$counts   = $snapshot['queue']['counts'];
		$loopback = $this->check_by_id( $snapshot['checks'], 'loopback' );
		$disabled = ! empty( $snapshot['environment']['disable_wp_cron'] );

		if ( $counts['overdue_24h'] > 0 && $loopback && in_array( $loopback['status'], array( 'warning', 'critical' ), true ) ) {
			/* translators: %d: Number of actions overdue by at least 24 hours. */
			$message = sprintf( __( '%d actions are more than 24 hours overdue and the local cron loopback test failed. The loopback problem is likely contributing to queue delay.', 'schedsense-fast-diagnostics-for-action-scheduler' ), $counts['overdue_24h'] );
			return $this->result( 'critical', __( 'Attention Needed', 'schedsense-fast-diagnostics-for-action-scheduler' ), $message, __( 'Likely', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		if ( $disabled && $counts['overdue_24h'] > 0 ) {
			return $this->result( 'critical', __( 'Attention Needed', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Traffic-triggered WP-Cron is disabled and the queue contains actions overdue by more than 24 hours. Verify that a server-level cron is configured and running.', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Likely', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		if ( $counts['stuck_in_progress'] > 0 ) {
			$count  = $counts['stuck_in_progress'];
			$status = $count >= 5 ? 'critical' : 'warning';
			/* translators: %d: Number of in-progress actions without an update for over an hour. */
			$message = sprintf( _n( '%d in-progress action has gone over an hour without an update. Check the worker and callback before treating it as stuck.', '%d in-progress actions have gone over an hour without an update. Check the worker and callbacks before treating them as stuck.', $count, 'schedsense-fast-diagnostics-for-action-scheduler' ), $count );
			return $this->result( $status, __( 'Attention Needed', 'schedsense-fast-diagnostics-for-action-scheduler' ), $message, __( 'Possible', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		$concentration = $this->failure_concentration( $snapshot['queue']['sources'] );
		if ( $counts['failed'] >= 5 && $concentration >= 0.8 && 0 === $counts['overdue_24h'] ) {
			return $this->result( 'warning', __( 'Attention Needed', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Most sampled failures come from one source while the pending queue remains current. The scheduler appears operational; the owning callback is the more likely fault domain.', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Likely', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		if ( $counts['failed'] > 0 && 0 === $counts['overdue_1h'] ) {
			/* translators: %1$d: Number of failed actions. */
			$message_template = _n(
				'%1$d failed action is recorded, but no pending action is more than one hour overdue. Review its action log and source plugin callback. Deleting the failed entry does not repair or retry its callback.',
				'%1$d failed actions are recorded, but no pending actions are more than one hour overdue. Review their action logs and source plugin callbacks. Deleting failed entries does not repair or retry their callbacks.',
				$counts['failed'],
				'schedsense-fast-diagnostics-for-action-scheduler'
			);
			$message          = sprintf( $message_template, $counts['failed'] );
			return $this->result( 'warning', __( 'Attention Needed', 'schedsense-fast-diagnostics-for-action-scheduler' ), $message, __( 'Possible', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		if ( 0 === $counts['overdue_24h'] && $counts['failed'] < 5 ) {
			return $this->result( 'healthy', __( 'Healthy', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'No significant scheduler problem was detected. A few recent or slightly delayed actions can be normal on traffic-triggered cron.', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Informational', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
		}

		return $this->result( 'warning', __( 'Attention Needed', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'The queue has warning signals, but the available evidence does not isolate one cause. Compare overdue age, failed hooks, and environment checks before making changes.', 'schedsense-fast-diagnostics-for-action-scheduler' ), __( 'Possible', 'schedsense-fast-diagnostics-for-action-scheduler' ) );
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
