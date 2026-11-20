<?php
/**
 * Bounded queue analysis.
 *
 * @package SchedSense
 */

namespace SchedSense\Scheduler;

defined( 'ABSPATH' ) || exit;

/** Collect bounded queue counts, samples, and source attribution. */
final class ActionAnalyzer {
	/**
	 * Scheduler read adapter.
	 *
	 * @var ActionSchedulerAdapter
	 */
	private $adapter;

	/**
	 * Source attribution for the action sample.
	 *
	 * @var SourceResolver
	 */
	private $resolver;

	/**
	 * Constructor.
	 *
	 * @param ActionSchedulerAdapter $adapter Scheduler adapter.
	 */
	public function __construct( ActionSchedulerAdapter $adapter ) {
		$this->adapter  = $adapter;
		$this->resolver = new SourceResolver();
	}

	/**
	 * Generate queue facts and a clearly-labelled bounded source sample.
	 *
	 * @return array
	 */
	public function analyze() {
		$this->adapter->reset_query_error();

		if ( ! $this->adapter->is_available() ) {
			return array(
				'available'      => false,
				'data_available' => false,
				'counts'         => array(),
				'sources'        => array(),
			);
		}

		$now         = time();
		$threshold   = array(
			'overdue_1h'  => $now - HOUR_IN_SECONDS,
			'overdue_6h'  => $now - ( 6 * HOUR_IN_SECONDS ),
			'overdue_24h' => $now - DAY_IN_SECONDS,
			'overdue_7d'  => $now - ( 7 * DAY_IN_SECONDS ),
		);
		$due_total   = $this->adapter->count(
			array(
				'status'       => 'pending',
				'date'         => $this->utc_date( $now ),
				'date_compare' => '<=',
			)
		);
		$overdue_1h  = $this->adapter->count(
			array(
				'status'       => 'pending',
				'date'         => $this->utc_date( $threshold['overdue_1h'] ),
				'date_compare' => '<=',
			)
		);
		$overdue_6h  = $this->adapter->count(
			array(
				'status'       => 'pending',
				'date'         => $this->utc_date( $threshold['overdue_6h'] ),
				'date_compare' => '<=',
			)
		);
		$overdue_24h = $this->adapter->count(
			array(
				'status'       => 'pending',
				'date'         => $this->utc_date( $threshold['overdue_24h'] ),
				'date_compare' => '<=',
			)
		);
		$overdue_7d  = $this->adapter->count(
			array(
				'status'       => 'pending',
				'date'         => $this->utc_date( $threshold['overdue_7d'] ),
				'date_compare' => '<=',
			)
		);
		$counts      = array(
			'pending'           => $this->adapter->count( array( 'status' => 'pending' ) ),
			'failed'            => $this->adapter->count( array( 'status' => 'failed' ) ),
			'in_progress'       => $this->adapter->count( array( 'status' => 'in-progress' ) ),
			'stuck_in_progress' => $this->adapter->count(
				array(
					'status'           => 'in-progress',
					'modified'         => $this->utc_date( $now - HOUR_IN_SECONDS ),
					'modified_compare' => '<=',
				)
			),
			'complete'          => $this->adapter->count( array( 'status' => 'complete' ) ),
			'overdue_total'     => $due_total,
			'under_1h'          => max( 0, $due_total - $overdue_1h ),
			'overdue_1h'        => $overdue_1h,
			'one_to_6h'         => max( 0, $overdue_1h - $overdue_6h ),
			'six_to_24h'        => max( 0, $overdue_6h - $overdue_24h ),
			'overdue_24h'       => $overdue_24h,
			'one_to_7d'         => max( 0, $overdue_24h - $overdue_7d ),
			'overdue_7d'        => $overdue_7d,
		);
		if ( $this->adapter->has_query_error() ) {
			return $this->unavailable_queue();
		}

		$sample = $this->source_sample( $threshold['overdue_1h'] );
		if ( $this->adapter->has_query_error() ) {
			return $this->unavailable_queue();
		}

		return array(
			'available'      => true,
			'data_available' => true,
			'counts'         => $counts,
			'sources'        => $sample['sources'],
			'failure_sample' => $sample['failures'],
		);
	}

	/**
	 * Create a UTC DateTime value in the format required by Action Scheduler.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return \DateTime
	 */
	private function utc_date( $timestamp ) {
		$date = new \DateTime( '@' . absint( $timestamp ) );
		$date->setTimezone( new \DateTimeZone( 'UTC' ) );
		return $date;
	}

	/**
	 * Report a detected provider whose queue could not be read.
	 *
	 * @return array
	 */
	private function unavailable_queue() {
		return array(
			'available'      => true,
			'data_available' => false,
			'counts'         => array(),
			'sources'        => array(),
		);
	}

	/**
	 * Aggregate a maximum of 300 relevant actions.
	 *
	 * @param int $cutoff Latest timestamp eligible for the overdue and stuck samples.
	 * @return array
	 */
	private function source_sample( $cutoff ) {
		$sample = array_merge(
			$this->adapter->query(
				array(
					'status'   => 'failed',
					'per_page' => 100,
					'orderby'  => 'date',
					'order'    => 'DESC',
				)
			),
			$this->adapter->query(
				array(
					'status'       => 'pending',
					'date'         => $this->utc_date( $cutoff ),
					'date_compare' => '<=',
					'per_page'     => 100,
					'orderby'      => 'date',
					'order'        => 'ASC',
				)
			),
			$this->adapter->query(
				array(
					'status'           => 'in-progress',
					'modified'         => $this->utc_date( $cutoff ),
					'modified_compare' => '<=',
					'per_page'         => 100,
					'orderby'          => 'modified',
					'order'            => 'ASC',
				)
			)
		);

		$hook_sources = $this->resolver->resolve_many( array_column( $sample, 'hook' ) );
		$sources      = array();
		$failures     = 0;
		$attributed   = array();
		foreach ( $sample as $action ) {
			$source = $hook_sources[ $action['hook'] ];
			$key    = $source['name'];
			if ( 'failed' === $action['status'] ) {
				++$failures;
				if ( 'high' === $source['confidence'] && ! empty( $source['path'] ) ) {
					$owner                = $source['type'] . ':' . explode( '/', $source['path'], 2 )[0];
					$attributed[ $owner ] = ( $attributed[ $owner ] ?? 0 ) + 1;
				}
			}
			if ( ! isset( $sources[ $key ] ) ) {
				$sources[ $key ] = array(
					'name'        => $source['name'],
					'confidence'  => $source['confidence'],
					'pending'     => 0,
					'failed'      => 0,
					'in_progress' => 0,
					'stuck'       => 0,
					'past_due'    => 0,
					'hooks'       => array(),
				);
			}
			if ( 'in-progress' === $action['status'] ) {
				++$sources[ $key ]['in_progress'];
				++$sources[ $key ]['stuck'];
			} else {
				++$sources[ $key ][ $action['status'] ];
			}
			if ( 'pending' === $action['status'] && $action['scheduled'] > 0 && $action['scheduled'] <= $cutoff ) {
				++$sources[ $key ]['past_due'];
			}
			$sources[ $key ]['hooks'][ $action['hook'] ] = isset( $sources[ $key ]['hooks'][ $action['hook'] ] ) ? $sources[ $key ]['hooks'][ $action['hook'] ] + 1 : 1;
		}

		usort(
			$sources,
			static function ( $left, $right ) {
				return ( $right['failed'] + $right['past_due'] + $right['stuck'] ) <=> ( $left['failed'] + $left['past_due'] + $left['stuck'] );
			}
		);
		return array(
			'sources'  => array_slice( $sources, 0, 10 ),
			'failures' => array(
				'total' => $failures,
				'top'   => $attributed ? max( $attributed ) : 0,
			),
		);
	}
}
