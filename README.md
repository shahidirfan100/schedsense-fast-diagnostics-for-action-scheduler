# Queue Health Monitor

A WordPress plugin for diagnosing Action Scheduler queue health. It reports pending, overdue, failed, and potentially stuck actions, checks WP-Cron, loopback, and REST API health, and provides bounded, privacy-conscious diagnostics.

## Features

- Queue totals and clickable status and overdue-age filters.
- Likely source-plugin attribution for sampled actions.
- Local WP-Cron, loopback, and REST API checks.
- A copyable diagnostic report that excludes action arguments and credentials.
- Optional, confirmed deletion of an individual action only while it remains failed. Deleting a record does not retry or repair its callback.

## Install

Upload the `queue-health-monitor` plugin ZIP through **Plugins → Add New → Upload Plugin**, or copy the plugin folder into `wp-content/plugins/` and activate **Queue Health Monitor**. Open **Tools → Queue Health Monitor** to view diagnostics.

The WordPress.org directory readme is in [`readme.txt`](readme.txt). WordPress.org listing graphics and screenshots are in [`assets/wordpress.org/`](assets/wordpress.org/).

## Requirements

- WordPress 6.8 or later
- PHP 7.4 or later
- Action Scheduler provided by a compatible plugin, such as WooCommerce

## Privacy and license

Diagnostics run on the current WordPress site. The plugin does not send diagnostic data to a third-party service. Licensed under GPL-2.0-or-later.
