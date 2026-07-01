<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand theme tokens
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the restaurant's brand colours, consumed by
    | BOTH front-ends:
    |   - Admin panel  : echoed into the :root {} block of layouts/app.blade.php
    |   - Customer SPA : exposed via /api/v1/settings/public ("theme" key) and
    |                    applied to :root at boot (customer-spa/src/index.css
    |                    holds the same values as fallback defaults)
    |
    | Override per restaurant with THEME_* env vars (no source edit needed), then
    | re-run `php artisan config:cache` in production. The installer wizard writes
    | THEME_PRIMARY for you.
    |
    */

    'primary' => env('THEME_PRIMARY', '#C0392B'),
    'primary_dark' => env('THEME_PRIMARY_DARK', '#922B21'),
    'primary_light' => env('THEME_PRIMARY_LIGHT', '#FDECEA'),

];
