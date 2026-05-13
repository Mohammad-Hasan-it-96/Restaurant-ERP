# PROJECT_STATUS.md
> Last updated: May 14, 2026  
> Laravel 12 · PHP 8.2 · Bootstrap 5.3 · Vanilla JS  

---

## 1. CURRENT STATE

A single-restaurant ERP system built with Laravel 12. It consists of:

- **Admin panel** (server-rendered Blade + Bootstrap 5) accessible at `/admin`
- **Customer-facing SPA** (single Blade page, ~1 600 lines of vanilla JS) at `/`
- **REST API v1** at `/api/v1/*` consumed exclusively by the customer SPA
- **Legacy API** at `/api/*` (Passport-based, pre-dates the restaurant module — still present)

---

## 2. WHAT'S WORKING

### Authentication & Users
- [x] Admin login / logout / password reset
- [x] Two roles: `admin` and `moderator` (enforced via `AdminMiddleware` + `ModeratorMiddleware`)
- [x] Profile edit (name, password, profile picture migration exists)
- [x] User management (admin only: create, edit, delete staff accounts)

### Products
- [x] Full CRUD for products (admin + moderator)
- [x] Bilingual product names/descriptions (`name_ar`, `name_en`, `description_ar`, `description_en`)
- [x] Price + optional `discount_price` (effective price accessor)
- [x] `is_active`, `is_available`, `is_featured`, `sort_order` toggles
- [x] Product image upload to storage
- [x] Excel import (`phpoffice/phpspreadsheet`) + export + download template
- [x] Filter by category + search in admin list
- [x] Public API: `GET /api/v1/products` with filters: `category_id`, `featured`, `search`, pagination

### Categories
- [x] Full CRUD (admin + moderator)
- [x] Self-referencing parent → children (nested categories)
- [x] Bilingual names (`name_ar`, `name_en`), image, sort order, active toggle
- [x] Public API: `GET /api/v1/categories`

### Customers
- [x] Auto-created via `Customer::firstOrCreate(['phone'])` on first order (no registration)
- [x] Admin list: search by name/phone, pagination, order count/total spent
- [x] Admin profile view: order history, total spent, last order
- [x] Block / Unblock with reason
- [x] `EnsureCustomerSession` middleware: binds `customer_id` to session after first order
- [x] `GET /api/v1/customer/me` — returns name + phone for auto-fill from server session
- [x] Auto-fill checkout fields from server session, falling back to `localStorage`

### Orders
- [x] Order placement via `POST /api/v1/orders` (guest, no auth)
- [x] All three types: `table`, `delivery` (immediate + scheduled), `takeaway`
- [x] Statuses: `pending`, `accepted`, `rejected`, `cancelled_by_admin`, `cancelled_by_customer`, `completed`
- [x] Admin: accept (with optional delivery fee override), reject (with reason), cancel, complete
- [x] Admin: invoice/print view per order
- [x] Unique order number: `ORD-YYYYMMDD-NNNN`
- [x] Duplicate-order guard: same phone + items + type within 5 s blocked via Cache
- [x] Opening hours enforcement: configurable per-day from/to window + global kill-switch
- [x] Customer cancellation: `POST /api/v1/orders/{order_number}/cancel` (pending only; scheduled delivery respects cancel window)
- [x] Order tracking: `GET /api/v1/orders/{order_number}`
- [x] Admin order list: status tabs, search by order number / customer name / phone, auto-pagination

### Cart
- [x] Session-based server cart (`CartService`) + `localStorage` client cart — in sync
- [x] Add, update (quantity), remove, clear
- [x] Product availability validated on every mutation
- [x] `GET/POST /api/v1/cart/*` endpoints (session + `customer.session` middleware)
- [x] Cart persists across page reloads via `localStorage` key `restaurant_cart_v1`

### Delivery Zones
- [x] Full CRUD in admin
- [x] `GET /api/v1/delivery-zones` — consumed by checkout dropdown
- [x] `estimated_fee` sent to backend as `estimated_delivery_fee`

### System Config
- [x] Key-value config store with grouping (`system_configs` table)
- [x] Cached reads (24 h) with automatic invalidation on write
- [x] Config groups: `restaurant`, `general`, etc.
- [x] `SystemConfigService`: `get`, `set`, `getBool`, `getNumber`, `getJson`, `getText`, `isOpenAt()`
- [x] Admin config UI: group listing + inline edit

### Public Settings API
- [x] `GET /api/v1/settings/public` → restaurant name, logo, phone, whatsapp, opening hours, is_open_now, delivery_note, is_accepting_orders

### Customer SPA (`/`)
- [x] Loads restaurant settings on init (name, logo, open/closed banner)
- [x] Category tabs (root + subcategory pills) with skeleton loaders
- [x] Product cards: name, price, effective price, image, availability badge
- [x] Product detail modal (description, image, add-to-cart)
- [x] Search across all products
- [x] Cart off-canvas (desktop button + mobile sticky bar): quantity controls, remove, clear
- [x] Checkout modal with per-field validation highlights and `invalid-feedback` messages
- [x] Order type switching (table / delivery / takeaway) shows/hides relevant fields
- [x] Delivery zones dropdown loaded from API
- [x] Submit loading spinner + double-submit prevention (`isSubmitting` flag)
- [x] Success modal with order number + large checkmark icon
- [x] Customer info auto-fill from server session or `localStorage`
- [x] Customer info saved to `localStorage` after successful order
- [x] Frontend error logging via `sendLog()` → `POST /api/v1/logs/frontend`

### Real-Time Admin Notifications
- [x] HTTP long-poll every 10 s: `GET /admin/orders?_poll=1` → `{latest_id, pending_count}`
- [x] New-order banner + audio beep (`public/sounds/notification.wav` played twice)
- [x] Browser tab title blinks "🔔 طلب جديد!"
- [x] Toggle auto-refresh on/off; manual refresh-now button
- [x] Test sound button

### Internationalisation
- [x] Arabic (`ar`) + English (`en`) — both full `app.php` translation files
- [x] RTL layout auto-applied when `locale = ar` (separate Bootstrap RTL CDN link)
- [x] Language switcher in customer header
- [x] Admin language management (add/edit/delete languages)
- [x] `SetLocale` middleware stores choice in session

### Dashboard
- [x] Today's orders count, today's sales, total customers, pending orders
- [x] Last-7-days orders bar chart
- [x] Recent 8 orders table

---

## 3. WHAT'S MISSING

### High Priority
| # | Feature | Notes |
|---|---------|-------|
| 1 | **Order status: `preparing` → `ready` → `delivered`** | ✅ **DONE** — Controller actions, PATCH routes, UI buttons in index + show views all implemented. |
| 2 | **Payment tracking** | ✅ **DONE** — "Mark as Paid" button + modal (select cash/card/online) on order detail page. Payment status badge added to order list. |
| 3 | **Customer order notification** | ✅ **DONE** — WhatsApp click-to-send buttons added to the order detail page. For each order status (accepted, rejected, preparing, ready, delivered, completed, cancelled) the admin sees a pre-filled WhatsApp message button that opens `wa.me/{customer_phone}` with a bilingual (AR/EN) message template. |
| 4 | **Admin statistics / reports** | ✅ **DONE** — Full Reports page with date range filter, revenue cards, bar chart (Chart.js), doughnut chart, top products table, orders-by-status breakdown, paginated order list, and CSV export. |

### Medium Priority
| # | Feature | Notes |
|---|---------|-------|
| 5 | **WebSocket / real-time push** | Admin polling is every 10 s. No true WebSocket (Laravel Echo / Pusher / Soketi). |
| 6 | **Product modifiers / add-ons** | No sizes, variants, extra toppings, or combo options. |
| 7 | **Inventory / stock tracking** | `quantity` column exists; stock check intentionally skipped in `OrderService`. |
| 8 | **Customer order history page** | Customers have no persistent login; there is no "My Orders" page. |
| 9 | **Takeaway scheduled pickup** | Only `delivery` supports `scheduled_at`. Takeaway is always immediate. |
| 10 | **WhatsApp integration** | `restaurant_whatsapp` stored in config and sent to SPA, but no auto-send on new order. |

### Low Priority
| # | Feature | Notes |
|---|---------|-------|
| 11 | **Thermal / POS printer support** | Admin invoice view exists but no 58 mm / 80 mm optimised print layout. |
| 12 | **Table management** | Table numbers are free text; no floor plan or table reservation system. |
| 13 | **Takeaway + dine-in order merging** | No concept of splitting bills or merging table orders. |
| 14 | **Product ratings / reviews** | Not in scope, not started. |
| 15 | **Multi-image per product** | Only one `image` field per product. |
| 16 | **Admin dark mode** | Not implemented. |
| 17 | **Automated tests** | `TestCase.php` exists; `Feature/` and `Unit/` directories are present but contain no meaningful test cases. |
| 18 | **Legacy API cleanup** | `/api/register`, `/api/login`, `/api/products` (Passport) are dead code from an earlier phase. |

---

## 4. FILE STRUCTURE

```
Restaurant-ERP/
├── app/
│   ├── Exceptions/Handler.php
│   ├── Helpers/
│   │   ├── ConfigHelper.php
│   │   └── Helpers.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── AuthController.php
│   │   │   ├── Admin/
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ConfigController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── DeliveryZoneController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   └── ProductController.php
│   │   │   ├── API/
│   │   │   │   ├── BaseController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── LanguageController.php
│   │   │   │   ├── ProductController.php   ← legacy
│   │   │   │   ├── ProfileController.php
│   │   │   │   ├── RegisterController.php  ← legacy
│   │   │   │   ├── UserController.php
│   │   │   │   └── V1/
│   │   │   │       ├── ApiResponse.php     ← trait
│   │   │   │       ├── CartController.php
│   │   │   │       ├── CategoryController.php
│   │   │   │       ├── CustomerController.php
│   │   │   │       ├── DeliveryZoneController.php
│   │   │   │       ├── FrontendLogController.php
│   │   │   │       ├── OrderController.php
│   │   │   │       ├── ProductController.php
│   │   │   │       └── PublicSettingsController.php
│   │   │   └── Public/
│   │   │       └── HomeController.php
│   │   ├── Middleware/
│   │   │   ├── AdminMiddleware.php
│   │   │   ├── ApiLoggingMiddleware.php
│   │   │   ├── Authenticate.php
│   │   │   ├── EnsureCustomerSession.php
│   │   │   ├── ModeratorMiddleware.php
│   │   │   ├── RedirectIfAuthenticated.php
│   │   │   └── SetLocale.php
│   │   ├── Requests/
│   │   │   └── StoreOrderRequest.php
│   │   └── Resources/
│   │       ├── ProductResource.php         ← legacy
│   │       └── V1/
│   │           ├── CategoryResource.php
│   │           ├── DeliveryZoneResource.php
│   │           ├── OrderItemResource.php
│   │           ├── OrderResource.php
│   │           └── ProductResource.php
│   ├── Models/
│   │   ├── Category.php
│   │   ├── Customer.php
│   │   ├── DeliveryZone.php
│   │   ├── Language.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Product.php
│   │   ├── SystemConfig.php
│   │   └── User.php
│   ├── Policies/ProductPolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── AuthServiceProvider.php
│   └── Services/
│       ├── CartService.php
│       ├── OrderService.php
│       └── SystemConfigService.php
├── database/
│   ├── migrations/           ← 23 migration files (see §5)
│   └── seeders/
│       ├── CategorySeeder.php
│       ├── DatabaseSeeder.php
│       ├── DeliveryZoneSeeder.php
│       ├── LanguageSeeder.php
│       ├── ProductSeeder.php
│       ├── RestaurantConfigSeeder.php
│       └── SystemConfigSeeder.php
├── resources/
│   ├── lang/
│   │   ├── ar/ (app, auth, messages, pagination, passwords, validation)
│   │   └── en/ (app, auth, messages, pagination, passwords, validation)
│   └── views/
│       ├── admin/
│       │   ├── categories/   (index, create, edit, _form)
│       │   ├── configs/      (index, group)
│       │   ├── customers/    (index, show)
│       │   ├── delivery_zones/ (index, create, edit, _form)
│       │   ├── languages/    (index, create, edit)
│       │   ├── orders/       (index, show, invoice, partials/modals, partials/status-badge)
│       │   ├── products/     (index, create, edit, import)
│       │   ├── profile/      (edit)
│       │   └── users/        (index, create, edit)
│       ├── auth/             (login, register, forgot-password, reset-password)
│       ├── errors/           (403, 404)
│       ├── layouts/app.blade.php
│       ├── public/home.blade.php   ← Customer SPA (~1 600 lines)
│       └── dashboard.blade.php
├── routes/
│   ├── api.php
│   └── web.php
└── public/
    ├── css/customer-home.css
    └── sounds/notification.wav
```

---

## 5. DATABASE STATUS

| Table | Created by Migration | Key Columns |
|-------|---------------------|-------------|
| `users` | `0001_01_01_000000` + `2023_08_15` | id, name, email, password, role (admin/moderator), profile_picture |
| `cache` | `0001_01_01_000001` | — |
| `jobs` | `0001_01_01_000002` | — |
| `languages` | `2023_08_15` + `2025_04_11` | id, name, code, flag_path, status, is_default, direction |
| `products` | `2025_04_08` + `2025_04_14` + `2026_05_03` + `2026_05_11` ×2 | id, name, name_ar, name_en, description_ar, description_en, price, discount_price, image, category_id, user_id, is_available, is_featured, is_active, sort_order, slug, quantity |
| `oauth_*` (5 tables) | `2025_04_09` | Laravel Passport tables (legacy — unused in current v1 flow) |
| `system_configs` | `2025_04_12` | id, key, value, group |
| `categories` | `2026_05_03_100000` | id, name_ar, name_en, image, parent_id, sort_order, is_active |
| `customers` | `2026_05_03_100001` | id, full_name, phone (unique), default_address, is_blocked, blocked_reason |
| `delivery_zones` | `2026_05_03_100002` | id, area_name, estimated_fee, sort_order, is_active |
| `orders` | `2026_05_03_100004` + `2026_05_04` | id, order_number (unique), customer_id (FK), source, order_type, table_number, **address**, delivery_type, scheduled_at, status, subtotal, estimated_delivery_fee, delivery_fee, discount, total, payment_status, payment_method, customer_note, rejection_reason, cancelled_at, accepted_at, completed_at |
| `order_items` | `2026_05_03_100005` | id, order_id (FK), product_id (FK), product_name, product_price, quantity, total |

**Total tables: 16**  
`oauth_*` tables exist but serve no active purpose in the current system.

---

## 6. API ENDPOINTS

### Legacy (pre-restaurant module — consider removing)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/register` | — | Passport user register |
| POST | `/api/login` | — | Passport login |
| GET/POST/PUT/DELETE | `/api/products` | `auth:api` | Legacy product CRUD |

### Public V1 (throttle: 60 req/min)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/settings/public` | — | Restaurant settings, opening hours |
| GET | `/api/v1/categories` | — | Active categories list |
| GET | `/api/v1/products` | — | Products (filters: category_id, featured, search, per_page) |
| GET | `/api/v1/delivery-zones` | — | Active delivery zones |
| POST | `/api/v1/logs/frontend` | — | Client-side error logging |

### Orders (StartSession + customer.session; orders: throttle 10 req/min)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/v1/orders` | Guest+session | Place new order |
| GET | `/api/v1/orders/{order_number}` | Guest+session | Get order status |
| POST | `/api/v1/orders/{order_number}/cancel` | Guest+session | Cancel pending order |

### Customer Session
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/customer/me` | Guest+session | Return name+phone if session bound |

### Cart (StartSession + customer.session)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/cart` | Guest+session | Get cart contents + subtotal |
| POST | `/api/v1/cart/add` | Guest+session | Add item (product_id, quantity) |
| POST | `/api/v1/cart/update` | Guest+session | Update quantity (0 = remove) |
| POST | `/api/v1/cart/remove` | Guest+session | Remove item |
| POST | `/api/v1/cart/clear` | Guest+session | Empty cart |

### Admin Web Routes (all require `auth`)
| Method | Path | Role | Description |
|--------|------|------|-------------|
| GET | `/admin/dashboard` | any | Dashboard |
| GET/POST | `/admin/products/*` | moderator | Product CRUD + import/export |
| GET/POST | `/admin/categories/*` | moderator | Category CRUD |
| GET/POST | `/admin/orders/*` | any/moderator | Order list, view, accept/reject/complete/cancel, invoice |
| GET/POST | `/admin/customers/*` | any | Customer list, view, block/unblock |
| GET/POST | `/admin/delivery-zones/*` | moderator | Delivery zone CRUD |
| GET/POST | `/admin/languages/*` | moderator | Language CRUD |
| GET/PUT | `/admin/configs/*` | admin | System config |
| GET/POST | `/admin/users/*` | admin | User management |
| GET/POST | `/admin/profile/*` | any | Own profile edit |

---

## 7. PRIORITY TODO LIST (Next 2 Weeks)

### Week 1 — Core Completeness

**Day 1–2: Order workflow status expand**
- Add admin buttons: `Preparing` → `Ready` → `Delivered` in `Admin\OrderController`
- Update `orders/show.blade.php` and `orders/index.blade.php` partials with new status badges
- Add transition guards in controller (e.g. can only mark "preparing" after "accepted")

**Day 2–3: Payment tracking**
- Add "Mark as Paid" button in order detail view
- Store `payment_method` (cash / card / online) when accepting order
- Show payment badges in order list

**Day 3–4: Customer notification (minimum viable)**
- Create `OrderConfirmed` / `OrderStatusChanged` Mailable classes
- Hook `Mail::to($customer)->send(...)` in admin accept/reject actions
- Alternatively: WhatsApp message via `restaurant_whatsapp` config (link open in new tab)

**Day 5: Admin order export**
- Add CSV export to `Admin\OrderController` (filtered by date range / status)
- Leverage existing `phpoffice/phpspreadsheet` already installed

### Week 2 — Polish & Stability

**Day 6–7: Reports / analytics**
- Date range filter on dashboard
- Revenue per period
- Top 5 products by order count

**Day 8: Automated tests**
- Feature tests for critical API paths: place order, cancel order, cart operations
- Unit test for `OrderService::createOrder` and `SystemConfigService::isOpenAt`

**Day 9: Legacy API cleanup**
- Remove or gate `/api/register`, `/api/login`, `/api/products` behind a feature flag
- Remove 5 `oauth_*` tables (or at least stop running Passport migrations)

**Day 10: Minor UX & bugs**
- Thermal/POS print stylesheet for `orders/invoice.blade.php`
- Blocked customer error message: replace hardcoded Arabic string in `OrderService` with `__('app.*')` key
- `ApiLoggingMiddleware` — register or remove
- Profile picture upload UI (migration exists, UI may be incomplete)

---

## 8. BLOCKERS & KNOWN ISSUES

### Active Bugs (fixed in this session)
| # | File | Issue | Status |
|---|------|-------|--------|
| 1 | `resources/views/public/home.blade.php` line 1327 | `btnText.innerHTML = <span...>` — HTML string without quotes caused JS parse error → blank page | ✅ **Fixed** |
| 2 | `app/Services/OrderService.php` | Blocked customer + duplicate order messages were **hardcoded Arabic** | ✅ **Fixed** — now use `__('app.customer_blocked_message')` and `__('app.duplicate_order_message')` |

### Known Issues (not yet fixed)
| # | Severity | Location | Issue |
|---|----------|----------|-------|
| 2 | Medium | ~~`app/Http/Controllers/Admin/OrderController.php`~~ | ~~Status transitions `preparing`, `ready`, `delivered` defined in model constants but **no controller actions or UI**~~ ✅ Fixed |
| 3 | Medium | ~~`app/Services/OrderService.php` line 86~~ | ~~Blocked customer error message is hardcoded Arabic~~ ✅ Fixed |
| 4 | Low | `app/Http/Middleware/ApiLoggingMiddleware.php` | Middleware exists but is **not registered** in `bootstrap/app.php` or any route group. Either register it or delete it. |
| 5 | Low | `app/Http/Controllers/API/` (root level) | `RegisterController`, `ProductController` (legacy Passport API) are dead code. Their routes still exist at `/api/register` and `/api/login`. These expose unnecessary surface area. |
| 6 | Low | `database/migrations/*oauth*` | Five Passport OAuth tables are created but the v1 API uses no Passport auth. Adds migration weight and confusion. |
| 7 | Low | `resources/views/admin/orders/index.blade.php` | New-order notification banner text is **hardcoded Arabic**, not behind a translation key. |
| 8 | Info | `app/Http/Controllers/Public/HomeController.php` | Controller passes **no variables** to `public.home` view. The SPA fetches everything via API, which is intentional. However, any server-side Blade error silently renders an empty page with no JS errors visible. |
| 9 | Info | `routes/api.php` | The three `Route::middleware([StartSession, 'customer.session'])` groups could be merged into one group to reduce duplication. |

