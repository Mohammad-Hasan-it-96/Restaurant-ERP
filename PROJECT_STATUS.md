# PROJECT_STATUS.md
> Last updated: May 14, 2026  
> Laravel 12 · PHP 8.2 · React 18 (Vite) · Bootstrap 5.3

---

## 1. CURRENT STATE

A single-restaurant ERP system built with Laravel 12. It consists of:

- **Admin panel** (server-rendered Blade + Bootstrap 5) at `/admin`
- **Customer-facing SPA** (React 18, Vite-built, deployed to `public/spa/`) served at `/`
- **REST API v1** at `/api/v1/*` — 14 clean routes, all legacy Passport code removed

The `HomeController` serves `public/spa/index.html` when built, or falls back to the legacy `resources/views/public/home.blade.php` (1 639-line vanilla JS page) if the React build is absent.

---

## 2. WHAT'S WORKING

### Authentication & Users
- [x] Admin login / logout / password reset
- [x] Two roles: `admin` and `moderator` (enforced via `AdminMiddleware` + `ModeratorMiddleware`)
- [x] Profile edit (name, password, profile picture)
- [x] User management (admin only: create, edit, delete staff accounts)

### Products
- [x] Full CRUD for products (admin + moderator)
- [x] Bilingual names/descriptions (`name_ar`, `name_en`, `description_ar`, `description_en`)
- [x] Price + optional `discount_price` (effective price accessor on model)
- [x] `is_active`, `is_available`, `is_featured`, `sort_order` toggles
- [x] Product image upload to storage
- [x] Filter by category + search in admin list; sortable columns
- [x] Public API: `GET /api/v1/products` (filters: `category_id`, `featured`, `search`, pagination)
- [ ] **⚠️ BROKEN**: Excel export / import / download template — `Admin\ProductController` delegates these three actions to `App\Http\Controllers\API\ProductController` (lines 188, 198, 203), which was **deleted** during Passport cleanup. Visiting `/admin/products/export`, `/admin/products/template`, or posting to `/admin/products/import` throws a fatal `BindingResolutionException` at runtime.

### Categories
- [x] Full CRUD (admin + moderator)
- [x] Self-referencing parent → children (nested categories)
- [x] Bilingual names, image, sort order, active toggle
- [x] Public API: `GET /api/v1/categories`

### Customers
- [x] Auto-created via `Customer::firstOrCreate(['phone'])` on first order (no registration)
- [x] Admin list: search by name/phone, pagination, sortable columns
- [x] Admin profile view: order history, total spent, last order
- [x] Block / Unblock with reason
- [x] `EnsureCustomerSession` middleware: binds `customer_id` to session after first order
- [x] `GET /api/v1/customer/me` — returns name + phone for auto-fill
- [x] Auto-fill checkout fields from server session, falling back to `localStorage`

### Orders
- [x] Order placement via `POST /api/v1/orders` (guest, no auth)
- [x] All three types: `table`, `delivery` (immediate + scheduled), `takeaway`
- [x] Full status workflow: `pending` → `accepted` → `preparing` → `ready` → `delivered` → `completed`
- [x] Also: `rejected`, `cancelled_by_admin`, `cancelled_by_customer`
- [x] **Accept modal — delivery orders**: shows customer address, all active delivery zones (name + fee as read-only reference table), manual delivery fee input
- [x] **Accept modal — dine-in / takeaway**: simple confirmation only (no fee input, no zones table)
- [x] Admin: reject (with reason preset/custom), cancel, force-complete
- [x] Admin: `Preparing` → `Ready` → `Delivered` → `Completed` PATCH status buttons with transition guards
- [x] Admin: **Mark as Paid** button with payment method selection (cash / card / online)
- [x] Admin: payment status badge in order list
- [x] Admin: **invoice/print view** per order — standalone 80 mm thermal receipt page with full `@media print` CSS
- [x] Admin: **WhatsApp notification buttons** — pre-filled bilingual (AR/EN) message per status opens `wa.me/{phone}` in new tab
- [x] Unique order number: `ORD-YYYYMMDD-NNNN`
- [x] Duplicate-order guard: same phone + items + type within 5 s blocked via Cache
- [x] Opening hours enforcement: configurable per-day from/to + global kill-switch
- [x] Customer cancellation: `POST /api/v1/orders/{order_number}/cancel`
- [x] Order tracking: `GET /api/v1/orders/{order_number}`
- [x] Admin order list: status tabs, search, sortable columns, pagination

### Session Isolation
- [x] `CustomerSession` middleware overrides `session.cookie` to `customer_spa_session`
- [x] Admin panel uses `restaurant_session` cookie (set via `SESSION_COOKIE` in `.env`)
- [x] **Three former `CustomerSession` route groups merged into one** in `api.php`
- [x] Placing an order no longer corrupts or invalidates the admin's authenticated session

### Cart
- [x] Session-based server cart (`CartService`) + `localStorage` client cart in sync
- [x] Add, update (quantity), remove, clear; product availability checked on every mutation
- [x] `GET/POST /api/v1/cart/*` (isolated via `CustomerSession` middleware)

### Delivery Zones
- [x] Full CRUD in admin (sortable columns)
- [x] `GET /api/v1/delivery-zones`; `estimated_fee` shown in checkout
- [x] Active zones shown as reference table inside the Accept Order modal (delivery orders only)

### System Config
- [x] Key-value config store with grouping (`system_configs` table); 24 h cached reads
- [x] Two active groups:
  - `general` → `site_name`, `restaurant_name_ar`, `restaurant_name_en`
  - `restaurant` → `restaurant_logo`, `restaurant_phone`, `restaurant_whatsapp`, `is_accepting_orders`, `customer_cancel_before_minutes`, `order_closed_message`, `delivery_note`, `opening_hours`, `rejection_reasons`
- [x] `SystemConfigService`: `get`, `set`, `getBool`, `getNumber`, `getJson`, `getText`, `getFirstText`, `isOpenAt()`
- [x] Admin config UI — **General** group: editable Arabic + English restaurant names (correct `dir` attributes)
- [x] Admin config UI — **Restaurant** group: logo upload with preview, opening hours per-day UI (toggle + time pickers), rejection reasons (dynamic add/remove list), boolean select, number input
- [x] Group-level cache clearing after save; config sidebar reduced to 3 links (All · Restaurant · General)

### Public Settings API
- [x] `GET /api/v1/settings/public` → `restaurant_name` (AR), `restaurant_name_en`, `restaurant_logo` (full URL), `restaurant_phone`, `restaurant_whatsapp`, `opening_hours`, `is_open_now`, `is_accepting_orders`, `delivery_note`

### Customer SPA (`/`)
- [x] **React 18 + Vite** built to `public/spa/`, served by `HomeController`
- [x] Locale-aware restaurant name: Arabic locale → `restaurant_name`, English → `restaurant_name_en`
- [x] Restaurant logo in header (icon fallback when no logo set)
- [x] Open / closed status banner
- [x] Category tabs (root + subcategory pills) with skeleton loaders
- [x] Product cards: bilingual name/description, price, image, availability badge
- [x] Product detail modal; bilingual search across `name_ar` + `name_en`
- [x] Cart off-canvas + checkout modal with per-type field validation
- [x] Submit spinner + double-submit guard; success modal with order number
- [x] Customer info auto-fill from session / `localStorage`; saved after successful order
- [x] My Orders page (`OrderHistory`) — past orders stored in `localStorage`
- [x] Bottom nav bar (menu / cart / orders)
- [x] Language toggle (AR ↔ EN) — persists to `localStorage` + sets Laravel session via `/lang/{locale}`
- [x] RTL / LTR switches automatically; frontend error logging → `POST /api/v1/logs/frontend`

### Admin Dashboard
- [x] Restaurant branding banner: logo + locale-aware name (Arabic or English based on admin's language)
- [x] Today's orders, today's sales, total customers, pending orders stat cards
- [x] Last-7-days orders bar chart + order-type doughnut (Chart.js)
- [x] Recent 8 orders table

### Admin Navbar & Sidebar
- [x] Navbar: restaurant logo (or icon fallback) + locale-aware restaurant name from `system_configs`
- [x] Sidebar: user profile (avatar, name, email, role badge); pending-orders badge on Orders link
- [x] Restaurant branding shown **once** (navbar only) — removed duplicate from sidebar

### Invoice / Thermal Print
- [x] `admin/orders/{order}/invoice` — standalone page (not in `layouts/app`)
- [x] Full thermal receipt CSS: `@page { size: 80mm auto; margin: 0 }`, monospace 11–14 px, dashed `hr` dividers, centered logo header, black-on-white, all backgrounds/shadows stripped for print
- [x] Print button calls `window.print()`; "← Back" link hidden when printing via `.no-print`

### API Logging
- [x] `ApiLoggingMiddleware` **wired up** — appended to all API requests via `bootstrap/app.php` (`$middleware->api(append: [ApiLoggingMiddleware::class])`)

### Reports
- [x] Date range filter, revenue cards, bar chart, doughnut, top products table, orders-by-status, paginated list, CSV export

### Real-Time Admin Notifications
- [x] HTTP long-poll every 10 s (`GET /admin/orders?_poll=1`)
- [x] New-order banner + audio beep (`public/sounds/notification.wav`)
- [x] Tab title blinks "🔔 طلب جديد!"; toggle auto-refresh; test sound button

### Internationalisation
- [x] Arabic (`ar`) + English (`en`) — full `app.php` translation files
- [x] RTL layout auto-applied when locale is `ar`; language switcher in admin navbar + SPA header
- [x] Locale-aware name display in admin navbar, dashboard banner, and customer SPA header

---

## 3. KNOWN BUGS

| # | Severity | Issue | File |
|---|----------|-------|------|
| 1 | **🔴 Critical** | **Excel export / import / template broken** — `Admin\ProductController::export()`, `downloadTemplate()`, `processImport()` call `app(\App\Http\Controllers\API\ProductController::class)` (lines 188, 198, 203), but that class was deleted during Passport cleanup. Visiting any of the three routes throws `BindingResolutionException`. **Fix**: move the Excel logic into a `ProductExcelService` and update the three delegating methods. | `app/Http/Controllers/Admin/ProductController.php` |
| 2 | Low | New-order notification banner text in `orders/index.blade.php` is hardcoded Arabic, not behind a translation key. | `resources/views/admin/orders/index.blade.php` |
| 3 | Low | `resources/views/public/home.blade.php` is a 1 639-line legacy vanilla JS SPA, served only as fallback. Safe to delete once React SPA is confirmed always present in production. | `resources/views/public/home.blade.php` |

---

## 4. WHAT'S MISSING / TODO

### High Priority
| # | Feature | Notes |
|---|---------|-------|
| 1 | **Fix Excel import/export** | Move `processImport`, `export`, `downloadTemplate` logic out of the deleted `API\ProductController` into a new `ProductExcelService`; update the 3 delegating methods in `Admin\ProductController` |
| 2 | **Automated tests** | Feature tests: place order, cancel order, cart mutations, `isOpenAt()`, `CustomerSession` isolation |

### Medium Priority
| # | Feature | Notes |
|---|---------|-------|
| 3 | **WhatsApp auto-send** | Admin clicks a pre-filled link manually. No auto-dispatch on order arrival. Could use CallMeBot or Twilio. |
| 4 | **WebSocket / real-time push** | Admin polls every 10 s. No true push (Laravel Echo / Pusher / Soketi). |
| 5 | **Product modifiers / add-ons** | No sizes, variants, extra toppings, combos. |
| 6 | **Inventory / stock tracking** | `quantity` column exists on `products`; stock check is skipped in `OrderService`. |
| 7 | **Takeaway scheduled pickup** | Only `delivery` supports `scheduled_at`. Takeaway is always immediate. |

### Low Priority
| # | Feature | Notes |
|---|---------|-------|
| 8 | **SPA build automation** | `public/spa/` must be manually rebuilt after JS changes (`cd customer-spa && npm run build`). No CI/CD hook. |
| 9 | **Delete legacy `public/home.blade.php`** | 1 639-line vanilla JS fallback. Delete once React SPA is confirmed stable in production. |
| 10 | **Multi-image per product** | Only one `image` field. |
| 11 | **Table management** | Table number is free text; no floor plan or reservation. |

---

## 5. FILE STRUCTURE

```
Restaurant-ERP/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ConfigController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── DeliveryZoneController.php
│   │   │   │   ├── OrderController.php         ← full workflow; passes $deliveryZones to show view
│   │   │   │   ├── ProductController.php        ← ⚠️ export/import/template delegate to deleted class
│   │   │   │   └── ReportController.php
│   │   │   ├── API/
│   │   │   │   ├── BaseController.php
│   │   │   │   ├── DashboardController.php      ← admin dashboard + locale-aware branding
│   │   │   │   ├── LanguageController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── V1/
│   │   │   │       ├── ApiResponse.php          ← trait: success() / error() helpers
│   │   │   │       ├── CartController.php
│   │   │   │       ├── CategoryController.php
│   │   │   │       ├── CustomerController.php
│   │   │   │       ├── DeliveryZoneController.php
│   │   │   │       ├── FrontendLogController.php
│   │   │   │       ├── OrderController.php
│   │   │   │       ├── ProductController.php
│   │   │   │       └── PublicSettingsController.php
│   │   │   ├── AuthController.php
│   │   │   └── Public/HomeController.php        ← serves public/spa/index.html (or blade fallback)
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php
│   │       ├── ApiLoggingMiddleware.php          ← ✅ registered in bootstrap/app.php
│   │       ├── Authenticate.php
│   │       ├── CustomerSession.php              ← isolates SPA session from admin session
│   │       ├── EnsureCustomerSession.php
│   │       ├── ModeratorMiddleware.php
│   │       ├── RedirectIfAuthenticated.php
│   │       └── SetLocale.php
│   ├── Models/
│   │   ├── Category.php
│   │   ├── Customer.php
│   │   ├── DeliveryZone.php                     ← active() scope
│   │   ├── Language.php
│   │   ├── Order.php                            ← STATUS_* / TYPE_* / PAYMENT_* constants
│   │   ├── OrderItem.php
│   │   ├── Product.php                          ← effectivePrice, displayName accessors
│   │   ├── SystemConfig.php
│   │   └── User.php
│   ├── Policies/ProductPolicy.php
│   ├── Providers/AppServiceProvider.php, AuthServiceProvider.php
│   └── Services/
│       ├── CartService.php
│       ├── OrderService.php
│       └── SystemConfigService.php
├── bootstrap/app.php                            ← ApiLoggingMiddleware appended to api group
├── customer-spa/                                ← React 18 + Vite SPA
│   ├── src/
│   │   ├── App.jsx
│   │   ├── i18n.js                              ← AR+EN strings, switchLanguage()
│   │   ├── components/
│   │   │   ├── Header.jsx                       ← locale-aware name: en→name_en, ar→name_ar
│   │   │   └── … (other components)
│   │   ├── hooks/
│   │   │   ├── useCart.js
│   │   │   └── useRestaurantData.js             ← decodes restaurant_name + restaurant_name_en
│   │   └── utils/format.js, myOrders.js
│   └── vite.config.js                           ← builds to ../public/spa/
├── database/
│   ├── migrations/                              ← 25 migrations, all ran
│   └── seeders/SystemConfigSeeder.php           ← single source of truth for all config keys
├── resources/
│   ├── lang/ar/app.php + en/app.php             ← full bilingual translation files
│   └── views/
│       ├── admin/configs/ (index, group)
│       ├── admin/orders/
│       │   ├── index.blade.php
│       │   ├── show.blade.php
│       │   ├── invoice.blade.php                ← standalone 80 mm thermal receipt page
│       │   └── partials/
│       │       ├── modals.blade.php             ← accept (delivery vs dine-in), reject, cancel, mark-paid
│       │       ├── status-badge.blade.php
│       │       └── whatsapp-notify.blade.php
│       ├── layouts/app.blade.php                ← navbar (branding) + sidebar (user profile)
│       ├── public/home.blade.php                ← ⚠️ legacy 1 639-line vanilla JS fallback
│       └── dashboard.blade.php
├── routes/
│   ├── api.php                                  ← 14 clean v1 routes; single CustomerSession group
│   └── web.php                                  ← 62 admin + public + auth routes
└── public/
    ├── spa/                                     ← built React SPA
    └── sounds/notification.wav
```

---

## 6. DATABASE STATUS

| Table | Key Columns / Notes |
|-------|---------------------|
| `users` | id, name, email, password, role (admin/moderator), profile_picture |
| `languages` | id, name, code, flag_path, status, is_default, direction |
| `products` | id, name_ar, name_en, description_ar/en, price, discount_price, image, category_id, is_available, is_featured, is_active, sort_order, quantity |
| `system_configs` | id, key, value, group — 12 active keys (see §7) |
| `categories` | id, name_ar, name_en, image, parent_id, sort_order, is_active |
| `customers` | id, full_name, phone (unique), default_address, is_blocked, blocked_reason |
| `delivery_zones` | id, area_name, estimated_fee, sort_order, is_active |
| `orders` | id, order_number, customer_id, order_type, table_number, address, delivery_type, scheduled_at, status, subtotal, estimated_delivery_fee, delivery_fee, discount, total, payment_status, payment_method, customer_note, rejection_reason, accepted_at, completed_at, cancelled_at |
| `order_items` | id, order_id, product_id, product_name, product_price, quantity, total |
| `cache`, `jobs` | Standard Laravel tables |

**Total: 11 active tables** — 5 `oauth_*` tables dropped via migration `2026_05_14_134844_drop_oauth_tables`  
**All 25 migrations: ran** — no pending migrations.

---

## 7. SYSTEM CONFIG KEYS

| Group | Key | Type | Notes |
|-------|-----|------|-------|
| `general` | `site_name` | text | Fallback site name |
| `general` | `restaurant_name_ar` | text RTL | Arabic restaurant name — editable in admin General config |
| `general` | `restaurant_name_en` | text LTR | English restaurant name — editable in admin General config |
| `restaurant` | `restaurant_logo` | storage path | Logo; upload via admin Restaurant config; displayed in SPA + admin navbar/dashboard |
| `restaurant` | `restaurant_phone` | text | Customer-facing phone |
| `restaurant` | `restaurant_whatsapp` | text | e.g. `+963983820430` |
| `restaurant` | `is_accepting_orders` | bool | Global order kill-switch |
| `restaurant` | `customer_cancel_before_minutes` | number | Cancel window before scheduled delivery |
| `restaurant` | `order_closed_message` | text | Shown in SPA when orders closed |
| `restaurant` | `delivery_note` | text | Shown in SPA checkout |
| `restaurant` | `opening_hours` | JSON | Per-day `{is_open, from, to}` |
| `restaurant` | `rejection_reasons` | JSON array | Admin rejection reason presets |

---

## 8. API ENDPOINTS

### Public V1 (throttle: 60/min)
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/settings/public` | Restaurant settings, open/closed |
| GET | `/api/v1/categories` | Active categories |
| GET | `/api/v1/products` | Products with filters |
| GET | `/api/v1/delivery-zones` | Active zones |
| POST | `/api/v1/logs/frontend` | Client error logging (30/min) |

### CustomerSession middleware group (isolated `customer_spa_session` cookie)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/orders` | Place order (extra strict: 10/min) |
| GET | `/api/v1/orders/{number}` | Track order |
| POST | `/api/v1/orders/{number}/cancel` | Cancel order |
| GET | `/api/v1/customer/me` | Guest profile auto-fill |
| GET | `/api/v1/cart` | Get cart |
| POST | `/api/v1/cart/add` | Add to cart |
| POST | `/api/v1/cart/update` | Update quantity |
| POST | `/api/v1/cart/remove` | Remove item |
| POST | `/api/v1/cart/clear` | Clear cart |

### Admin Web Routes (`auth` middleware)
| Path | Role | Description |
|------|------|-------------|
| `/admin/dashboard` | any | Dashboard + stats |
| `/admin/products/*` | moderator | CRUD (**export/import: ⚠️ broken**) |
| `/admin/categories/*` | moderator | Category CRUD |
| `/admin/orders/*` | any/mod | Full workflow + WhatsApp + invoice |
| `/admin/customers/*` | any | Customer list + block/unblock |
| `/admin/delivery-zones/*` | moderator | Zone CRUD |
| `/admin/configs/*` | admin | System config edit |
| `/admin/reports` | any | Reports + CSV export |
| `/admin/users/*` | admin | Staff management |
| `/admin/languages/*` | moderator | Language management |
| `/admin/profile/*` | any | Own profile |

---

## 9. NEXT ACTIONS (prioritised)

### Fix now (critical bug)
1. **Fix Excel export / import / template** — `Admin\ProductController` delegates these 3 actions to the deleted `App\Http\Controllers\API\ProductController`. Options:
   - Create `App\Services\ProductExcelService` with the full Spreadsheet logic and inject it into `Admin\ProductController`
   - Or inline the logic directly into the 3 methods in `Admin\ProductController`

### Soon (new features)
2. **Automated tests** — Feature tests for: place order, cancel order, cart CRUD, `isOpenAt()`, session isolation, accept delivery vs dine-in modal behaviour
3. **WhatsApp auto-send** — Auto-dispatch on new order arrival (CallMeBot / Twilio / meta API)
4. **WebSocket push** — Replace 10 s poll with Laravel Echo + Pusher/Soketi

### When ready (polish)
5. **Delete `public/home.blade.php`** once React SPA is confirmed always present in production
6. **SPA build automation** — Add deploy script: `cd customer-spa && npm ci && npm run build`
7. **Product modifiers** — `product_options` JSON column + UI for sizes/extras
8. **Inventory/stock** — Enable `quantity` column check in `OrderService` when placing orders
