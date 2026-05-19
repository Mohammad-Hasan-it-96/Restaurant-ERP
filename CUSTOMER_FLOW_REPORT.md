# تقرير كامل — سيناريو الزبائن، الطلبات، والـ Tokens
**تاريخ التقرير:** 2026-05-19  
**حالة المشروع:** ⚠️ يوجد بُق حرج يمنع تخزين التوكن في الداتابيز

---

## 1. خريطة المكوّنات المعنيّة

```
┌─────────────────────────────────────────────────────────────────┐
│  Frontend (React SPA — customer-spa/)                           │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────────┐   │
│  │CheckoutModal │  │   MyOrders   │  │     MyProfile       │   │
│  │  (POST /orders)│  │(GET /customer│  │(GET /customer/me)   │   │
│  └──────┬───────┘  │ /orders)     │  └──────────┬──────────┘   │
│         │          └──────┬───────┘             │              │
│         │                 │                     │              │
│  localStorage:            └──────────────────────┘             │
│  • customer_token   (Bearer Token)                              │
│  • my_orders        (order numbers)                             │
│  • my_orders_data   (full order objects)                        │
│  • customer_info    (name, phone, address)                      │
└─────────────────────────────────────────────────────────────────┘
                         ↕ HTTP
┌─────────────────────────────────────────────────────────────────┐
│  Backend (Laravel API — routes/api.php)                         │
│                                                                 │
│  PUBLIC (no auth):                                              │
│    GET  /api/v1/settings/public                                 │
│    GET  /api/v1/categories                                      │
│    GET  /api/v1/products                                        │
│    GET  /api/v1/delivery-zones                                  │
│                                                                 │
│  SESSION-BASED (CustomerSession + EnsureCustomerSession):       │
│    POST /api/v1/orders           ← طلب جديد                    │
│    GET/POST /api/v1/cart/*       ← السلة                       │
│                                                                 │
│  TOKEN-BASED (ResolveCustomerByToken - Authorization: Bearer):  │
│    GET  /api/v1/orders/{order_number}                           │
│    POST /api/v1/orders/{order_number}/cancel                    │
│    GET  /api/v1/customer/me                                     │
│    GET  /api/v1/customer/orders                                 │
│    POST /api/v1/customer/update                                 │
└─────────────────────────────────────────────────────────────────┘
                         ↕ Eloquent ORM
┌─────────────────────────────────────────────────────────────────┐
│  Database                                                       │
│  • customers   (id, token⚠️, full_name, phone, ...)            │
│  • orders      (id, order_number, customer_id, status, ...)     │
│  • order_items (id, order_id, product_name, qty, total, ...)    │
│  • sessions    (customer_spa_session cookie)                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. سيناريو الزبون خطوة بخطوة

### المرحلة 1 — تصفّح القائمة (Public)
```
الزبون يفتح الموقع
   → GET /api/v1/settings/public   ✅ بدون auth
   → GET /api/v1/categories        ✅ بدون auth
   → GET /api/v1/products          ✅ بدون auth
   → GET /api/v1/delivery-zones    ✅ بدون auth
```

### المرحلة 2 — إضافة للسلة (localStorage)
```
الزبون يضغط "أضف للسلة"
   → يُخزَّن في localStorage فقط (useCart hook)
   → لا يوجد أي طلب API هنا
```

### المرحلة 3 — تقديم الطلب (POST /orders)
```
الزبون يضغط "تأكيد الطلب" في CheckoutModal
   → POST /api/v1/orders
      Middleware: [CustomerSession, 'customer.session']
         1. CustomerSession:     يضبط session.cookie = 'customer_spa_session'
                                  يشغّل StartSession
         2. EnsureCustomerSession: يقرأ session()->get('customer_id')
                                    → null في أول طلب

      OrderService::createOrder():
         1. يقرأ session('customer_id') → null (لا جلسة)
         2. Customer::firstOrCreate(['phone' => ...]) 
            → يجد أو يُنشئ الزبون في DB ✅
         3. if (!$customer->token) {
               $customer->update(['token' => Str::uuid()]);
            }
            ⚠️⚠️⚠️ BUG HERE — token ليست في $fillable ⚠️⚠️⚠️
            → update() يصمت ولا يُنفَّذ لأن token محمية بـ mass-assignment
         4. يُنشئ Order ✅
         5. session()->put('customer_id', $customer->id) ✅

   OrderController::store() يُعيد:
      {
        "success": true,
        "data": {
           "order_number": "ORD-20260519-0001",
           ...
           "customer_token": null   ← ⚠️ NULL دائماً!
        }
      }

   CheckoutModal.jsx يستلم الرد:
      if (data.customer_token) {   ← false لأنه null
         localStorage.setItem('customer_token', ...)  ← لا يُنفَّذ أبداً
      }
   
   ✅ يُسجَّل الطلب في DB بشكل صحيح
   ❌ لا يُخزَّن التوكن في جدول customers
   ❌ لا يُخزَّن التوكن في localStorage
```

### المرحلة 4 — عرض طلباتي (GET /customer/orders)
```
الزبون يضغط "طلباتي" (MyOrders.jsx)
   → GET /api/v1/customer/orders
      Middleware: ['customer.token'] → ResolveCustomerByToken
         يقرأ Authorization: Bearer <token>
         → localStorage.getItem('customer_token') = null
         → لا يُرسَل Header
         → customer = null في request->attributes

      CustomerController::orders():
         $customer = $request->attributes->get('customer') → null
         return $this->success([])   ← مصفوفة فارغة دائماً

   ✅ MyOrders يقرأ من localStorage كـ fallback (my_orders_data)
   ❌ لا يجلب الطلبات الحقيقية من السيرفر
   ❌ لا تحديث للحالة (status) من السيرفر
```

### المرحلة 5 — عرض الملف الشخصي (GET /customer/me)
```
الزبون يفتح CheckoutModal مرة أخرى
   → GET /api/v1/customer/me
      Middleware: ['customer.token'] → ResolveCustomerByToken
         Authorization header = لا شيء
         customer = null

      CustomerController::me():
         return $this->success([])   ← فارغ دائماً
   
   ❌ الحقول لا تُملأ من السيرفر
   ✅ التراجع إلى localStorage (customer_info)
```

---

## 3. البُق الرئيسي — مفصّل

### 🔴 المشكلة: `token` غير موجودة في `$fillable`

**الملف:** `app/Models/Customer.php`

```php
// الحالة الراهنة ❌
protected $fillable = [
    'full_name',
    'phone',
    'default_address',
    'is_blocked',
    'blocked_reason',
    // ← token مفقودة هنا!
];
```

**ما يحدث:**
```php
// في OrderService.php السطر 97
$customer->update(['token' => (string) Str::uuid()]);
// لا يطرح استثناء، لكنه يتجاهل 'token' بصمت
// لأن Laravel's mass-assignment protection تصفّيه

// في CustomerController.php السطر 90
$customer->update(['token' => (string) Str::uuid()]);
// نفس المشكلة
```

**النتيجة المتسلسلة:**

| الخطوة | المتوقع | الواقع |
|--------|---------|--------|
| `$customer->update(['token' => '...'])` | يكتب في DB | يُتجاهَل بصمت |
| `customers.token` في DB | UUID مثل `3f7a-...` | `NULL` |
| `$order->customer->token` | UUID | `NULL` |
| `customer_token` في response API | UUID | `null` |
| `localStorage.customer_token` | UUID | غير موجود |
| `Authorization: Bearer ...` header | يُرسَل | لا يُرسَل |
| `/customer/me` يُعيد بيانات | ✅ | ❌ فارغ |
| `/customer/orders` يُعيد الطلبات | ✅ | ❌ فارغ |

---

## 4. مشاكل إضافية مكتشفة

### 🟡 مشكلة 2: الـ Middleware المزدوج على `orders.store`

**الملف:** `routes/api.php` السطر 31

```php
// الحالة الراهنة
Route::middleware([CustomerSession::class, 'customer.session'])->group(...)
```

- `CustomerSession::class` ← هذا يبدأ الجلسة (ALIAS = `customer.start`)
- `'customer.session'` ← هذا يقرأ الجلسة (EnsureCustomerSession)

الكود يستخدم اسم الـ Class مباشرة بدل الـ Alias المُعرَّف في `bootstrap/app.php`:
```php
'customer.start' => \App\Http\Middleware\CustomerSession::class,
```
يعمل لكنه غير متسق — يجب استخدام `'customer.start'`.

### 🟡 مشكلة 3: السلة تستخدم Session لكن Token-Routes لا تبدأ Session

راوتات السلة:
```php
Route::middleware([CustomerSession::class, 'customer.session'])->group(function () {
    Route::get('cart', ...) // يبدأ session → customer_spa_session
```

راوتات Token:
```php
Route::middleware(['customer.token'])->group(function () {
    Route::get('customer/orders', ...) // لا session هنا
```

الزبون الذي يملك token يمكنه الوصول، لكن بما أن التوكن لا يُخزَّن أصلاً (بُق #1) فهذه المسارات معطّلة دائماً.

### 🟡 مشكلة 4: `cancel` لا يتحقق من ownership

**الملف:** `app/Http/Controllers/API/V1/OrderController.php` السطر 95-127

```php
public function cancel(string $orderNumber): JsonResponse
{
    $order = Order::where('order_number', $orderNumber)->first();
    // ✅ يُلغي الطلب لأي زبون يعرف order_number
    // ❌ لا يتحقق أن الطلب يعود لـ customer من التوكن!
}
```

أي شخص يعرف رقم الطلب يستطيع إلغاءه.

---

## 5. هيكل الداتابيز — الوضع الحالي

### جدول `customers`
| عمود | نوع | ملاحظة |
|------|-----|--------|
| id | bigint PK | ✅ |
| token | varchar(64) UNIQUE NULLABLE | ✅ عمود موجود، لكن **دائماً NULL** بسبب البُق |
| full_name | varchar | ✅ |
| phone | varchar UNIQUE | ✅ |
| default_address | text NULLABLE | ✅ |
| is_blocked | boolean | ✅ |
| blocked_reason | varchar NULLABLE | ✅ |
| created_at / updated_at | timestamp | ✅ |

> **المشكلة:** العمود موجود في DB (migration نُفِّذ)، لكن التطبيق لا يكتب فيه أبداً.

### جدول `orders`
| عمود | نوع | ملاحظة |
|------|-----|--------|
| id | bigint PK | ✅ |
| order_number | varchar UNIQUE | ✅ مثل `ORD-20260519-0001` |
| customer_id | FK → customers | ✅ |
| customer_name | varchar | ✅ snapshot |
| phone | varchar | ✅ snapshot |
| source | varchar | ✅ دائماً `website` |
| order_type | varchar | `table` / `delivery` / `takeaway` |
| table_number | varchar NULLABLE | - |
| address | varchar NULLABLE | - |
| delivery_type | varchar NULLABLE | `immediate` / `scheduled` |
| scheduled_at | timestamp NULLABLE | - |
| status | varchar | `pending` / `accepted` / ... |
| subtotal | decimal(10,2) | ✅ |
| estimated_delivery_fee | decimal NULLABLE | ✅ |
| delivery_fee | decimal NULLABLE | null حتى يُحدَّد من الأدمن |
| discount | decimal | ✅ افتراضي 0 |
| total | decimal NULLABLE | = subtotal حالياً |
| payment_status | varchar | `unpaid` / `paid` / `refunded` |
| payment_method | varchar NULLABLE | - |
| customer_note | text NULLABLE | - |
| rejection_reason | varchar NULLABLE | - |
| cancelled_at | timestamp NULLABLE | - |
| accepted_at | timestamp NULLABLE | - |
| completed_at | timestamp NULLABLE | - |
| created_at / updated_at | timestamp | ✅ |

---

## 6. تدفق البيانات بين Frontend وBackend

```
┌───────────────────────────────────────────────────────────────┐
│ اختبار: POST /api/v1/orders (الطلب الأول)                     │
│                                                               │
│ Request:                                                      │
│   No Auth header                                              │
│   No customer_spa_session cookie (أول مرة)                    │
│   Body: { customer_name, customer_phone, order_type, items }  │
│                                                               │
│ Result in DB:                                                 │
│   customers: id=1, phone='0501234567', token=NULL ❌          │
│   orders: id=1, order_number='ORD-20260519-0001',            │
│           customer_id=1, status='pending' ✅                   │
│                                                               │
│ Response:                                                     │
│   { success: true, data: { order_number, ...,                │
│     customer_token: null ← هذا هو الداء }                     │
│                                                               │
│ localStorage بعد الطلب:                                       │
│   customer_token: (not set) ❌                                │
│   my_orders: ['ORD-20260519-0001'] ✅                         │
│   my_orders_data: [{...order object}] ✅                      │
│   customer_info: { name, phone, address } ✅                  │
└───────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────┐
│ اختبار: GET /api/v1/customer/orders                           │
│                                                               │
│ Request:                                                      │
│   Authorization: Bearer undefined/null (لا يُرسَل) ❌         │
│                                                               │
│ Response:                                                     │
│   { success: true, data: [] } ← فارغ دائماً                  │
│                                                               │
│ MyOrders.jsx يُعرض:                                           │
│   orders من localStorage (my_orders_data) ✅                  │
│   لكن بدون تحديث للـ status من السيرفر ❌                      │
└───────────────────────────────────────────────────────────────┘
```

---

## 7. الإصلاحات المطلوبة

### 🔴 الإصلاح #1 — CRITICAL: إضافة `token` لـ `$fillable`

**الملف:** `app/Models/Customer.php`

```php
protected $fillable = [
    'full_name',
    'phone',
    'default_address',
    'is_blocked',
    'blocked_reason',
    'token',    // ← أضف هذا السطر فقط
];
```

هذا وحده يحل:
- تخزين التوكن في DB
- إرجاع التوكن في response `POST /orders`
- حفظ التوكن في `localStorage`
- عمل جميع مسارات `customer.token`

---

### 🟡 الإصلاح #2 — التحقق من ownership في `cancel`

**الملف:** `app/Http/Controllers/API/V1/OrderController.php`

```php
public function cancel(string $orderNumber): JsonResponse
{
    $customer = request()->attributes->get('customer'); // من middleware
    $order = Order::where('order_number', $orderNumber)->first();

    if (!$order) {
        return $this->error(__('app.order_not_found'), 404);
    }

    // تحقق أن الطلب يعود لهذا الزبون
    if (!$customer || $order->customer_id !== $customer->id) {
        return $this->error(__('app.order_not_found'), 404);
    }

    // ... باقي الكود
}
```

---

### 🟡 الإصلاح #3 — تنظيف الـ Middleware alias

**الملف:** `routes/api.php`

```php
// بدلاً من:
Route::middleware([CustomerSession::class, 'customer.session'])

// استخدم الـ alias المعرَّف:
Route::middleware(['customer.start', 'customer.session'])
```

---

## 8. ملخص تشخيصي سريع

| المشكلة | التأثير | الملف | الأولوية |
|---------|---------|-------|----------|
| `token` غير موجودة في `$fillable` | التوكن لا يُخزَّن نهائياً في DB | `Customer.php` | 🔴 حرج |
| `OrderController::cancel` بلا ownership check | أي شخص يلغي أي طلب | `OrderController.php` | 🟠 عالي |
| Middleware alias غير متسق | code quality فقط | `api.php` | 🟡 متوسط |
| `MyOrders` يعتمد على localStorage كـ fallback | قد يعرض بيانات قديمة | `MyOrders.jsx` | 🟡 متوسط |

---

## 9. التحقق بعد الإصلاح

بعد إضافة `'token'` لـ `$fillable`، ستكون النتيجة المتوقعة:

```bash
# 1. تقديم طلب جديد
POST /api/v1/orders
# Response:
# { ..., "customer_token": "3f7a1b2c-..." }  ← يصبح UUID حقيقي

# 2. التحقق في DB
# customers: token = '3f7a1b2c-...'  ✅

# 3. طلب الطلبات بعدها
GET /api/v1/customer/orders
Authorization: Bearer 3f7a1b2c-...
# Response: [ { order_number, status, total, items }, ... ]  ✅

# 4. طلب الملف الشخصي
GET /api/v1/customer/me
Authorization: Bearer 3f7a1b2c-...
# Response: { id, name, phone, default_address, orders }  ✅
```

---

## 10. قائمة المراجعة الكاملة

- [ ] **إضافة `'token'` إلى `Customer::$fillable`** ← الأهم
- [ ] تشغيل migration التوكن: `php artisan migrate` (إذا لم يُنفَّذ بعد)
- [ ] اختبار `POST /orders` للتحقق من `customer_token` في الرد
- [ ] اختبار `GET /customer/orders` بـ Bearer Token
- [ ] اختبار `GET /customer/me` بـ Bearer Token
- [ ] إضافة ownership check في `OrderController::cancel`
- [ ] تنظيف `api.php` لاستخدام `customer.start` بدل class name مباشرة

---

*تم توليد هذا التقرير بتاريخ 2026-05-19 بناءً على تحليل أكواد المشروع الحالي.*

