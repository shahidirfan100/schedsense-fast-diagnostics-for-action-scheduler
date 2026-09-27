<?php
/**
 * Plaintext diagnostic report builder.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Reports;

defined( 'ABSPATH' ) || exit;

final class ReportBuilder {
	/** @var Redactor */
	private $redactor;

	/** Constructor. */
	public function __construct() {
		$this->redactor = new Redactor();
	}

	/**
	 * Build a non-sensitive report.
	 *
	 * @param array $snapshot Diagnostic snapshot.
	 * @param array $diagnosis Diagnosis result.
	 * @return string
	 */
	public function build( array $snapshot, array $diagnosis ) {
		$environment = $snapshot['environment'];
		$queue       = $snapshot['queue'];
		$counts      = isset( $queue['counts'] ) ? $queue['counts'] : array();
		$lines       = array(
			__( 'SCHEDSENSE DIAGNOSTIC REPORT', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			'',
			sprintf(
				/* translators: %s: Report generation date and time. */
				__( 'Generated: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ),
				wp_date( 'Y-m-d H:i:s T', $snapshot['generated_at'] )
			),
			'',
			__( 'WORDPRESS', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			/* translators: %s: WordPress version. */
			sprintf( __( 'Version: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['wordpress_version'] ),
			/* translators: %s: PHP version. */
			sprintf( __( 'PHP: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['php_version'] ),
			/* translators: %s: Database version. */
			sprintf( __( 'Database: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['database_version'] ),
			/* translators: %s: Site timezone. */
			sprintf( __( 'Timezone: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['timezone'] ),
			/* translators: %s: Whether WordPress Multisite is enabled. */
			sprintf( __( 'Multisite: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['multisite'] ? __( 'Yes (current site only)', 'schedsense-fast-diagnostics-for-action-scheduler' ) : __( 'No', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			/* translators: %s: WooCommerce version or not-detected label. */
				sprintf( __( 'WooCommerce: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), ! empty( $environment['woocommerce'] ) ? $environment['woocommerce'] : __( 'Not detected', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			'',
			__( 'ACTION SCHEDULER', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			/* translators: %s: Action Scheduler version or not-detected label. */
				sprintf( __( 'Version: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), ! empty( $snapshot['action_scheduler_version'] ) ? $snapshot['action_scheduler_version'] : __( 'Not detected', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			/* translators: %s: Whether queue data was successfully read. */
			sprintf( __( 'Queue data: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), ! empty( $queue['data_available'] ) ? __( 'Available', 'schedsense-fast-diagnostics-for-action-scheduler' ) : __( 'Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			/* translators: %s: Pending action count or unavailable label. */
			sprintf( __( 'Pending: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), isset( $counts['pending'] ) ? $counts['pending'] : __( 'Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			/* translators: %s: Failed action count or unavailable label. */
			sprintf( __( 'Failed: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), isset( $counts['failed'] ) ? $counts['failed'] : __( 'Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			/* translators: %s: One-hour overdue action count or unavailable label. */
			sprintf( __( 'Past Due >1h: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), isset( $counts['overdue_1h'] ) ? $counts['overdue_1h'] : __( 'Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			/* translators: %s: Twenty-four-hour overdue action count or unavailable label. */
			sprintf( __( 'Past Due >24h: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), isset( $counts['overdue_24h'] ) ? $counts['overdue_24h'] : __( 'Unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ) ),
			'',
			__( 'WP-CRON', 'schedsense-fast-diagnostics-for-action-scheduler' ),
			/* translators: %s: Boolean DISABLE_WP_CRON state. */
			sprintf( __( 'DISABLE_WP_CRON: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['disable_wp_cron'] ? 'true' : 'false' ),
			/* translators: %s: Boolean ALTERNATE_WP_CRON state. */
			sprintf( __( 'ALTERNATE_WP_CRON: %s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $environment['alternate_wp_cron'] ? 'true' : 'false' ),
			'',
			__( 'CHECKS', 'schedsense-fast-diagnostics-for-action-scheduler' ),
		);

		foreach ( $snapshot['checks'] as $check ) {
			$lines[] = sprintf( '%s: %s — %s', $check['title'], strtoupper( $check['status'] ), $check['summary'] );
		}

		$lines[] = '';
		$lines[] = __( 'TOP PROBLEM SOURCES (BOUNDED SAMPLE)', 'schedsense-fast-diagnostics-for-action-scheduler' );
		if ( empty( $queue['data_available'] ) ) {
			$lines[] = __( 'Unavailable because the Action Scheduler queue could not be read.', 'schedsense-fast-diagnostics-for-action-scheduler' );
		} elseif ( empty( $queue['sources'] ) ) {
			$lines[] = __( 'No failed, overdue, or potentially stuck actions were found in the bounded sample.', 'schedsense-fast-diagnostics-for-action-scheduler' );
		}
		foreach ( array_slice( $queue['sources'], 0, 10 ) as $source ) {
			/* translators: 1: Source name, 2: Failed count, 3: Past-due count, 4: Potentially stuck count, 5: Confidence. */
			$lines[] = sprintf( __( '%1$s: %2$d failed, %3$d past due, %4$d potentially stuck (%5$s confidence)', 'schedsense-fast-diagnostics-for-action-scheduler' ), $source['name'], $source['failed'], $source['past_due'], $source['stuck'], $source['confidence'] );
		}
		$lines[] = '';
		$lines[] = __( 'LIKELY DIAGNOSIS', 'schedsense-fast-diagnostics-for-action-scheduler' );
		/* translators: 1: Diagnostic confidence, 2: Diagnosis message. */
		$lines[] = sprintf( __( '%1$s: %2$s', 'schedsense-fast-diagnostics-for-action-scheduler' ), $diagnosis['confidence'], $diagnosis['message'] );
		$lines[] = '';
		$lines[] = __( 'PRIVACY', 'schedsense-fast-diagnostics-for-action-scheduler' );
		$lines[] = __( 'No action arguments, credentials, cookies, authorization data, customer records, or full server paths are included.', 'schedsense-fast-diagnostics-for-action-scheduler' );

		return $this->redactor->redact( implode( "\n", $lines ) );
	}
}
