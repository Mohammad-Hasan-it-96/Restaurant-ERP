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

# Run tests
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

Database: MySQL (`restaurant_erp_db`). Sessions, cache, and queues all use the database driver.

## Architecture

### Backend (`app/`)

**Controllers** are split by audience:
- `Http/Controllers/Admin/` — Blade-rendered admin pages
- `Http/Controllers/API/V1/` — JSON API consumed by the customer SPA
- `Http/Controllers/API/` — Dashboard, profile, language (shared admin concerns)
- `Http/Controllers/Public/` — Public landing page

**Services** contain business logic:
- `OrderService` — order creation with duplicate-guard, DB transaction, inventory checks
- `CartService` — cart manipulation
- `SystemConfigService` — typed accessors for `system_configs` table; values are cached 24 hours

**Models**: `User`, `Customer`, `Order`, `OrderItem`, `Category`, `Product`, `DeliveryZone`, `Language`, `SystemConfig`

### Session Isolation (Critical)

The customer SPA API uses a separate session cookie (`customer_spa_session`) from the admin panel (`restaurant_session`). This is enforced by `CustomerSession` middleware and prevents the customer API from corrupting the admin's auth session. Routes using cart or order placement must include the `customer.start` + `customer.session` middleware group; do not mix these with admin routes.

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

`pending` → `accepted` → `preparing` → `ready` → `delivered` → `completed`

Terminal states: `cancelled`, `cancelled_by_admin`, `cancelled_by_customer`, `rejected`, `modified`

Order numbers format: `ORD-YYYYMMDD-0001` (sequential per day).

### Multilingual Support

- **Admin panel**: Laravel localization files in `resources/lang/ar/` and `resources/lang/en/`; use `Helpers::translate($key)` in Blade or `__('app.key')`.
- **Customer SPA**: All UI strings are inlined in `customer-spa/src/i18n.js` (no HTTP round-trip); language preference stored in `localStorage` as `spa_lang`.
- Product names/descriptions have per-locale columns: `name_ar`, `name_en`, `description_ar`, `description_en`.
- Admin panel uses RTL Bootstrap when locale is `ar`.

### SystemConfig

Key-value store in the `system_configs` table, grouped (e.g., `general`, `restaurant`, `ordering`). Use `SystemConfigService` for typed access (`getText`, `getBool`, `getJson`, `getNumber`). Values are cached 24 hours — call `SystemConfig::clearCache($key)` after writes (the service's `set()` method handles this automatically).

### Customer SPA (`customer-spa/`)

Single-page React 18 app with no router — state-based page switching (`activePage`: `menu` | `orders` | `profile`). During `npm run dev`, Vite proxies `/api`, `/lang`, and `/language` to `localhost:8000`. The production build outputs to `../public/spa/`.

API calls go through `customer-spa/src/api/client.js` (axios instance) which automatically attaches the `Authorization: Bearer <token>` header and `Accept-Language` header from localStorage.

### New Order Notification (Admin)

The orders index page polls `GET /admin/orders?_poll=1` (returns `latest_id` + `pending_count`) to show a banner when new orders arrive without a full page reload.
