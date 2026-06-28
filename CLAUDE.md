# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Restaurant ERP system with two separate front-ends sharing one Laravel 12 backend:
- **Admin panel**: Blade templates + Bootstrap 5, served at `/admin/*`
- **Customer SPA**: React 18 app in `customer-spa/`, built to `public/spa/`, served at `/spa/`

## Development Commands

```bash
# Start everything (PHP server + queue worker + Vite) concurrently
composer run dev

# Run tests (uses real MySQL — SQLite is disabled in phpunit.xml)
php artisan test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Lint PHP (Laravel Pint)
./vendor/bin/pint

# Admin panel assets (Vite + Tailwind)
npm run dev          # watch
npm run build        # production

# Customer SPA (separate Vite project)
cd customer-spa
npm run dev          # runs on port 5173, proxies /api to localhost:8000
npm run build        # outputs to ../public/spa/
```

## Database Setup

```bash
php artisan migrate
php artisan db:seed   # creates admin@example.com / password + system configs
```

Database: MySQL (`restaurant_erp_db`). Sessions, cache, and queues all use the database driver. **Tests also run against MySQL** — the SQLite/in-memory lines in `phpunit.xml` are commented out.

### Installer Wizard (fresh-domain setup, no manual .env editing)

On a fresh deploy, visiting any URL redirects to **`/install`** (`App\Http\Controllers\InstallController`), a 5-step wizard: **Database → App URL → Restaurant info → Admin account → Finish**. It writes `.env`, migrates, seeds defaults, creates the admin, and locks itself. The **Restaurant info** step now also captures **currency** (code/symbol/position/decimals), **timezone**, **default language** (ar/en), and **primary brand colour** — so a new restaurant is configured entirely through the wizard + admin Settings, with **zero source edits** (see "Reusable branding" below).

- **Install state**: `App\Support\Installer` — a lock file at `storage/installed` (gitignored). `isInstalled()` **self-heals**: an app configured before the wizard existed (APP_KEY set + DB reachable + `users` table) is auto-marked installed, so existing deployments are unaffected.
- **Pre-DB mode**: `App\Providers\InstallerServiceProvider` (registered first) — while not installed, forces `session.driver=file`, `cache.default=array`, `queue.default=sync`, and **generates+persists `APP_KEY`** on first request (the encrypter/CSRF need it). Runs in `boot()`. `App\Http\Middleware\SetLocale` early-returns when not installed (it queries the DB).
- **Gate**: `App\Http\Middleware\EnsureInstalled` (web group) redirects all traffic to `/install` until installed, then locks `/install` (redirects home).
- **`.env` writes**: `App\Support\EnvWriter` (the only thing that writes `.env`; injectable path for tests).
- **Finish** writes `.env` (incl. `APP_TIMEZONE` + `THEME_PRIMARY`), repoints the live DB connection (`DB::purge`/`reconnect`), runs `migrate` + **only** `SystemConfigSeeder`/`LanguageSeeder`/`DeliveryZoneSeeder` (never `DatabaseSeeder`, which hardcodes a Super Admin), `storage:link`, creates the admin from input (`User` password cast hashes), overrides restaurant + currency configs via `SystemConfigService::set()`, flips the chosen `Language.is_default`, writes the lock, and redirects to `/auth/login`.
- **Generic seed defaults**: `SystemConfigSeeder` seeds **unbranded** placeholders (`My Restaurant`, empty phone/whatsapp, `USD`/`$` currency) via `firstOrCreate` (never clobbers operator values on re-run); `DeliveryZoneSeeder` seeds **no** zones (location-specific — added at `/admin/delivery-zones`).
- **Re-install**: delete `storage/installed`.

### Reusable branding (currency & theme — "config-only" new restaurant)

Restaurant-specific values are consolidated into configuration so standing up a new restaurant needs **no source edits**:

- **Currency**: SystemConfig keys (group `general`) `currency_code`, `currency_symbol`, `currency_position` (`prefix`|`suffix`), `currency_decimals`. Read via `SystemConfigService::currency()` / `formatMoney()`. Use the **`money($amount)`** global helper (full formatting) or **`currency_symbol()`** (bare symbol) in Blade — never hardcode a currency string. Exposed to the SPA in the `/api/v1/settings/public` `currency` key; the SPA's `formatPrice` (in `customer-spa/src/utils/format.js`) renders it (set once via `setCurrency()`; counts use `formatNumber()`).
- **Theme**: single source of truth in **`config/theme.php`** (`primary`/`primary_dark`/`primary_light`, env-overridable via `THEME_*`). The admin layout echoes it into its `:root {}`; the SPA receives it in the settings payload `theme` key and applies it to `:root` at boot (`customer-spa/src/utils/theme.js`), with `index.css` values as fallback. **No per-restaurant admin colour UI** — change `THEME_*` + `php artisan config:cache`.
- **Identity** (name/logo/phone/whatsapp/messages/hours) stays in SystemConfig (admin-editable at `/admin/configs`). Restaurant strings are **not** duplicated in `resources/lang/*` anymore.
- **Prod**: re-run `php artisan config:cache` after changing `config/theme.php` or `THEME_*`.

## Architecture

### Backend (`app/`)

**Controllers** are split by audience:
- `Http/Controllers/Admin/` — Blade-rendered admin pages
- `Http/Controllers/API/V1/` — JSON API consumed by the customer SPA
- `Http/Controllers/API/` — Dashboard, profile, language (shared admin concerns)
- `Http/Controllers/Public/` — Public landing page

**Services** contain business logic:
- `OrderService` — order creation with duplicate-guard (5-second cache window), DB transaction, inventory checks, bulk item insert, FCM token capture
- `CartService` — cart manipulation
- `SystemConfigService` — typed accessors for `system_configs` table; values are cached 24 hours
- `NotificationService` — Firebase Cloud Messaging (FCM) push notifications; requires `FIREBASE_CREDENTIALS` in `.env`
- `ImageService` — GD-based image compression + downscaling on upload (transparent fallback if GD unavailable)

**Models**: `User`, `Customer`, `Order`, `OrderItem`, `Category`, `Product`, `DeliveryZone`, `Language`, `SystemConfig`, `Weight`, `Option`, `OptionValue`

**Jobs**: `SendPushNotificationJob` — queued FCM delivery with 3 retries + exponential backoff.

### Session Isolation (Critical)

The customer SPA API uses a separate session cookie (`customer_spa_session`) from the admin panel (`restaurant_session`). This is enforced by two distinct middleware aliases:

- `customer.start` (`CustomerSession`) — **starts** the customer session by overriding the cookie name to `customer_spa_session` before `StartSession` boots. Must come first.
- `customer.session` (`EnsureCustomerSession`) — **reads** `customer_id` from the active session and attaches the resolved `Customer` model to the request. Guests are allowed through unchanged.

Routes using cart or order placement must include **both** `customer.start` + `customer.session`; do not mix these with admin routes.

### Authentication

- **Admin**: Standard Laravel session auth; roles are `admin` and `moderator` stored on the `users.role` column.
  - `admin` middleware: admin only
  - `moderator` middleware: admin or moderator
- **Customer**: Stateless Bearer token stored in `customers.token` and in `localStorage` as `customer_token`. The `customer.token` middleware (`ResolveCustomerByToken`) resolves the customer from this token.

### User Roles

| Role | Access |
|------|--------|
| `admin` | Full access including users, configs, destructive actions |
| `moderator` | Order management, product CRUD, categories, languages |
| (no role / viewer) | Dashboard and reports only |

### Order Status Flow

**Strict forward lifecycle** (enforced by `Order::ADMIN_TRANSITIONS` + `canTransitionTo()`):

`pending` → `accepted` → `ready` → `delivered` → `completed`

Parallel terminal states:
- `rejected` — admin declines pending order
- `cancelled_by_customer` — customer self-cancels
- `modified` — order superseded by customer modification

The `preparing`, `cancelled`, `cancelled_by_admin` statuses no longer exist (removed in migration `2026_06_17_000000`).

Order numbers format: `ORD-YYYYMMDD-0001` (sequential per day). Order types: `delivery`, `takeaway`, `table` — each gated by the corresponding `core.*` feature flag.

### Product Options

Products support configurable options (e.g., "نوع التقطيع") via `Option` / `OptionValue` models and the `product_option_values` pivot table. Manage options at `/admin/options`. Each option value selected by a customer is stored as `option_name` on `OrderItem`.

### Payment Tracking

Orders carry an authoritative `is_paid` (boolean) + `paid_at` (datetime), added in migration `2026_06_17_100000`. The legacy `payment_status` / `payment_method` columns are **kept and stay in sync** — the dashboard, reports, and SPA still read them, so update both when changing payment state.

### Push Notifications (FCM)

Set `FIREBASE_CREDENTIALS=path/to/firebase-service-account.json` in `.env`. Customers receive push notifications on order status changes (accepted, rejected, ready). The SPA registers FCM tokens via `POST /api/v1/customer/fcm-token` (authenticated) or during order placement. Guest customers can register for notifications before ordering via `POST /api/v1/customer/guest`.

Requires a queue worker: `php artisan queue:work` (or Supervisor in production).

### Multilingual Support

- **Admin panel**: Laravel localization files in `resources/lang/ar/` and `resources/lang/en/`; use `Helpers::translate($key)` in Blade or `__('app.key')`.
- **Customer SPA**: All UI strings are inlined in `customer-spa/src/i18n.js` (no HTTP round-trip); language preference stored in `localStorage` as `spa_lang`.
- Product names/descriptions have per-locale columns: `name_ar`, `name_en`, `description_ar`, `description_en`.
- Admin panel uses RTL Bootstrap when locale is `ar`.

### SystemConfig

Key-value store in the `system_configs` table, grouped (e.g., `general`, `restaurant`, `ordering`). Use `SystemConfigService` for typed access (`getText`, `getBool`, `getJson`, `getNumber`). Values are cached 24 hours — call `SystemConfig::clearCache($key)` after writes (the service's `set()` method handles this automatically).

Dashboard stats (`Order::DASHBOARD_STATS_CACHE_KEY`) are cached separately and auto-cleared on every order `saved`/`deleted` event.

### Feature Flags (config-driven, not DB)

Per-client capability switches that make the product reusable across restaurants. **Distinct from SystemConfig** — these are static, config-file based (no DB hit), resolved once per request.

- **Source of truth**: `config/system_features.php` — each flag is wired to a `FEATURE_*` env var defaulting **`true`** (a fresh install behaves like before; clients opt *out*). A `client_safe` whitelist lists the paths exposed to public frontends.
- **Resolver**: `App\Support\Feature` is the single access point. **All flag reads must go through it** — never call `config('system_features...')` directly.
- **Three surfaces**: `feature('orders.modification')` global helper; `feature_or_fail('orders.modification')` global helper (aborts 403 when flag is off); `@feature('products.options') … @endfeature` Blade directive (registered in `AppServiceProvider`); and `Feature::enabled()` / `Feature::disabled()` static calls.
- **Enforcement is layered**: customer-facing *write* routes are hard-blocked by the `feature:<flag>` route middleware (`FeatureGate`) → 403 when off; services no-op as defense-in-depth; UI always hides disabled features.
- **Frontend exposure**: `PublicSettingsController` adds `features` (= `Feature::clientSafe()`, admin-only flags never leak) to the `/api/v1/settings/public` payload. The SPA reads it via `customer-spa/src/utils/features.js` → `featureEnabled(settings, 'orders.modification')`. Defaults to `true` when the settings haven't loaded yet.
- **`admin.permissions_system` is special**: when off, `AdminMiddleware`/`ModeratorMiddleware` collapse to single-role mode (any authenticated user allowed).
- **Safety rule**: flags gate *new actions and UI*, never *historical data display* — disabling `products.weight_products` after weight orders exist still renders their stored values.
- **Prod**: re-run `php artisan config:cache` after changing the config file or `FEATURE_*` env vars.

**All flags** (grouped, all default `true`):

| Flag path | Env var |
|-----------|---------|
| `core.delivery` | `FEATURE_DELIVERY` |
| `core.takeaway` | `FEATURE_TAKEAWAY` |
| `core.table_ordering` | `FEATURE_TABLE_ORDERING` |
| `products.weight_products` | `FEATURE_WEIGHT_PRODUCTS` |
| `products.options` | `FEATURE_PRODUCT_OPTIONS` |
| `orders.modification` | `FEATURE_ORDER_MODIFICATION` |
| `orders.customer_cancellation` | `FEATURE_ORDER_CANCELLATION_BY_CUSTOMER` |
| `orders.admin_cancel` | `FEATURE_ADMIN_CANCEL_ORDER` |
| `customer.profile` | `FEATURE_CUSTOMER_PROFILE` |
| `customer.order_history` | `FEATURE_ORDER_HISTORY` |
| `notifications.push` | `FEATURE_PUSH_NOTIFICATIONS` |
| `notifications.whatsapp_in_order_view` | `FEATURE_WHATSAPP_IN_ORDER_VIEW` |
| `admin.permissions_system` | `FEATURE_PERMISSIONS_SYSTEM` |
| `localization.languages` | `FEATURE_LANGUAGES` |

The `client_safe` whitelist omits `orders.admin_cancel` and `admin.permissions_system` so they never leak to the SPA.

### Customer SPA (`customer-spa/`)

Single-page React 18 app with no router — state-based page switching (`activePage`: `menu` | `orders` | `profile`). During `npm run dev`, Vite proxies `/api`, `/lang`, and `/language` to `localhost:8000`. The production build outputs to `../public/spa/`.

Key modules:
- `src/api/client.js` — axios instance pre-configured with `baseURL: /api/v1`; interceptor attaches `Authorization: Bearer <token>` and `Accept-Language` headers. Also exports `extractArray`, `extractData`, and `decodeApiText` (handles JSON-escaped Unicode strings from the API).
- `src/hooks/useRestaurantData.js` — the main data hook; fetches settings, categories, products, and delivery zones in parallel via `Promise.allSettled` on mount; exposes `{ settings, categories, allProducts, zones, loading, error }`.
- `src/hooks/useCart.js` — cart state management.
- `src/utils/features.js` — `featureEnabled(settings, 'flag.path')` reads from the `features` key on the settings object; returns `true` when missing (safe default).
- `src/i18n.js` — all UI strings for Arabic and English.

### Admin Panel Features

- **Product import/export**: `GET /admin/products/export` (download), `GET /admin/products/import` (upload form), `POST /admin/products/import` (process), `GET /admin/products/template` (blank template download). Uses `phpoffice/phpspreadsheet`.
- **Log viewer**: `GET /admin/system-secure-metrics-health-logs` — powered by `rap2hpoutre/laravel-log-viewer`; accessible to all authenticated admin users.
- **New order notifications**: The orders index polls `GET /admin/orders?_poll=1` (returns `latest_id` + `pending_count`) to show a banner when new orders arrive without a full page reload.

### API Throttling

All v1 routes: `throttle:60,1` (60 req/min per IP). Per-route overrides (replace, not stack):
- `POST /api/v1/orders` — `throttle:20,1`
- `POST /api/v1/customer/guest` — `throttle:10,1`
- `POST /api/v1/logs` — `throttle:30,1`

### Middleware & Performance

Registered in `bootstrap/app.php` (not `Kernel.php` — this is Laravel 12's slim skeleton):
- `MinifyHtml` is **appended globally to the `web` group** — strips whitespace/comments from HTML responses while preserving `<script>`/`<style>` blocks. Skips JSON and non-HTML responses.
- `cache.headers` is a route-middleware alias (`SetCacheHeaders`) taking an optional max-age: `cache.headers:600`. Adds `Cache-Control: public` + `Vary: Accept-Language` to successful GET/HEAD responses only. The `Vary` is critical — cached product/category payloads are locale-dependent.
- `SetLocale` and `ApiLoggingMiddleware` are appended to the `api` group; `SetLocale` is also on `web`.
- `InjectLogContext` is appended to **both** the `web` and `api` groups, **before** `SetLocale`/`ApiLoggingMiddleware` (see Logging below).

### Logging

**All application logging goes through `App\Services\LogService`** (the global `logService()` helper) — never call the `Log` facade directly. The facade is used in exactly one place, inside `LogService`.

- **Levels**: `info`, `warning`, `error`, `critical` only. `error()`/`critical()` take an optional `?\Throwable $e` third argument; LogService folds a compact, non-sensitive summary (`class`, `message`, `file:line`) into the context — never log full stack traces or request payloads.
- **Event names** use the established dot convention, e.g. `order.create.failed`, `cart.item_added`, `frontend.error`.
- **Shared context is auto-attached**, not repeated per call. `App\Http\Middleware\InjectLogContext` populates Laravel's `Context` facade so **every** log line (app, framework, uncaught exception) carries: `request_id`, `route`, `ip`, `user_agent`, `user_id`. `customer_id` is added by `ResolveCustomerByToken` / `EnsureCustomerSession` once resolved. `order_id` is passed per-call. The `request_id` is also returned as the `X-Request-Id` response header so SPA/browser errors (`POST /api/v1/logs`) correlate to the backend request.
- **Daily rotation**: the default `LOG_CHANNEL=app` is a stack over the `daily` channel (`storage/logs/laravel-YYYY-MM-DD.log`, retention `LOG_DAILY_DAYS`, default 14). The standard line formatter is kept so the log viewer at `/admin/system-secure-metrics-health-logs` still parses it.
- **Telegram alerting (implemented)**: when `LOG_TELEGRAM_ENABLED=true`, the `telegram` channel (`App\Logging\TelegramHandler`) feeds error+critical records to `App\Services\TelegramAlertGate`, which sends only curated categories — **critical exceptions, failed payments (`payment.*`), queue failures (`queue.*`), database failures (`db.*`/`QueryException`/`PDOException`), and fatal/uncaught errors** — to Telegram. Routine `error()` logs stay silent (hybrid rule: every `critical()` alerts; `error()` alerts only on the allowlist). The gate adds **env-awareness** (`config('alerts.environments')`), **dedup** (cache fingerprint), **per-category rate limiting** (`RateLimiter`), and **multi-bot routing** (`config/alerts.php` `bots[]`, each subscribing to categories). Infra categories (queue/database/fatal) send synchronously (queue/DB may be down); others dispatch `SendTelegramAlertJob`. Delivery self-failures log at **warning** (below the floor) so a Telegram outage never loops. Wiring: `Queue::failing` → `queue.job.failed` (AppServiceProvider); `withExceptions` reporter → fatal (bootstrap/app.php). Set `TELEGRAM_BOT_TOKEN`/`TELEGRAM_CHAT_ID`, then `php artisan config:cache`.
- **Prod**: re-run `php artisan config:cache` after changing `config/logging.php` or any `LOG_*` env var.

### Health Checks

- `/up` — Laravel's built-in, public, dependency-free liveness route (configured in `bootstrap/app.php`). **Use this for load balancers / external uptime monitors.**
- `GET /api/health` (`HealthController` → `App\Services\HealthService`) — **admin-session-gated** (registered in `routes/web.php` with `auth`+`admin`) comprehensive report covering **database, cache, queue, storage, disk usage, and PHP/Laravel versions**. Each probe is isolated in its own try/catch and logs failures via `health.*` events. Status codes: `200 ok`; `503 error` when a **critical** subsystem (database/cache/storage) is down; `200 degraded` for capacity warnings (disk usage ≥ `HEALTH_DISK_WARN_PERCENT`, failed jobs > `HEALTH_FAILED_JOBS_WARN`) — see `config/health.php`. Trade-off (by design): a DB outage fails the admin auth here (login redirect, not a clean 503), so automated liveness relies on `/up`.

### Activity Log (business audit trail)

Curated **business** events (who did what), separate from the file-based `LogService`. Written **only** via the `activity()` helper → `App\Services\ActivityLogger` (`activity()->log($action, $subject, $description, $properties, $causer)`), never observers — so only intended events are recorded.

- **Storage**: `activity_logs` table. Polymorphic `causer` (User | Customer | system/null) and `subject` columns carry **no FK** + denormalized `causer_label`/`subject_label`, so rows survive subject/causer deletion (e.g. `product.deleted`). Rows are immutable (no `updated_at`). `request_id`/`ip` pulled from the `Context` facade.
- **Events**: admin actions (`product.created/updated/deleted`, `order.accepted/rejected/ready/delivered/completed/paid`, `settings.updated/created/deleted`, `customer.blocked/unblocked`) and customer-initiated (`order.placed/modified/cancelled`). Action constants live on `App\Models\ActivityLog`.
- **Dashboard**: `GET /admin/activity-logs` (`ActivityLogController`, **admin only**) — searchable/filterable (search, action, date range) + sortable, mirroring the Orders index. Action labels are localized via the `app.activity_actions.*` lang map.
- **Retention**: `activitylog:prune` (scheduled daily) deletes rows older than `ACTIVITYLOG_RETENTION_DAYS` (default 365). Toggle with `ACTIVITYLOG_ENABLED`.

### Version System

Semantic app version (MAJOR.MINOR.PATCH) + upgrade notes, config-only (no DB).

- **Source of truth**: `config/version.php` — committed literal `current` + `released_at` + `releases[]` (newest first). **Read everything through `App\Support\Version`** (`current()`/`releasedAt()`/`releases()`/`latest()`) — never `config('version.*')` directly. Bump it in a PR alongside `CHANGELOG.md` (the developer-facing mirror).
- **Helper**: `app_version()` (in `app/Helpers/functions.php`) → `Version::current()`. Named to avoid confusion with Laravel's framework `app()->version()`.
- **Public endpoint**: `GET /api/v1/version` (`API\V1\VersionController`, throttled with the v1 group) → `{version, released_at}`. Only version + date are public.
- **Health**: `HealthService::report()` `versions` includes `app` alongside `php`/`laravel`.
- **Admin UI**: version badge in the dashboard banner + a `v<x.y.z>` link in the sidebar Account section (app-wide), both linking to **`GET /admin/release-notes`** (`Admin\ReleaseNotesController`, **admin only**) which renders `config('version.releases')`.
- **Prod**: re-run `php artisan config:cache` after bumping `config/version.php`.

### Backups

Automatic backups via **`spatie/laravel-backup`** (`config/backup.php`). One scheduled job bundles the **database** (mysqldump), **uploaded images** (`storage/app/public`), and **`.env`** into a single AES-256 **encrypted** zip (`BACKUP_ARCHIVE_PASSWORD`).

- **Schedule** (`bootstrap/app.php` `withSchedule`): `backup:run` daily 02:00, `backup:clean` daily 01:30. Retention is 7 days (`cleanup.default_strategy.keep_all_backups_for_days = 7`, all longer-term keeps set to 0).
- **Destination**: `BACKUP_DESTINATION_DISK` (default `local` → `storage/app/private`, not web-served). For off-host, add `'s3'` to `config/backup.php` `destination.disks` and set `AWS_*` — the S3 driver is already installed. No code change.
- **mysqldump**: set `DB_DUMP_BINARY_PATH` to the MySQL `bin` dir when it isn't on `PATH` (e.g. Windows dev: `C:/xampp/mysql/bin`) — see `config/database.php` `mysql.dump`.
- **Health → logs**: spatie's success/failure events are bridged to `LogService` (`backup.completed` / `backup.failed`) in `AppServiceProvider` (mail is disabled). This is also the seam for routing failures to the Telegram alert channel.
- **Restore** is manual: unzip (with the password) → import the `db-dumps/*.sql` → copy images back to `storage/app/public` → restore `.env`.
- **Prod**: requires the scheduler running (`php artisan schedule:run` via cron/Supervisor) and a queue worker; re-run `php artisan config:cache` after changing `config/backup.php` or `BACKUP_*`/`AWS_*` env vars.

### Supplementary Docs

Root `README.md` is a stub. The substantive supplementary docs live at the repo root and are indexed by `README_DOCUMENTATION.md`: `IMPLEMENTATION_SUMMARY.md`, `DEBUGGING_GUIDE.md`, `TECHNICAL_FIXES.md`, `TESTING_GUIDE.md` — they focus on the customer home page feature and SPA debugging/testing scenarios. Consult them for that area; this file remains the authoritative architecture overview.
