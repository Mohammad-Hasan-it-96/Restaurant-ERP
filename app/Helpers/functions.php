<?php

/*
|--------------------------------------------------------------------------
| Global helper functions
|--------------------------------------------------------------------------
|
| Intentionally NOT namespaced so the functions below are available globally
| (Blade views, services, controllers). Registered via composer.json's
| autoload "files" array.
|
*/

use App\Support\Feature;

if (! function_exists('feature')) {
    /**
     * Is the given system feature flag enabled?
     *
     * Thin global wrapper over App\Support\Feature::enabled() for ergonomic
     * use in Blade and services: feature('orders.modification').
     */
    function feature(string $path): bool
    {
        return Feature::enabled($path);
    }
}

if (! function_exists('feature_or_fail')) {
    /**
     * Abort with 403 when the given feature flag is disabled.
     *
     * Defense-in-depth guard for controller/service actions whose route is
     * also (or should be) gated by the `feature:<flag>` middleware:
     *   feature_or_fail('orders.admin_cancel');
     */
    function feature_or_fail(string $path): void
    {
        if (Feature::disabled($path)) {
            abort(403, 'This feature is disabled.');
        }
    }
}
