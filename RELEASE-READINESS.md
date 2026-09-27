# SchedSense 1.0.3 WordPress.org review remediation

Updated: 2026-09-27

## Review findings addressed

- Replaced hard-coded plugin and content directory constants with WordPress path APIs; retained `site_url( 'wp-cron.php' )` so loopback checks target the site's real core endpoint, including subdirectory installs.
- Added nonce validation before reading the plugin's GET filters, tabs, and pagination values. Navigation links and filter forms carry the view nonce; state-changing controls retain capability checks and their own POST nonces.
- Sanitized cookies and HTTP Basic Authentication values before forwarding them to same-site loopback and REST requests.
- Replaced generic `qhm` and `queue_health_monitor_*` symbols with the distinct `SchedSense` / `schedsense_*` identifiers, and changed the admin page slug from `schedsense` to `schedsense_diagnostics`.
- Kept the cron diagnostic on WordPress's normal spawn path without directly including the core cron file.

## Validation

- Official WordPress.org Readme Validator accepted the current readme. The only note is that no donate link was found; this is optional.
- PHP syntax, JavaScript syntax, `git diff --check`, and Plugin Check's `plugin-review.xml` PHPCS ruleset passed. PHPCS reported zero errors and warnings across the plugin PHP files.
- Focused request-context checks confirmed invalid or absent view nonces fall back safely, valid allowlisted filters are read and sanitized, and non-scalar cookie/basic-auth values are rejected.
- Installed and activated the 1.0.3 package on a clean WordPress Playground site with WordPress 7.1.2, PHP 8.3.33, WooCommerce 11.1.2, and Action Scheduler 4.0.0. The plugin rendered and queried live queue data; its loopback and REST checks returned HTTP 200.
- Enabled `WP_DEBUG` and `WP_DEBUG_LOG`, revisited the plugin dashboard and queue views, and repeated failed-action deletion. The failed fixture was removed after confirmation, its pending fixture remained pending, and the debug log contained zero lines mentioning SchedSense. The disposable provider site's log did contain unrelated WooCommerce/SQLite notices.
- Plugin Check's browser page displayed “Checks complete. No errors found,” but five frontend-enqueue requests returned HTTP 400 with body `0` in the Playground environment. Treat that UI result as partial, not a definitive stock Plugin Check pass; run the official Plugin Check workflow on a conventional WordPress filesystem before submission.

## Package

- Candidate: `SchedSense-1.0.3-review-candidate-final.zip` (root folder `schedsense-fast-diagnostics-for-action-scheduler`). The package contains plugin runtime files and readme, excluding repository documentation and WordPress.org directory artwork.

## Submission boundary

This is a reviewed release candidate, not a WordPress.org approval or submission. WordPress.org's automated and human review can still identify issues that local checks do not reproduce.
