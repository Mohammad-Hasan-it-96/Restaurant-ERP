# Changelog

All notable changes to this project are documented here. The version below is the
single source of truth in [`config/version.php`](config/version.php) (resolved via
`App\Support\Version`) — keep this file in sync when bumping the version.

This project adheres to [Semantic Versioning](https://semver.org/) (MAJOR.MINOR.PATCH).

## [1.0.0] - 2026-06-28

### Added
- Initial release.
- Installer wizard for fresh-domain setup (no manual `.env` editing).
- Operational systems: Telegram alerts, activity log, automatic backups, health checks.
- Reusable configuration: currency and brand theme, generic seed defaults.
