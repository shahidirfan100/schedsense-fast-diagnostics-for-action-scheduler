# SchedSense roadmap

## Current audit (1.0.5)

- Fix filtered Actions pagination and reduce source-cache writes. Both changes have regression checks in [the audit pull request](https://github.com/shahidirfan100/schedsense-fast-diagnostics-for-action-scheduler/pull/1).
- Show the first HTTP response from local health checks. A redirected endpoint is useful diagnostic evidence, and request credentials stay with the original endpoint.

## Next (1.0.6–1.0.7 candidates)

- Test cold and warm diagnostic loads against 10,000 and 100,000 queue records on MariaDB with and without persistent object caching. The audit measured source-cache writes on 300 distinct hooks. Queue count queries and Action Scheduler's per-action fetches still need production-scale measurements.
- Add the default queue runner's next scheduled time where WordPress exposes it. Keep “WP-Cron disabled” separate from “no runner detected,” since a server cron can still drive the queue. [ModelGain runner diagnostics](https://wordpress.org/plugins/modelgain-queue-health-for-action-scheduler/) · [WP Crontrol missed schedules](https://wp-crontrol.com/help/missed-cron-events/)
- Test more scheduler providers and multisite with separate site queues before expanding the compatibility claim. [Action Scheduler compatibility policy](https://github.com/woocommerce/action-scheduler/blob/trunk/docs/usage.md)

## Later

- Assess demand for opt-in failure summaries after measuring the cost of polling. [Failed Actions Monitor](https://wordpress.org/plugins/failed-actions-monitor-for-action-scheduler/) documents a daily digest.
- Add more attribution evidence only where tests can distinguish registered ownership from the cause of a failure. [ModelGain ownership checks](https://wordpress.org/plugins/modelgain-queue-health-for-action-scheduler/)

## Product scope

Keep SchedSense focused on diagnosis and explicit, single-record cleanup. Queue execution, rescheduling, and retention remain the scheduler provider's responsibility.
