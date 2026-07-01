<?php

/*
|--------------------------------------------------------------------------
| System Feature Flags
|--------------------------------------------------------------------------
|
| Centralised on/off switches that shape this Restaurant ERP per client
| WITHOUT code changes. Flip a boolean here (or override via the matching
| FEATURE_* env var) to enable/disable a capability across the backend,
| the customer SPA, and the admin panel.
|
| Read flags through the resolver, never config() directly:
|     App\Support\Feature::enabled('orders.modification')
|     feature('orders.modification')               // global helper
|     @feature('products.options') ... @endfeature // Blade
|
| Safe default = ENABLED. A fresh install behaves exactly like before; a
| client opts features OUT. Each flag falls back to false so a missing env
| var never silently disables a feature.
|
| Flag path  ............................  User-facing name
| ---------------------------------------------------------------
| core.delivery ........................  enable_delivery
| core.takeaway .......................   enable_takeaway
| core.table_ordering .................   enable_table_ordering
| products.weight_products ............   enable_weight_products
| products.options ....................   enable_product_options
| orders.modification .................   enable_order_modification
| orders.customer_cancellation ........   enable_order_cancellation_by_customer
| orders.admin_cancel .................   enable_admin_cancel_order
| customer.profile ....................   enable_customer_profile
| customer.order_history ..............   enable_order_history
| notifications.push ..................   enable_push_notifications
| notifications.whatsapp_in_order_view    enable_whatsapp_in_order_view
| admin.permissions_system ............   enable_permissions_system
| localization.languages ..............   enable_languages
|
| NOTE: after changing this file or the FEATURE_* env vars in production,
| run `php artisan config:cache` so the cached config picks up the values.
|
*/

return [

    // ── Core ordering channels ──────────────────────────────────────────
    'core' => [
        'delivery' => env('FEATURE_DELIVERY', true),
        'takeaway' => env('FEATURE_TAKEAWAY', true),
        'table_ordering' => env('FEATURE_TABLE_ORDERING', true),
    ],

    // ── Product capabilities ────────────────────────────────────────────
    'products' => [
        'weight_products' => env('FEATURE_WEIGHT_PRODUCTS', true),
        'options' => env('FEATURE_PRODUCT_OPTIONS', true),
    ],

    // ── Order lifecycle actions ─────────────────────────────────────────
    'orders' => [
        'modification' => env('FEATURE_ORDER_MODIFICATION', true),
        'customer_cancellation' => env('FEATURE_ORDER_CANCELLATION_BY_CUSTOMER', true),
        'admin_cancel' => env('FEATURE_ADMIN_CANCEL_ORDER', true),
    ],

    // ── Customer account features ───────────────────────────────────────
    'customer' => [
        'profile' => env('FEATURE_CUSTOMER_PROFILE', true),
        'order_history' => env('FEATURE_ORDER_HISTORY', true),
    ],

    // ── Notifications ───────────────────────────────────────────────────
    'notifications' => [
        'push' => env('FEATURE_PUSH_NOTIFICATIONS', true),
        'whatsapp_in_order_view' => env('FEATURE_WHATSAPP_IN_ORDER_VIEW', true),
    ],

    // ── Admin / platform ────────────────────────────────────────────────
    'admin' => [
        'permissions_system' => env('FEATURE_PERMISSIONS_SYSTEM', true),
    ],

    // ── Localization ────────────────────────────────────────────────────
    // When off: hides the public language switcher AND admin Languages
    // management. The SPA falls back to its built-in default locale.
    'localization' => [
        'languages' => env('FEATURE_LANGUAGES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    348| client_safe
    |--------------------------------------------------------------------------
    | The whitelist of flag paths exposed to the customer SPA (and any other
    | public frontend) via GET /api/v1/settings/public. Admin-only flags such
    | as admin.permissions_system and orders.admin_cancel are intentionally
    | omitted so they never leak to customers. Keep this list curated.
    */
    'client_safe' => [
        'core.delivery',
        'core.takeaway',
        'core.table_ordering',
        'products.weight_products',
        'products.options',
        'orders.modification',
        'orders.customer_cancellation',
        'customer.profile',
        'customer.order_history',
        'notifications.push',
        'notifications.whatsapp_in_order_view',
        'localization.languages',
    ],

];
