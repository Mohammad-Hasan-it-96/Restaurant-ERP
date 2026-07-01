# Restaurant-ERP — Architecture Remediation Roadmap

> Architectural review of Restaurant-ERP as a **commercial reusable template** intended to run
> as **hundreds of independent single-tenant installations** (one codebase + one MySQL DB +
> one `.env` + one cron/queue worker per restaurant). Weaknesses are ranked by severity, followed
> by a phased, high-value remediation roadmap.
>
> **Chosen direction:** keep the single-tenant-per-install model and add a lightweight **control
> plane** (fleet deploy automation, remote log/error aggregation, pull-based health monitoring)
> rather than re-architecting to multi-tenancy.
>
> Evidence is cited as `file:line`.

---

## Overall finding

The per-install engineering is genuinely good — centralized logging (`LogService`), env-driven
theme + feature flags, DB-stored identity, hashed customer tokens, consolidated cache invalidation
in model events. The customization/config layer is well-designed for reuse. But **everything around
operating a fleet is missing or opt-in**, and the core domain has **no service abstractions**
(notifications/payments hardcoded) and **duplicated, drifting domain vocabulary** across the two
front-ends. These are the blockers to selling and operating this at scale.

---

## Severity-Ranked Weaknesses

### CRITICAL

**C1 — No fleet operations layer (deploy, log/error aggregation, automatable health).**
- No deployment automation: no root `Dockerfile`/`docker-compose`, no `.github/` CI, no
  Envoy/Forge/Deployer scripts. `composer.json:46-66` has only stock scripts + a `dev` task. Every
  patch is a manual per-host `git pull` → `composer install` → `npm run build` → `migrate --force`
  → `config:cache`. No staged rollout, rollback, or version-gated deploy; no way to know which
  install runs which version.
- Logs are local files only. Default channel `app` = `daily` (`config/logging.php:68-75,94`),
  14-day retention. `papertrail`/`slack`/`stderr` exist but are dormant/opt-in per install. No
  Sentry/Bugsnag/Datadog SDK. `withExceptions` (`bootstrap/app.php:64-82`) only fans out to
  Telegram. `X-Request-Id` correlates *within* a box but nothing correlates *across* the fleet.
- `/api/health` is admin-session-gated (`routes/web.php:18-20`) → external monitors can't call it,
  and a DB outage returns a login redirect, not a 503. `/up` is liveness-only.

**C2 — No idempotent upgrade path; new config/seed data never reaches existing installs.**
- Seeders are idempotent (`SystemConfigSeeder` `firstOrCreate`, `:74-77`) but **run only once**, on
  fresh install (`InstallController.php:187-189`), then gated off by `EnsureInstalled`. No migration
  calls a seeder; no post-deploy hook; no `app:upgrade` command.
- Any new `system_configs` key / language / zone default added in v2 **silently never reaches
  existing installs**.
- Migrations run **inside the install HTTP request** (`InstallController.php:186-189`) — timeout →
  half-migrated DB. No schema-version ledger. `finish()` never runs `config:cache`/`route:cache`.

**C3 — Backups ship unencrypted by default and embed every secret; failures don't alert; local-disk only.**
- `config/backup.php:182` reads `BACKUP_ARCHIVE_PASSWORD`; `.env.example` ships it **empty**, and
  the installer **never sets it** (`InstallController.php:157-169`). The bundle includes `.env`
  (all secrets + APP_KEY) and a full DB dump (`config/backup.php:31-32,93-95`) → default daily
  backup is an **unencrypted** archive of every secret + all customer data.
- Default destination `local` → `storage/app/private` on the **same host** it protects.
- Backup failures log at `error` (`backup.failed`, `AppServiceProvider`) but `config/alerts.php`
  has **no `backup.` category** → failures never page. Restore is manual/untested.

**C4 — No service abstractions; notifications hardcoded to FCM+Arabic, payments hardcoded to cash.**
- No interfaces/contracts anywhere (`app/` has no `Contracts` dir).
- `NotificationService` is entirely Firebase-specific; message bodies hardcoded Arabic
  (`NotificationService.php:104-116`, re-hardcoded at `OrderController.php:326-335`).
- Payment hardcoded to cash: `markAsPaid()` sets `'payment_method' => 'cash'`
  (`OrderController.php:274,278`). No `PaymentProvider` seam; `is_paid`/legacy columns synced by hand.

### HIGH

**H1 — `DatabaseSeeder` plants a known-password admin (fleet backdoor).**
`database/seeders/DatabaseSeeder.php:18-23` creates `admin@example.com` / `bcrypt('password')` via
factory. A stray `php artisan db:seed` during upgrade/troubleshoot plants a public-password admin.

**H2 — New `FEATURE_*` flags default ON → auto-enable features fleet-wide on upgrade.**
Every flag is `env('FEATURE_...', true)` (`config/system_features.php:48-88`). New capabilities turn
**on for every restaurant on upgrade** unless the operator preemptively opts out in each `.env`.

**H3 — SPA/API contract has no version handshake; stale `index.html` → white-screen; committed build artifacts.**
- Built SPA committed (`public/spa/` + hashed bundles) → `git pull` merge conflicts on hashed names.
- `public/spa/index.html` isn't content-hashed; after an upgrade a cached shell requests **dead
  asset filenames** → blank app until hard reload.
- No API version negotiation (`client.js:3` hardcodes `/api/v1`); shape changes silently break
  cached SPAs.

**H4 — Domain vocabulary duplicated & drifting; money formatting triplicated with a real currency bug.**
- Status→color/bucket re-encoded independently in Blade (`status-badge.blade.php:2-14`) and React
  (`MyOrders.jsx:10-18`, `OrderHistory.jsx:9-16`, `index.css:1754-1782`) with mismatched bucketing.
- Money formatting triplicated: `SystemConfigService::formatMoney()` / `money()`; JS `formatPrice()`
  (`format.js:34-46`); **and** raw `number_format($x,2)` in admin order/invoice Blade
  (`show.blade.php:234-276`, `invoice.blade.php:355-398`) that **ignores configured currency
  symbol/decimals** — a genuine correctness bug.
- Two hand-maintained i18n systems (`resources/lang/{ar,en}/app.php` vs `customer-spa/src/i18n.js`).

**H5 — Fat controllers + 220-line `createOrder` god method; business logic split across API/admin.**
- `Admin/OrderController.php`: delivery-fee recomputation in the controller; transition pattern
  copy-pasted 5× (`:94-259`); `notifyOrderStatus` missing on delivered/completed.
- Order create/modify in `OrderService` but cancel inlined in `API/V1/OrderController.php:93-133`.
- `OrderService::createOrder` (`:29-251`) mixes customer resolution, HTTP session mutation, token
  issuance, FCM, dup-guard cache, hours checks, pricing, persistence, logging — untestable in isolation.

### MEDIUM

**M1 — All infra on the `database` driver (cache/queue/session), no Redis, no cache tags.** Every
cache op is a DB round-trip — the cache *is* load on the MySQL it should relieve.

**M2 — A stopped queue worker is invisible; queue/cache/storage failures don't alert.** Alerting
rides on `Queue::failing` (fires only when a worker processes+fails a job). `config/alerts.php`
whitelists only `health.db_failure`. No Horizon/Supervisor/heartbeat.

**M3 — Thin tests on the core money path and installer.** No end-to-end test for
`OrderService::createOrder` / `POST /api/v1/orders`. `PerformanceTest` doesn't assert query counts.

**M4 — Incomplete per-install provisioning by the installer.** Writes only `THEME_PRIMARY`
(`InstallController.php:168`), not dark/light. Secrets (`TELEGRAM_*`, `FIREBASE_CREDENTIALS`,
`AWS_*`, `BACKUP_ARCHIVE_PASSWORD`) are hand-edit-only.

**M5 — Config-cache staleness trap fleet-wide.** Theme/flags/version are cached config; every env
change needs a manual `config:cache` with nothing enforcing/verifying it.

**M6 — Adding an order type/status touches ~6 disconnected places; no registry.** `featureEnabled`
defaults to `true` (`features.js:19-22`) so a typo'd flag path silently *enables* a feature.

**M7 — Duplicate/dead/irreversible migrations.** Duplicate timestamp `2023_08_15_000000`; dead
`2026_05_14_131439_move_restaurant_names...` on a non-existent table; one-shot irreversible data
migrations with no-op `down()`.

### LOW / HYGIENE

**L1 — Repo hygiene:** 32 MB `ngrok.exe` tracked; `.rnd` tracked; `public/spa/` build artifacts
committed; `.idea/` not gitignored; leftover SQLite bootstrap in `composer.json:57-61`.

---

## Phased Remediation Roadmap

Ordered by value-to-effort. Each phase is independently shippable.

### Phase 0 — Stop the bleeding (security & data safety) — do first
- **Encrypt backups by default + off-host + alert on failure.** Installer generates a random
  `BACKUP_ARCHIVE_PASSWORD` per install (via `EnvWriter`); flip `.env.example` guidance. Add a
  `backup.*` category to `config/alerts.php` and route `backup.failed`/`backup.cleanup_failed` to
  alerting. Document/scaffold the `s3` destination. *(C3)*
- **Neutralize the backdoor seeder.** `DatabaseSeeder` refuses to run in production / requires
  env-provided credentials; no factory in a prod seeder. *(H1)*
- **Repo hygiene:** untrack `ngrok.exe` + `.rnd`; gitignore them + `.idea/`; stop committing
  `public/spa/` build output. *(L1, H3-artifacts)*

### Phase 1 — Fleet control plane
- **Central error/log aggregation, on by default**, tagged with a per-install identifier. *(C1)*
- **Public, token-guarded health endpoint** returning full `HealthService::report()` with a proper
  503 on critical-subsystem failure. Keep `/up` for liveness. *(C1, M2)*
- **Deploy automation + version reporting** (one-command deploy running `migrate --force` + config
  cache; each install reports version + git SHA). *(C1)*
- **Queue-worker heartbeat + Supervisor config.** *(M2)*

### Phase 2 — Safe, idempotent upgrades
- **`php artisan app:upgrade`** = `migrate --force` + re-run idempotent config/data seeders +
  `config:cache` + `storage:link`, as the single documented upgrade entry point. *(C2, M5)*
- **Schema/version guard** (assert no pending migrations at boot; surface in health). *(C2)*
- **New feature flags default to previous behavior.** *(H2)*
- **Move installer migration off the HTTP request**; write full theme triplet + secret placeholders. *(C2, M4)*

### Phase 3 — SPA/API contract stability
- **Build the SPA in CI/deploy**, not committed to git; cache-bust so a stale shell can't request
  dead bundles. *(H3)*
- **Version handshake** with a non-destructive "please refresh" on mismatch. *(H3)*

### Phase 4 — Extensibility abstractions
- **`NotificationChannel` interface** with an FCM implementation; message bodies in lang files. *(C4)*
- **`PaymentProvider` seam** (even if only `CashProvider` today); centralize `is_paid`/legacy sync. *(C4)*

### Phase 5 — Domain single-source-of-truth & correctness
- **Fix the admin currency bug now**: `money()` helper in `orders/show.blade.php` +
  `invoice.blade.php`. *(H4)*
- **One source of truth for order status/type metadata** exposed via the settings/API payload and
  consumed by both Blade and the SPA. *(H4, M6)*
- **Consolidate i18n** so a language is added in one place. *(H4)*

### Phase 6 — Domain layering & tests
- **Extract status-transition + delivery-fee logic** from `Admin/OrderController` into
  `OrderService`; move cancel there; fix missing `notifyOrderStatus`. Break up `createOrder`. *(H5)*
- **Add end-to-end tests** for `POST /api/v1/orders`, the installer, and query-count assertions. *(M3)*
- **Consider Redis** for cache/queue/session (or document it as the production requirement). *(M1)*

---

## Verification (per phase)

- **Phase 0:** `backup:run` on a fresh install produces a password-protected zip (`unzip` refuses
  without the password); a forced backup failure pages the alert channel; `php artisan db:seed`
  refuses on a prod-flagged env; `git ls-files` no longer lists `ngrok.exe`/`.rnd`/`public/spa`.
- **Phase 1:** external monitor hits the health endpoint; killing MySQL returns 503 (not a redirect);
  a thrown exception lands in the central tracker tagged with the install identifier; stopping the
  worker alerts.
- **Phase 2:** on a v1-seeded DB, `php artisan app:upgrade` backfills a new `system_configs` key
  without clobbering operator values; a new `FEATURE_*` defaults to prior behavior.
- **Phase 3:** deploying a new SPA build with an old cached `index.html` loads or prompts to refresh
  (no white screen).
- **Phase 4–6:** `php artisan test` passes; an admin invoice under a non-`$` currency matches
  `money()`; order-status colors/labels match between the admin badge and the SPA.
