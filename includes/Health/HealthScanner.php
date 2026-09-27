<?php
/**
 * Fault-isolated health scanner.
 *
 * @package SchedSense
 */

namespace QueueHealthMonitor\Health;

use QueueHealthMonitor\Health\Checks\ActionSchedulerCheck;
use QueueHealthMonitor\Health\Checks\CronCheck;
use QueueHealthMonitor\Health\Checks\FailedActionsCheck;
use QueueHealthMonitor\Health\Checks\LoopbackCheck;
use QueueHealthMonitor\Health\Checks\OverdueActionsCheck;
use QueueHealthMonitor\Health\Checks\RestApiCheck;
use QueueHealthMonitor\Health\Checks\StuckActionsCheck;
use QueueHealthMonitor\Scheduler\ActionSchedulerAdapter;
use QueueHealthMonitor\Scheduler\ActionAnalyzer;
use QueueHealthMonitor\Support\Environment;
use Throwable;

defined( 'ABSPATH' ) || exit;

final class HealthScanner {
	/** @var ActionSchedulerAdapter */
	private $adapter;

	/**
	 * Constructor.
	 *
	 * @param ActionSchedulerAdapter $adapter Scheduler adapter.
	 */
	public function __construct( ActionSchedulerAdapter $adapter ) {
		$this->adapter = $adapter;
	}

	/**
	 * Run checks independently so one failure cannot break the dashboard.
	 *
	 * @return array
	 */
	public function scan() {
		$environment = ( new Environment() )->collect();
		$queue       = ( new ActionAnalyzer( $this->adapter ) )->analyze();
		$context     = array(
			'environment'              => $environment,
			'queue'                    => $queue,
			'action_scheduler_version' => $this->adapter->get_version(),
		);
		$checks      = array(
			new ActionSchedulerCheck(),
			new OverdueActionsCheck(),
			new FailedActionsCheck(),
			new StuckActionsCheck(),
			new CronCheck(),
			new LoopbackCheck(),
			new RestApiCheck(),
		);
		$results     = array();

		foreach ( $checks as $check ) {
			try {
				$results[] = $check->run( $context )->to_array();
			} catch ( Throwable $throwable ) {
				$results[] = ( new HealthResult(
					'check-failed',
					__( 'Diagnostic check unavailable', 'schedsense-fast-diagnostics-for-action-scheduler' ),
					'unavailable',
					'none',
					__( 'One diagnostic could not finish. Other checks are unaffected.', 'schedsense-fast-diagnostics-for-action-scheduler' ),
					'',
					__( 'Refresh diagnostics. If the result repeats, inspect the site PHP error log.', 'schedsense-fast-diagnostics-for-action-scheduler' )
				) )->to_array();
			}
		}

		$context['checks']       = $results;
		$context['generated_at'] = time();
		return $context;
	}
}
