# SchedSense: Fast Diagnostics for Action Scheduler

SchedSense helps WordPress administrators investigate delayed and failing Action Scheduler work. It shows bounded queue totals, overdue and failed actions, likely callback sources, and local WP-Cron, loopback, and REST API checks.

## Features

- Queue totals and clickable status and overdue-age filters.
- Likely source-plugin attribution for sampled actions.
- Local WP-Cron, loopback, and REST API checks.
- A copyable diagnostic report that excludes action arguments and credentials.
- Optional, confirmed deletion of an individual action only while it remains failed. Deleting a record does not retry or repair its callback.

## Install

Upload the `schedsense-fast-diagnostics-for-action-scheduler` plugin ZIP through **Plugins → Add New → Upload Plugin**, or copy the plugin folder into `wp-content/plugins/` and activate **SchedSense**. Open **Tools → SchedSense** to view diagnostics.

The WordPress.org directory readme is in [`readme.txt`](readme.txt). Listing graphics and screenshots are in [`assets/wordpress.org/`](assets/wordpress.org/).

## Requirements

- WordPress 6.8 or later
- PHP 7.4 or later
- Action Scheduler provided by a compatible plugin, such as WooCommerce

## Privacy and license

Diagnostics run on the current WordPress site. The plugin sends no diagnostic data to a third-party service. Licensed under GPL-2.0-or-later.
