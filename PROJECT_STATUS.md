# PROJECT_STATUS.md
> Last updated: May 14, 2026  
> Laravel 12 · PHP 8.2 · React 18 (Vite) · Bootstrap 5.3

---

## 1. CURRENT STATE

A single-restaurant ERP system built with Laravel 12. It consists of:

- **Admin panel** (server-rendered Blade + Bootstrap 5) at `/admin`
- **Customer-facing SPA** (React 18, Vite-built, deployed to `public/spa/`) served at `/`
- **REST API v1** at `/api/v1/*` consumed by the customer SPA
- **Legacy API** at `/api/*` (Passport-based — dead code, still present, see §8)

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
- [x] Excel import (`phpoffice/phpspreadsheet`) + export + download template
- [x] Filter by category + search in admin list; sortable columns
- [x] Public API: `GET /api/v1/products` (filters: `category_id`, `featured`, `search`, pagination)

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
- [x] Admin: accept (with optional delivery fee override), reject (with reason), cancel, complete
- [x] Admin: `Preparing` → `Ready` → `Delivered` → `Completed` status buttons with transition guards
- [x] Admin: **Mark as Paid** button with payment method selection (cash / card / online)
- [x] Admin: payment status badge in order list
- [x] Admin: invoice/print view per order
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
- [x] Placing an order no longer corrupts or invalidates the admin's authenticated session

### Cart
- [x] Session-based server cart (`CartService`) + `localStorage` client cart in sync
- [x] Add, update (quantity), remove, clear; product availability checked on every mutation
- [x] `GET/POST /api/v1/cart/*` (isolated via `CustomerSession` middleware)

### Delivery Zones
- [x] Full CRUD in admin (sortable columns)
- [x] `GET /api/v1/delivery-zones`; `estimated_fee` shown in checkout

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

## 3. WHAT'S MISSING / TODO

### High Priority
| # | Feature | Notes |
|---|---------|-------|
| 1 | **Legacy API / Passport cleanup** | `POST /api/register`, `POST /api/login`, `GET/PUT/DELETE /api/products` + `RegisterController` + legacy `ProductController` + 5 `oauth_*` tables are all dead code. Active attack surface. **→ Remove.** |
| 2 | **Automated tests** | `Feature/` + `Unit/` directories exist but empty. Need coverage for: place order, cancel order, cart mutations, `isOpenAt()`, `CustomerSession` isolation. |
| 3 | **WhatsApp auto-send** | Admin clicks a pre-filled link manually. No auto-dispatch on order arrival. Could use CallMeBot or Twilio. |
| 4 | **WebSocket / real-time push** | Admin polls every 10 s. No true push (Laravel Echo / Pusher / Soketi). |

### Medium Priority
| # | Feature | Notes |
|---|---------|-------|
| 5 | **Thermal / POS print layout** | Invoice view exists; needs `@media print` CSS for 58/80 mm receipt printers. |
| 6 | **Product modifiers / add-ons** | No sizes, variants, extra toppings, combos. |
| 7 | **Inventory / stock tracking** | `quantity` column exists; stock check skipped in `OrderService`. |
| 8 | **Takeaway scheduled pickup** | Only `delivery` supports `scheduled_at`. Takeaway is always immediate. |

### Low Priority
| # | Feature | Notes |
|---|---------|-------|
| 9 | **`ApiLoggingMiddleware` orphan** | Exists but registered nowhere — wire it up or delete it. |
| 10 | **SPA build automation** | `public/spa/` must be manually rebuilt after JS changes (`cd customer-spa && npm run build`). No CI/CD hook. |
| 11 | **Delete legacy `public/home.blade.php`** | 1 639-line vanilla JS fallback. Once React SPA is stable in production, this can be removed. |
| 12 | **Multi-image per product** | Only one `image` field. |
| 13 | **Table management** | Table number is free text; no floor plan or reservation. |

---

## 4. FILE STRUCTURE

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
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── API/
│   │   │   │   ├── DashboardController.php   ← serves admin dashboard view
│   │   │   │   ├── ProductController.php     ← ⚠️ legacy (Passport)
│   │   │   │   ├── RegisterController.php    ← ⚠️ legacy (Passport)
│   │   │   │   └── V1/
│   │   │   │       ├── CartController.php
│   │   │   │       ├── CategoryController.php
│   │   │   │       ├── CustomerController.php
│   │   │   │       ├── DeliveryZoneController.php
│   │   │   │       ├── FrontendLogController.php
│   │   │   │       ├── OrderController.php
│   │   │   │       ├── ProductController.php
│   │   │   │       └── PublicSettingsController.php
│   │   │   └── Public/HomeController.php     ← serves public/spa/index.html (or blade fallback)
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php
│   │       ├── ApiLoggingMiddleware.php       ← ⚠️ not registered anywhere
│   │       ├── CustomerSession.php           ← isolates SPA session from admin session
│   │       ├── EnsureCustomerSession.php
│   │       ├── ModeratorMiddleware.php
│   │       └── SetLocale.php
│   └── Services/
│       ├── CartService.php
│       ├── OrderService.php
│       └── SystemConfigService.php
├── customer-spa/                             ← React 18 + Vite SPA
│   ├── src/
│   │   ├── App.jsx
│   │   ├── i18n.js                           ← AR+EN strings, switchLanguage()
│   │   ├── components/
│   │   │   ├── Header.jsx                    ← locale-aware restaurant name + logo
│   │   │   └── … (12 other components)
│   │   ├── hooks/
│   │   │   ├── useCart.js
│   │   │   └── useRestaurantData.js
│   │   └── utils/format.js, myOrders.js
│   └── vite.config.js                        ← builds to ../public/spa/
├── database/
│   ├── migrations/                           ← 25 migrations, all ran
│   └── seeders/
│       └── SystemConfigSeeder.php            ← single source of truth for all configs
├── resources/
│   ├── lang/ar/ + en/
│   └── views/
│       ├── admin/configs/ (index, group)      ← rich config edit UI
│       ├── admin/orders/  (index, show, invoice, partials/)
│       ├── layouts/app.blade.php             ← navbar + sidebar with restaurant branding
│       ├── public/home.blade.php             ← ⚠️ legacy 1639-line vanilla JS fallback
│       └── dashboard.blade.php
├── routes/api.php, web.php
└── public/
    ├── spa/                                  ← built React SPA
    └── sounds/notification.wav
```

---

## 5. DATABASE STATUS

| Table | Key Columns / Notes |
|-------|---------------------|
| `users` | id, name, email, password, role (admin/moderator), profile_picture |
| `languages` | id, name, code, flag_path, status, is_default, direction |
| `products` | id, name_ar, name_en, description_ar/en, price, discount_price, image, category_id, is_available, is_featured, is_active, sort_order, quantity |
| `system_configs` | id, key, value, group — 12 active keys (see §6) |
| `categories` | id, name_ar, name_en, image, parent_id, sort_order, is_active |
| `customers` | id, full_name, phone (unique), default_address, is_blocked, blocked_reason |
| `delivery_zones` | id, area_name, estimated_fee, sort_order, is_active |
| `orders` | id, order_number, customer_id, order_type, address, delivery_type, scheduled_at, status, subtotal, delivery_fee, total, payment_status, payment_method, rejection_reason |
| `order_items` | id, order_id, product_id, product_name, product_price, quantity, total |
| `oauth_*` (5 tables) | ⚠️ **Unused** — Passport dead code; safe to drop |
| `cache`, `jobs` | Standard Laravel tables |

**Total: 16 tables** (5 unused `oauth_*`)  
**All 25 migrations: ran** — no pending migrations.

---

## 6. SYSTEM CONFIG KEYS

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

## 7. API ENDPOINTS

### ⚠️ Legacy — remove
| Method | Path | Notes |
|--------|------|-------|
| POST | `/api/register` | Passport — dead |
| POST | `/api/login` | Passport — dead |
| * | `/api/products` | Passport — dead |

### Public V1 (throttle: 60/min)
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/settings/public` | Restaurant settings, open/closed |
| GET | `/api/v1/categories` | Active categories |
| GET | `/api/v1/products` | Products with filters |
| GET | `/api/v1/delivery-zones` | Active zones |
| POST | `/api/v1/logs/frontend` | Client error logging (30/min) |

### CustomerSession middleware group
| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/orders` | Place order (10/min) |
| GET | `/api/v1/orders/{number}` | Track order |
| POST | `/api/v1/orders/{number}/cancel` | Cancel order |
| GET | `/api/v1/customer/me` | Guest profile auto-fill |
| GET/POST | `/api/v1/cart/*` | Cart CRUD |

### Admin Web Routes (`auth` middleware)
| Path | Role | Description |
|------|------|-------------|
| `/admin/dashboard` | any | Dashboard |
| `/admin/products/*` | moderator | Product CRUD + import/export |
| `/admin/categories/*` | moderator | Category CRUD |
| `/admin/orders/*` | any/mod | Full order workflow + WhatsApp |
| `/admin/customers/*` | any | Customer list + block |
| `/admin/delivery-zones/*` | moderator | Zone CRUD |
| `/admin/configs/*` | admin | System config group edit |
| `/admin/reports` | any | Reports + CSV export |
| `/admin/users/*` | admin | Staff management |
| `/admin/profile/*` | any | Own profile |

---

## 8. KNOWN ISSUES

| # | Severity | Issue |
|---|----------|-------|
| 1 | **High** | Legacy Passport routes (`/api/register`, `/api/login`, `/api/products`) still active — dead code and unnecessary attack surface. `RegisterController`, legacy `ProductController`, and 5 `oauth_*` tables all need removing. |
| 2 | Medium | Three separate `Route::middleware([CustomerSession, 'customer.session'])` groups in `api.php` (orders, customer/me, cart) should be merged into one. |
| 3 | Medium | `ApiLoggingMiddleware` exists but is registered nowhere. Wire it up to the v1 group or delete it. |
| 4 | Low | New-order notification banner text in `orders/index.blade.php` is hardcoded Arabic — not behind a translation key. |
| 5 | Low | `resources/views/public/home.blade.php` is a 1 639-line legacy vanilla JS SPA used only as a fallback. Delete once React SPA is confirmed stable in production. |
| 6 | Low | No `@media print` CSS on the invoice view — unusable on 58/80 mm thermal printers. |

---

## 9. NEXT ACTIONS (prioritised)

### Do now (clean up debt)
1. **Remove legacy Passport code** — delete `RegisterController`, legacy `ProductController` (in `API/`), the 3 dead routes in `api.php`, and write a migration to `dropIfExists` the 5 `oauth_*` tables
2. **Merge the 3 `CustomerSession` route groups** in `api.php` into one group
3. **Register or delete `ApiLoggingMiddleware`**

### Soon (new features)
4. **Automated tests** — Feature tests for place order, cancel order, cart CRUD, `isOpenAt()`, session isolation
5. **Thermal print CSS** — `@media print` stylesheet in `orders/invoice.blade.php` for receipt printers
6. **WhatsApp auto-send** — Auto-open or background-send a WhatsApp message on new order (CallMeBot / Twilio)

### When ready (polish)
7. **Delete `public/home.blade.php`** once React SPA is confirmed always present
8. **SPA build automation** — Add deploy script: `cd customer-spa && npm ci && npm run build`
9. **Product modifiers** — `product_options` JSON column + UI for sizes/extras
