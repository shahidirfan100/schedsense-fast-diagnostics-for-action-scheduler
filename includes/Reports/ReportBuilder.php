<?php
/**
 * Plaintext diagnostic report builder.
 *
 * @package QueueHealthMonitor
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
			__( 'QUEUE HEALTH MONITOR DIAGNOSTIC REPORT', 'queue-health-monitor' ),
			'',
			sprintf(
				/* translators: %s: Report generation date and time. */
				__( 'Generated: %s', 'queue-health-monitor' ),
				wp_date( 'Y-m-d H:i:s T', $snapshot['generated_at'] )
			),
			'',
			__( 'WORDPRESS', 'queue-health-monitor' ),
			/* translators: %s: WordPress version. */
			sprintf( __( 'Version: %s', 'queue-health-monitor' ), $environment['wordpress_version'] ),
			/* translators: %s: PHP version. */
			sprintf( __( 'PHP: %s', 'queue-health-monitor' ), $environment['php_version'] ),
			/* translators: %s: Database version. */
			sprintf( __( 'Database: %s', 'queue-health-monitor' ), $environment['database_version'] ),
			/* translators: %s: Site timezone. */
			sprintf( __( 'Timezone: %s', 'queue-health-monitor' ), $environment['timezone'] ),
			/* translators: %s: Whether WordPress Multisite is enabled. */
			sprintf( __( 'Multisite: %s', 'queue-health-monitor' ), $environment['multisite'] ? __( 'Yes (current site only)', 'queue-health-monitor' ) : __( 'No', 'queue-health-monitor' ) ),
			/* translators: %s: WooCommerce version or not-detected label. */
				sprintf( __( 'WooCommerce: %s', 'queue-health-monitor' ), ! empty( $environment['woocommerce'] ) ? $environment['woocommerce'] : __( 'Not detected', 'queue-health-monitor' ) ),
			'',
			__( 'ACTION SCHEDULER', 'queue-health-monitor' ),
			/* translators: %s: Action Scheduler version or not-detected label. */
				sprintf( __( 'Version: %s', 'queue-health-monitor' ), ! empty( $snapshot['action_scheduler_version'] ) ? $snapshot['action_scheduler_version'] : __( 'Not detected', 'queue-health-monitor' ) ),
			/* translators: %s: Whether queue data was successfully read. */
			sprintf( __( 'Queue data: %s', 'queue-health-monitor' ), ! empty( $queue['data_available'] ) ? __( 'Available', 'queue-health-monitor' ) : __( 'Unavailable', 'queue-health-monitor' ) ),
			/* translators: %s: Pending action count or unavailable label. */
			sprintf( __( 'Pending: %s', 'queue-health-monitor' ), isset( $counts['pending'] ) ? $counts['pending'] : __( 'Unavailable', 'queue-health-monitor' ) ),
			/* translators: %s: Failed action count or unavailable label. */
			sprintf( __( 'Failed: %s', 'queue-health-monitor' ), isset( $counts['failed'] ) ? $counts['failed'] : __( 'Unavailable', 'queue-health-monitor' ) ),
			/* translators: %s: One-hour overdue action count or unavailable label. */
			sprintf( __( 'Past Due >1h: %s', 'queue-health-monitor' ), isset( $counts['overdue_1h'] ) ? $counts['overdue_1h'] : __( 'Unavailable', 'queue-health-monitor' ) ),
			/* translators: %s: Twenty-four-hour overdue action count or unavailable label. */
			sprintf( __( 'Past Due >24h: %s', 'queue-health-monitor' ), isset( $counts['overdue_24h'] ) ? $counts['overdue_24h'] : __( 'Unavailable', 'queue-health-monitor' ) ),
			'',
			__( 'WP-CRON', 'queue-health-monitor' ),
			/* translators: %s: Boolean DISABLE_WP_CRON state. */
			sprintf( __( 'DISABLE_WP_CRON: %s', 'queue-health-monitor' ), $environment['disable_wp_cron'] ? 'true' : 'false' ),
			/* translators: %s: Boolean ALTERNATE_WP_CRON state. */
			sprintf( __( 'ALTERNATE_WP_CRON: %s', 'queue-health-monitor' ), $environment['alternate_wp_cron'] ? 'true' : 'false' ),
			'',
			__( 'CHECKS', 'queue-health-monitor' ),
		);

		foreach ( $snapshot['checks'] as $check ) {
			$lines[] = sprintf( '%s: %s — %s', $check['title'], strtoupper( $check['status'] ), $check['summary'] );
		}

		$lines[] = '';
		$lines[] = __( 'TOP PROBLEM SOURCES (BOUNDED SAMPLE)', 'queue-health-monitor' );
		if ( empty( $queue['data_available'] ) ) {
			$lines[] = __( 'Unavailable because the Action Scheduler queue could not be read.', 'queue-health-monitor' );
		} elseif ( empty( $queue['sources'] ) ) {
			$lines[] = __( 'No failed, overdue, or potentially stuck actions were found in the bounded sample.', 'queue-health-monitor' );
		}
		foreach ( array_slice( $queue['sources'], 0, 10 ) as $source ) {
			/* translators: 1: Source name, 2: Failed count, 3: Past-due count, 4: Potentially stuck count, 5: Confidence. */
			$lines[] = sprintf( __( '%1$s: %2$d failed, %3$d past due, %4$d potentially stuck (%5$s confidence)', 'queue-health-monitor' ), $source['name'], $source['failed'], $source['past_due'], $source['stuck'], $source['confidence'] );
		}
		$lines[] = '';
		$lines[] = __( 'LIKELY DIAGNOSIS', 'queue-health-monitor' );
		/* translators: 1: Diagnostic confidence, 2: Diagnosis message. */
		$lines[] = sprintf( __( '%1$s: %2$s', 'queue-health-monitor' ), $diagnosis['confidence'], $diagnosis['message'] );
		$lines[] = '';
		$lines[] = __( 'PRIVACY', 'queue-health-monitor' );
		$lines[] = __( 'No action arguments, credentials, cookies, authorization data, customer records, or full server paths are included.', 'queue-health-monitor' );

		return $this->redactor->redact( implode( "\n", $lines ) );
	}
}
