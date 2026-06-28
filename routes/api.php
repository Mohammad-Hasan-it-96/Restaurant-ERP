<?php

use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\DeliveryZoneController;
use App\Http\Controllers\API\V1\FrontendLogController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\PublicSettingsController;
use Illuminate\Support\Facades\Route;

// NOTE: The comprehensive GET /api/health report is registered in routes/web.php
// (admin-gated, needs a session). Automated external liveness uses Laravel's /up.

// ── V1 Public API ─────────────────────────────────────────────────────────────
// Rate limits + HTTP cache max-age are configurable in config/api.php.
Route::prefix('v1')->name('api.v1.')->middleware('throttle:'.config('api.throttle.default'))->group(function () {

    // ── Public (no session required) ─────────────────────────────────────────
    // Browser/CDN cache on the read-only public GETs. cache.headers adds
    // Cache-Control: public + Vary: Accept-Language (payloads are locale-dependent).
    Route::middleware('cache.headers:'.config('api.http_cache_max_age'))->group(function () {
        Route::get('settings/public', PublicSettingsController::class)->name('settings.public');
        Route::get('version', \App\Http\Controllers\API\V1\VersionController::class)->name('version');
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('products', [\App\Http\Controllers\API\V1\ProductController::class, 'index'])->name('products.index');
        Route::get('delivery-zones', [DeliveryZoneController::class, 'index'])->name('delivery-zones.index');
    });

    // Frontend structured logging — { message, level?, context? }. Relaxed
    // throttle (30/min/IP) that replaces the group throttle (no double-count).
    Route::post('logs', FrontendLogController::class)
        ->withoutMiddleware('throttle:'.config('api.throttle.default'))
        ->middleware('throttle:'.config('api.throttle.logs'))
        ->name('logs.store');

    // ── Guest customer creation (no auth — visitor allowing notifications) ──────
    // Tighter limit than the global 60/min: an unauthenticated visitor only needs
    // this once, so 10/min/IP stops the endpoint being used to flood guest rows.
    // Replaces (not stacks on) the group throttle so the IP counter isn't hit twice.
    Route::post('customer/guest', [CustomerController::class, 'createGuest'])
        ->withoutMiddleware('throttle:'.config('api.throttle.default'))
        ->middleware(['throttle:'.config('api.throttle.guest'), 'feature:notifications.push'])
        ->name('customer.guest');

    // ── Order placement (separate customer session, isolated from admin) ────────
    Route::middleware(['customer.start', 'customer.session'])->group(function () {
        // 20/min/IP — generous for real ordering, blocks bulk-order spam. Replaces
        // the group throttle so this route's writes get their own independent budget.
        Route::post('orders', [OrderController::class, 'store'])
            ->withoutMiddleware('throttle:'.config('api.throttle.default'))
            ->middleware('throttle:'.config('api.throttle.orders'))
            ->name('orders.store');
    });

    // ── Token-protected routes ────────────────────────────────────────────────
    // Authorization: Bearer <customer_token>
    Route::middleware(['customer.token'])->group(function () {
        Route::get('orders/{order_number}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order_number}/cancel', [OrderController::class, 'cancel'])
            ->middleware('feature:orders.customer_cancellation')
            ->name('orders.cancel');
        Route::post('orders/{order_number}/modify', [OrderController::class, 'modify'])
            ->middleware('feature:orders.modification')
            ->name('orders.modify');

        Route::get('customer/me', [CustomerController::class, 'me'])->name('customer.me');
        Route::get('customer/orders', [CustomerController::class, 'orders'])
            ->middleware('feature:customer.order_history')
            ->name('customer.orders');
        Route::post('customer/update', [CustomerController::class, 'update'])
            ->middleware('feature:customer.profile')
            ->name('customer.update');
        Route::post('customer/fcm-token', [CustomerController::class, 'saveFcmToken'])
            ->middleware('feature:notifications.push')
            ->name('customer.fcm-token');
    });

    // Cart (session-based as before) — inherits the group throttle (api.throttle.default),
    // which is exactly the intended cart limit; no per-route override needed (a duplicate
    // throttle would double-count against the same IP key).
    Route::middleware(['customer.start', 'customer.session'])->group(function () {
        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('cart/update', [CartController::class, 'update'])->name('cart.update');
        Route::post('cart/remove', [CartController::class, 'remove'])->name('cart.remove');
        Route::post('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    });
});
