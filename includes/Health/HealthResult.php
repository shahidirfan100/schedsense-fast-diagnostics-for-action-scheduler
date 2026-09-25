<?php
/**
 * Immutable health-check result.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Health;

defined( 'ABSPATH' ) || exit;

final class HealthResult {
	/** @var array */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param string $id             Result ID.
	 * @param string $title          Human title.
	 * @param string $status         Result status.
	 * @param string $severity       Severity.
	 * @param string $summary        Human summary.
	 * @param string $evidence       Supporting evidence.
	 * @param string $recommendation Safe recommendation.
	 * @param array  $technical      Structured technical details.
	 */
	public function __construct( $id, $title, $status, $severity, $summary, $evidence = '', $recommendation = '', array $technical = array() ) {
		$allowed_status   = array( 'pass', 'warning', 'critical', 'informational', 'unavailable' );
		$allowed_severity = array( 'none', 'low', 'medium', 'high' );
		$this->data       = array(
			'id'             => sanitize_key( $id ),
			'title'          => (string) $title,
			'status'         => in_array( $status, $allowed_status, true ) ? $status : 'unavailable',
			'severity'       => in_array( $severity, $allowed_severity, true ) ? $severity : 'none',
			'summary'        => (string) $summary,
			'evidence'       => (string) $evidence,
			'recommendation' => (string) $recommendation,
			'technical'      => $technical,
			'timestamp'      => time(),
		);
	}

	/** @return array */
	public function to_array() {
		return $this->data;
	}
}
