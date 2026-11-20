# SchedSense roadmap

## Current audit (1.0.5)

- Fix filtered Actions pagination and reduce source-cache writes. Both changes have regression checks in [the audit pull request](https://github.com/shahidirfan100/schedsense-fast-diagnostics-for-action-scheduler/pull/1).
- Show the first HTTP response from local health checks. A redirected endpoint is useful diagnostic evidence, and request credentials stay with the original endpoint.

## Performance and diagnosis (1.0.6 candidate)

- The Actions tab reads only the filtered queue page and its source labels. Opening it with an empty diagnostics cache no longer triggers the full scan or two local HTTP probes.
- The Overview, Sources, and Report still share the full diagnostic snapshot. The scanner dropped two unused oldest-action lookups and still uses Action Scheduler's public APIs.
- The Overview and Report show the default WordPress cron event's scheduled time and distinguish a late event from a missing event or callback. Server cron, WP-CLI workers, and completed callbacks require separate evidence.
- Action Scheduler pending jobs at least an hour late now produce a queue warning. A likely common source requires registered callback evidence for at least 80% of all sampled failures, including unresolved failures in the denominator.
- The performance workflow compares five runs per combination of queue size, cache state, and view. It measures SQL statements, attempted HTTP calls, and median server-side rendering time on the same synthetic MariaDB data. Local HTTP responses are simulated in that benchmark. The separate smoke suite makes real loopback and REST requests.

## Comparison

| Product | Published strength | Implication for SchedSense |
| --- | --- | --- |
| [ModelGain Queue Health](https://wordpress.org/plugins/modelgain-queue-health-for-action-scheduler/) | Default runner schedule, dominant hooks, and callback ownership | The closest diagnostic alternative. Runner schedule support is baseline coverage. Verified ownership, low-cost navigation, and useful drill-downs are the opportunities. |
| [Action Scheduler](https://wordpress.org/plugins/action-scheduler/) | Native queue records and administration | SchedSense can guide investigation and deep-link to provider-owned logs while preserving the scheduler as the source of truth. |
| [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/) | WordPress cron event inspection, logs, and missed schedules | Look at WordPress cron and Action Scheduler completion evidence together to help separate an invocation problem from a stalled callback. |
| [Failed Actions Monitor](https://wordpress.org/plugins/failed-actions-monitor-for-action-scheduler/) | Daily failure summaries | Consider opt-in alerts after the same diagnosis is available and accurate on demand. |
| [Minuttes](https://wordpress.org/plugins/minuttes/) | Local queue controls and optional hosted trends | Keep SchedSense usable entirely within WordPress and make the one-time diagnosis easy to share. |

The comparison describes published features. The benchmark compares SchedSense versions in the same installation.

## Next (1.0.7)

- Profile cold Overview scans with MariaDB query plans and a Redis object cache. On busy installations, the remaining work includes counting statuses and hydrating up to 300 sampled actions through Action Scheduler. Preserve exact queue counts and compatibility with alternate stores before changing the query path. [Action Scheduler's storage model](https://actionscheduler.org/faq/)
- Add WooCommerce-bundled Action Scheduler and two-site multisite fixtures. Verify that each site shows its own queue, caches and runner evidence. Cover a custom worker and disabled traffic-triggered WP-Cron without treating external execution as a failure. [Action Scheduler WP-CLI guidance](https://actionscheduler.org/wp-cli/)
- Run a fixture with two callback owners on one hook. Display ambiguous attribution explicitly and require enough evidence before narrowing the diagnosis to one source. [ModelGain ownership checks](https://wordpress.org/plugins/modelgain-queue-health-for-action-scheduler/)

## Next feature release (1.1)

- Compare recent queue completions with the oldest overdue job and the runner schedule. Show whether there is evidence of queue progress, a problem concentrated on one hook, or insufficient evidence. Use bounded lookups and test cases with real fixture callbacks. [WooCommerce support advice on comparing runner activity](https://wordpress.org/support/topic/woocommerce-analytics-scheduler-actions/)
- Add direct links from the diagnosis to a matching sanitized action view and the provider's existing logs. Keep arguments out of copyable reports. Validate the link and visibility with WooCommerce and standalone Action Scheduler.
- Provide a read-only WP-CLI summary for server cron and incident triage, using the same diagnostic data as the admin screen. Leave HTTP probes opt-in for CLI callers and emit machine-readable status with confidence.

## Later

- Assess demand for opt-in failure summaries after measuring the cost of polling. [Failed Actions Monitor](https://wordpress.org/plugins/failed-actions-monitor-for-action-scheduler/) documents a daily digest.
- Evaluate opt-in local progress history after setting measurable storage and write budgets. An absent observation should remain unknown when an external runner can run independently.

## Product scope

Keep SchedSense focused on diagnosis and explicit, single-record cleanup. Queue execution, rescheduling, and retention remain the scheduler provider's responsibility.
