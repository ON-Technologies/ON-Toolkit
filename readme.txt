=== ON Toolkit — Site Health, Broken Link Scanner & Media Inspector ===
Contributors: ontoolkit
Donate link: https://github.com/sponsors/Tksharmely
Tags: site health, broken link checker, media inspector, database cleanup, performance
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The ultra-high-performance WordPress Site Health platform. Detect broken links, audit unused media, and safely clean database waste in a unified dashboard.

== Description ==

**ON Toolkit** is a modern, unified WordPress Site Health platform engineered for performance, precision, and simplicity.

Instead of installing 4 separate bloated utilities that slow down your website, ON Toolkit provides a single, high-speed administration platform with zero public site overhead.

== Why ON Toolkit? ==

* **Recursive Elementor Scanning**: Parses deeply nested `_elementor_data` JSON metadata where 70%+ of modern links hide.
* **Domain-Aware Rate Limiting**: Groups URLs by domain to prevent Cloudflare/WAF IP bans during background crawls.
* **Safe Batched Cleanup**: Executes non-locking SQL deletes (`DELETE ... LIMIT 500`) to guarantee zero site downtime.
* **Unified Site Health Scoring**: Gamified 0-100 real-time score evaluating Performance, Database, Broken Links, and Media Library.
* **Zero Frontend Asset Loading**: Enqueues 0 scripts or stylesheets on the public frontend of your website.
* **Non-Blocking Background Queue**: Micro-batch processing via Action Scheduler / WP-Cron allows admins to close the page anytime.
* **Modern Native WordPress Interface**: Clean, familiar admin design matching WooCommerce, RankMath, and Yoast.

### Key Platform Features

* **Gamified ON Site Health Score**: Real-time 0–100 score evaluating Performance, Database, Broken Links, and Media Library health.
* **Broken Link Scanner**: Asynchronous background queue scanning posts, pages, nav menus, and Elementor JSON metadata without freezing your site.
* **Resilient HTTP Verification Engine**: 3-stage fallback flow (HEAD → 1KB GET → Timeout Retry) to eliminate false positives.
* **Explain Why Context Engine**: Provides human-readable diagnostic context explaining why a link is broken (e.g. 403 WAF block vs 404 deleted page).
* **Media Inspector**: Single-pass audit detecting unused images, duplicate SHA-256 content hashes, huge PNGs (>1MB), huge JPGs (>500KB), oversized dimensions (>3000px), and SVGs.
* **Safe Fix Queue & Inline ALT Fix**: Execute batch actions in 1 click and edit missing ALT text inline with Undo capability.
* **Database Space Recovery**: Non-locking batched cleanup (`DELETE ... LIMIT 500`) for post revisions, trash, expired transients, and orphan metadata with mandatory dry-run previews.
* **WP-CLI First Class Support**: Native command suite (`wp on-toolkit clean-db`, `wp on-toolkit audit-media`) for DevOps and crontab automation.

== Installation ==

1. Upload the `on-toolkit` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress Admin.
3. Navigate to **ON Toolkit** in your WordPress admin menu to run your first 500ms Instant Onboarding Health Audit.

== Frequently Asked Questions ==

= Will ON Toolkit slow down my website? =
No. ON Toolkit guarantees zero public site asset loading. When modules are disabled, they register zero hooks. Background link scans run asynchronously via Action Scheduler / WP-Cron in small micro-batches.

= Is it compatible with Elementor? =
Yes! ON Toolkit recursively traverses `_elementor_data` JSON metadata to audit button links, section links, and image usage.

= Does database cleanup risk breaking my site? =
No. All database cleanup operations use limit-chunked SQL statements (`LIMIT 500`) and default to a Dry-Run preview before any data is removed.

== Screenshots ==

1. ON Site Health Dashboard featuring 0-100 score ring and 4 sub-category health pillars.
2. Broken Link Scanner with Explain Why diagnostic context.
3. Media Inspector with SHA-256 duplicate content detection and inline ALT text editing.
4. Safe Database Space Recovery with Dry-Run preview.

== Changelog ==

= 1.0.0 =
* Initial public release on WordPress.org.
