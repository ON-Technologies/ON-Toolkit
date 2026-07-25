<div align="center">

# ON Toolkit

### Modern Site Health Toolkit for WordPress

Find broken links, unused media, missing ALT text, and database bloat — all from a single, high-performance dashboard.

[![CI](https://github.com/ON-Technologies/on-toolkit/actions/workflows/ci.yml/badge.svg)](https://github.com/ON-Technologies/on-toolkit/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)]()
[![WordPress](https://img.shields.io/badge/WordPress-6.7%2B-21759B?logo=wordpress&logoColor=white)]()
[![Status](https://img.shields.io/badge/Status-Active-success)]()

</div>

---

## Overview

**ON Toolkit** is a modern WordPress Site Health platform designed to help website owners detect, prioritize, and safely fix common website issues before they impact SEO, performance, or user experience.

Unlike traditional maintenance plugins, ON Toolkit focuses on **fast background scanning**, **zero frontend performance impact**, and a unified **Site Health Score** that helps you understand your website at a glance.

---

# Features

## 🔗 Broken Link Scanner

- Recursive Elementor JSON scanning
- Gutenberg support
- Navigation menu scanning
- Domain-aware request throttling
- Smart HTTP validation (HEAD → GET fallback)
- Background batch scanning
- Redirect detection
- Response time monitoring
- Real-time progress tracking

---

## 🖼 Media Inspector

- Detect unused media
- Duplicate file detection
- Missing ALT text detection
- Oversized image detection
- Featured image validation
- Theme logo validation
- WooCommerce gallery support
- Safe deletion verification

---

## 🗄 Database Cleanup

- Safe batched cleanup
- Revision cleanup
- Trash cleanup
- Expired transient cleanup
- Spam comment cleanup
- Orphan metadata cleanup
- Recoverable space estimation
- Dry-run preview before cleanup

---

## 📊 Site Health Dashboard

See your website's overall health in seconds.

Example:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Site Health
91 / 100

🟢 Performance
🟡 Database
🔴 Broken Links
🟢 Media Library

Potential storage savings:
1.8 GB

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

# Why ON Toolkit?

Most maintenance plugins provide isolated tools.

ON Toolkit combines them into a single health platform with modern architecture and performance-first design.

### Highlights

- Modern native WordPress interface
- Zero frontend asset loading
- Lazy-loaded modules
- Background processing
- REST API powered
- WP-CLI support
- PSR-4 architecture
- Dependency Injection
- Translation ready
- Developer friendly

---

# Performance First

ON Toolkit is built with one principle:

> **Never slow down WordPress.**

Disabled modules load:

- No hooks
- No CSS
- No JavaScript
- No database queries

Long-running operations execute in the background using Action Scheduler when available, with an automatic fallback to native WP-Cron.

---

# Requirements

- PHP 8.1+
- WordPress 6.7+
- MySQL 8+ / MariaDB equivalent
- Composer (development)

---

# Installation

1. Install the plugin.
2. Activate ON Toolkit.
3. Open **ON Toolkit** from the WordPress admin menu.
4. Run your first Site Health Scan.

---

# Roadmap

### Version 1

- ✅ Broken Link Scanner
- ✅ Media Inspector
- ✅ Database Cleanup
- ✅ Site Health Dashboard

### Future Modules

- Security Scanner
- Performance Analyzer
- SEO Inspector
- Redirect Manager
- Theme Inspector
- Plugin Conflict Detector
- PHP Compatibility Checker
- Scheduled Reports

---

# Development

```bash
composer install
```

Run coding standards

```bash
composer phpcs
```

Run static analysis

```bash
composer phpstan
```

Run tests

```bash
composer test
```

---

# Contributing

Contributions, issues and feature requests are welcome.

Please open an issue before submitting major changes.

---

# License

Released under the GPL v2 or later License.

---

# Support

If ON Toolkit saves you time or improves your workflow, consider supporting its development.

❤️ GitHub Sponsors

https://github.com/sponsors/Tksharmely

---

<div align="center">

Built with ❤️ for the WordPress community.

</div>
