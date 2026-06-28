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

    // Server-side cache TTL (seconds) for the product list endpoint.
    'product_list_ttl' => (int) env('API_PRODUCT_LIST_TTL', 300),

    // Browser/CDN cache max-age (seconds) for the public GET endpoints
    // (cache.headers middleware).
    'http_cache_max_age' => (int) env('API_HTTP_CACHE_MAX_AGE', 300),

];
