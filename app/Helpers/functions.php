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

use App\Services\ActivityLogger;
use App\Services\LogService;
use App\Services\SystemConfigService;
use App\Support\Feature;

if (! function_exists('activity')) {
    /**
     * Resolve the ActivityLogger for recording business audit events.
     *
     * Records curated business events (admin and customer) to the activity_logs
     * table, separate from LogService's technical logs:
     *   activity()->log('order.accepted', $order, 'Order #'.$order->order_number);
     */
    function activity(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }
}

if (! function_exists('logService')) {
    /**
     * Resolve the central LogService singleton.
     *
     * Terse entry point for structured application logging that goes through
     * the standard levels and benefits from the globally-injected request
     * context (see App\Http\Middleware\InjectLogContext):
     *   logService()->error('order.create.failed', ['order_id' => $id], $e);
     */
    function logService(): LogService
    {
        return app(LogService::class);
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount with the configured currency (symbol, position, decimals).
     *
     * Single formatting path for restaurant-tunable currency in Blade:
     *   {{ money($order->total) }}
     */
    function money(int|float|string|null $amount): string
    {
        return app(SystemConfigService::class)->formatMoney($amount);
    }
}

if (! function_exists('currency_symbol')) {
    /**
     * The configured currency symbol on its own (for labels next to a number
     * that is formatted separately): {{ currency_symbol() }}.
     */
    function currency_symbol(): string
    {
        return app(SystemConfigService::class)->currency()['symbol'];
    }
}

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
