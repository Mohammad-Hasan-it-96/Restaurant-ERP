<?php

use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\DeliveryZoneController;
use App\Http\Controllers\API\V1\FrontendLogController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\PublicSettingsController;
use App\Http\Middleware\CustomerSession;
use Illuminate\Support\Facades\Route;


// ── V1 Public API ─────────────────────────────────────────────────────────────
// throttle:60,1 → max 60 requests per IP per minute across all v1 routes
Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function () {

    // ── Public (no session required) ─────────────────────────────────────────
    Route::get('settings/public', PublicSettingsController::class)->name('settings.public');
    Route::get('categories',      [CategoryController::class,     'index'])->name('categories.index');
    Route::get('products',        [\App\Http\Controllers\API\V1\ProductController::class, 'index'])->name('products.index');
    Route::get('delivery-zones',  [DeliveryZoneController::class, 'index'])->name('delivery-zones.index');

    // Frontend error logging — relaxed throttle
    Route::post('logs/frontend', FrontendLogController::class)
        ->withoutMiddleware('throttle:60,1')
        ->middleware('throttle:30,1')
        ->name('logs.frontend');

    // ── Session-bound routes (CustomerSession isolates from admin cookie) ─────
    // CustomerSession boots a SEPARATE 'customer_spa_session' cookie so the
    // admin's 'restaurant_session' is never touched by SPA requests.
    Route::middleware([CustomerSession::class, 'customer.session'])->group(function () {

        // Orders (extra strict throttle on placement only)
        Route::post('orders', [OrderController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('orders.store');
        Route::get ('orders/{order_number}',        [OrderController::class, 'show'])  ->name('orders.show');
        Route::post('orders/{order_number}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        // Guest profile auto-fill
        Route::get('customer/me', [CustomerController::class, 'me'])->name('customer.me');

        // Cart
        Route::get ('cart',        [CartController::class, 'index'])  ->name('cart.index');
        Route::post('cart/add',    [CartController::class, 'add'])    ->name('cart.add');
        Route::post('cart/update', [CartController::class, 'update']) ->name('cart.update');
        Route::post('cart/remove', [CartController::class, 'remove']) ->name('cart.remove');
        Route::post('cart/clear',  [CartController::class, 'clear'])  ->name('cart.clear');
    });
});
