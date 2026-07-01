<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public API tuning
    |--------------------------------------------------------------------------
    |
    | Deployment-level knobs for the customer-facing v1 API. Re-run
    | `php artisan config:cache` (and route:cache) after changing in prod.
    |
    */

    // Rate limits as "maxAttempts,decayMinutes" strings (Laravel throttle syntax).
    'throttle' => [
        'default' => env('API_THROTTLE_DEFAULT', '60,1'),
        'logs' => env('API_THROTTLE_LOGS', '30,1'),
        'guest' => env('API_THROTTLE_GUEST', '10,1'),
        'orders' => env('API_THROTTLE_ORDERS', '20,1'),
    ],

    // Hard cap on the client-supplied per_page for the product list.
    'max_per_page' => (int) env('API_MAX_PER_PAGE', 100),

    // Safety ceiling on the un-paginated ("full menu") product list response, so
    // the payload is bounded even when no per_page is supplied. Generous — above
    // any realistic catalogue; a truncation is logged (products.list.truncated).
    'product_list_max' => (int) env('API_PRODUCT_LIST_MAX', 1000),

    // Server-side cache TTL (seconds) for the product list endpoint.
    'product_list_ttl' => (int) env('API_PRODUCT_LIST_TTL', 300),

    // Server-side cache TTL (seconds) for the public categories list endpoint.
    'categories_list_ttl' => (int) env('API_CATEGORIES_LIST_TTL', 3600),

    // Server-side cache TTL (seconds) for the public delivery-zones list endpoint.
    'delivery_zones_list_ttl' => (int) env('API_DELIVERY_ZONES_LIST_TTL', 300),

    // Browser/CDN cache max-age (seconds) for the public GET endpoints
    // (cache.headers middleware).
    'http_cache_max_age' => (int) env('API_HTTP_CACHE_MAX_AGE', 300),

];
