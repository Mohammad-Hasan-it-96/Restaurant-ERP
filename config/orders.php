<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Order business rules
    |--------------------------------------------------------------------------
    |
    | Per-restaurant order behaviour that some operators want to change without
    | touching code. Re-run `php artisan config:cache` after changing in prod.
    |
    */

    // Prefix for generated order numbers, e.g. "ORD-" → ORD-20260628-0001.
    'number_prefix' => env('ORDER_NUMBER_PREFIX', 'ORD-'),

    // Window (seconds) during which an identical order from the same customer is
    // treated as a duplicate submission and rejected (double-click / retry guard).
    'duplicate_guard_seconds' => (int) env('ORDER_DUPLICATE_GUARD_SECONDS', 5),

];
