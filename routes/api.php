<?php

use App\Http\Controllers\API\HealthController;
use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\DeliveryZoneController;
use App\Http\Controllers\API\V1\FrontendLogController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\PublicSettingsController;
use Illuminate\Support\Facades\Route;

// ── Health check ──────────────────────────────────────────────────────────────
// Unthrottled, session-less monitoring probe. 200 when healthy, 503 if DB down.
// Skip SetLocale (it queries the DB for active languages — would 500 the probe
// before our controlled try/catch when the DB is down) and ApiLoggingMiddleware
// (monitors poll often; no need to log every hit).
Route::get('health', HealthController::class)
    ->withoutMiddleware([
        \App\Http\Middleware\SetLocale::class,
        \App\Http\Middleware\ApiLoggingMiddleware::class,
    ])
    ->name('api.health');

// ── V1 Public API ─────────────────────────────────────────────────────────────
// throttle:60,1 → max 60 requests per IP per minute across all v1 routes
Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function () {

    // ── Public (no session required) ─────────────────────────────────────────
    Route::get('settings/public', PublicSettingsController::class)->name('settings.public');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('products', [\App\Http\Controllers\API\V1\ProductController::class, 'index'])->name('products.index');

    Route::get('delivery-zones', [DeliveryZoneController::class, 'index'])->name('delivery-zones.index');

    // Frontend structured logging — { message, level?, context? }. Relaxed
    // throttle (30/min/IP) that replaces the group throttle (no double-count).
    Route::post('logs', FrontendLogController::class)
        ->withoutMiddleware('throttle:60,1')
        ->middleware('throttle:30,1')
        ->name('logs.store');

    // ── Guest customer creation (no auth — visitor allowing notifications) ──────
    // Tighter limit than the global 60/min: an unauthenticated visitor only needs
    // this once, so 10/min/IP stops the endpoint being used to flood guest rows.
    // Replaces (not stacks on) the group throttle so the IP counter isn't hit twice.
    Route::post('customer/guest', [CustomerController::class, 'createGuest'])
        ->withoutMiddleware('throttle:60,1')
        ->middleware(['throttle:10,1', 'feature:notifications.push'])
        ->name('customer.guest');

    // ── Order placement (separate customer session, isolated from admin) ────────
    Route::middleware(['customer.start', 'customer.session'])->group(function () {
        // 20/min/IP — generous for real ordering, blocks bulk-order spam. Replaces
        // the group throttle so this route's writes get their own independent budget.
        Route::post('orders', [OrderController::class, 'store'])
            ->withoutMiddleware('throttle:60,1')
            ->middleware('throttle:20,1')
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

    // Cart (session-based as before) — inherits the group throttle:60,1, which is
    // exactly the intended cart limit; no per-route override needed (a duplicate
    // throttle:60,1 would double-count against the same IP key).
    Route::middleware(['customer.start', 'customer.session'])->group(function () {
        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('cart/update', [CartController::class, 'update'])->name('cart.update');
        Route::post('cart/remove', [CartController::class, 'remove'])->name('cart.remove');
        Route::post('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    });
});
