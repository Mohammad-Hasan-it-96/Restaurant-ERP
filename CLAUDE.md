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
php artisan db:seed   # creates admin@Alghadeer.com / password + system configs
```

Database: MySQL (`restaurant_erp_db`). Sessions, cache, and queues all use the database driver. **Tests also run against MySQL** — the SQLite/in-memory lines in `phpunit.xml` are commented out.

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

### Health Checks

- `GET /api/health` (`HealthController`) — session-less, unthrottled probe that runs `select 1`; returns 200 `{status: ok}` or 503 when the DB is unreachable. Exempt from `SetLocale` and `ApiLoggingMiddleware`.
- `/up` — Laravel's built-in health route (configured in `bootstrap/app.php`).
