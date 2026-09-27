=== SchedSense: Fast Diagnostics for Action Scheduler ===
Contributors: shahidirfan100
Tags: cron, diagnostics, queue, troubleshooting, site health
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.0.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast diagnostics for Action Scheduler queues: review overdue and failed actions, likely sources, WP-Cron, loopback, and REST API health.

== Description ==

SchedSense: Fast Diagnostics for Action Scheduler helps administrators investigate delayed or failing scheduled work. It reads Action Scheduler through its public APIs and reports queue totals, overdue pending actions, potentially stuck in-progress actions, failed actions, and relevant site health checks. Counts and source attribution are clearly identified as bounded samples where applicable.

= Features =

* Queue totals for pending, overdue, failed, in-progress, and completed actions.
* Overdue-age buckets from one hour to seven days, with links to matching action lists.
* Flags in-progress actions with no recorded update for over one hour as potentially stuck; long callbacks can be intentional.
* Checks WP-Cron configuration, the local WordPress loopback, and the local REST API.
* Attributes registered callbacks to likely source plugins using callback file metadata; uncertain matches are labelled.
* Bounded, filterable action lists by status, age range, exact hook, and exact group. Action arguments are never displayed.
* A confirmed, per-action delete control only for actions that are still failed. Deleting an entry does not repair or retry its callback.
* A copyable support report that excludes action arguments, credentials, cookies, customer records, and complete server paths.
* No telemetry, advertising, review nags, external account, analytics, remote service, or frontend assets.

Diagnostics run on the current WordPress site. The loopback check follows WordPress Site Health behavior: it posts a test marker to this site's `wp-cron.php`, includes the site's current login cookies and optional HTTP Basic Authentication for that same request, and honors the `https_local_ssl_verify` filter. The marker makes `wp-cron.php` finish without starting cron jobs. The REST API check also requests this site's own REST endpoint. No diagnostic data is sent to a third-party service.

SchedSense does not bundle, replace, reset, retry, reschedule, or directly execute Action Scheduler actions. An authorized administrator can explicitly delete a selected failed action record after confirmation; the server checks that its status is still failed. Deleting a record does not fix or retry its callback. An administrator can also request WordPress's normal cron spawner; due callbacks may then run, including callbacks that process Action Scheduler actions.

== Installation ==

1. Upload the `schedsense-fast-diagnostics-for-action-scheduler` folder to `/wp-content/plugins/`, or install the ZIP through Plugins > Add New > Upload Plugin.
2. Activate SchedSense through the Plugins screen.
3. Open Tools > SchedSense.
4. Select Refresh diagnostics when you want a fresh snapshot.

On Multisite, SchedSense reports the current site's scheduler context. Network-wide aggregate diagnostics are not supported.

== Frequently Asked Questions ==

= Does SchedSense require WooCommerce? =

No. It works with Action Scheduler when a compatible plugin provides and initializes it.

= Does SchedSense modify scheduled actions? =

It can delete an individual action only when Action Scheduler reports that it is failed, and only after an administrator confirms the request. This removes the failed entry; it does not fix or retry the callback. SchedSense does not delete pending, in-progress, or completed actions, and it does not retry, reschedule, or directly execute actions. The optional cron control asks WordPress to run its normal due cron events; those events may include Action Scheduler processing.

= Does SchedSense send data to another service? =

No. Checks and reports stay on this WordPress site. The loopback test sends its test marker and, when present, this site's login cookies or HTTP Basic Authentication only to this site's own `wp-cron.php` endpoint. The report is shared only if an administrator chooses to copy it.

= Why is WP-Cron shown as disabled? =

`DISABLE_WP_CRON` can be intentional on production sites using a server-level cron or WP-CLI. SchedSense reports that configuration as informational unless queue evidence suggests processing is inactive. PHP cannot prove that every external scheduler exists.

= Does every overdue action indicate a problem? =

No. Normal WP-Cron depends on site traffic, and long-running callbacks can be intentional. SchedSense uses age thresholds and labels in-progress actions without an update for over one hour as potentially stuck, not as confirmed failures.

= Can SchedSense fix a firewall or server configuration? =

No. It can report symptoms such as HTTP errors or timeouts, but it does not modify a CDN, firewall, security plugin, server configuration, `.htaccess`, or `wp-config.php`.

= What appears in the diagnostic report? =

The report contains WordPress, PHP, database, Action Scheduler, and WooCommerce versions; queue counts; check summaries; bounded source totals; and the current diagnosis. It excludes action arguments, credentials, cookies, authorization data, customer records, and complete server paths.

== Screenshots ==

1. SchedSense overview with queue totals, overdue-age filters, environment checks, and the current diagnosis.
2. Environment checks for Action Scheduler, WP-Cron, loopback, and REST API health.
3. Bounded action list filtered by status, overdue age, exact hook, or exact group, with delete controls only for failed records.
4. Likely source plugins ranked by sampled failed, overdue, and potentially stuck actions.
5. Copyable diagnostic report with queue data availability and privacy details.

== Changelog ==

= 1.0.3 =
* Hardened diagnostic request context and nonce-protected queue filters.
* Updated plugin identifiers and WordPress path resolution for the SchedSense brand.

= 1.0.2 =
* Rebranded the plugin as SchedSense: Fast Diagnostics for Action Scheduler and aligned the plugin slug and text domain.
* Removed a redundant WordPress core cron include before calling the normal cron spawner.
* Updated the plugin icon, banner, and screenshots for the new name.

= 1.0.1 =
* Added Action Scheduler queue health and overdue-age diagnostics.
* Added potentially stuck in-progress action checks.
* Added WP-Cron, loopback, and REST API diagnostics.
* Added callback source attribution and a privacy-conscious report.
* Added links from queue totals and overdue-age buckets to matching action lists.
* Added confirmed deletion for individual failed actions and clearer failure-specific guidance.

== Upgrade Notice ==

= 1.0.3 =
Hardens diagnostic request handling, sanitizes loopback credentials, and uses WordPress path APIs for plugin and content locations.
