<?php

use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\DeliveryZoneController;
use App\Http\Controllers\API\V1\FrontendLogController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\PublicSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;

Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::resource('products', ProductController::class);
});

// ── V1 Public API ─────────────────────────────────────────────────────────────
// throttle:60,1 → max 60 requests per IP per minute across all v1 routes
Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function () {

    // Restaurant public settings
    Route::get('settings/public', PublicSettingsController::class)
         ->name('settings.public');

    // Menu
    Route::get('categories',     [CategoryController::class,     'index'])->name('categories.index');
    Route::get('products', action: [\App\Http\Controllers\API\V1\ProductController::class,      'index'])->name('products.index');
    Route::get('delivery-zones', [DeliveryZoneController::class, 'index'])->name('delivery-zones.index');

    // Orders — extra strict throttle: max 10 order attempts per IP per minute
    Route::post('orders', [OrderController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('orders.store');

    Route::get ('orders/{order_number}',        [OrderController::class, 'show'])  ->name('orders.show');
    Route::post('orders/{order_number}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Frontend error logging — relaxed throttle to avoid blocking legit error reports
    Route::post('logs/frontend', FrontendLogController::class)
        ->withoutMiddleware('throttle:60,1')
        ->middleware('throttle:30,1')
        ->name('logs.frontend');

    // ── Cart (session-based, no auth required) ────────────────────────────────
    Route::middleware(\Illuminate\Session\Middleware\StartSession::class)->group(function () {
        Route::get ('cart',        [CartController::class, 'index'])  ->name('cart.index');
        Route::post('cart/add',    [CartController::class, 'add'])    ->name('cart.add');
        Route::post('cart/update', [CartController::class, 'update']) ->name('cart.update');
        Route::post('cart/remove', [CartController::class, 'remove']) ->name('cart.remove');
        Route::post('cart/clear',  [CartController::class, 'clear'])  ->name('cart.clear');
    });
});
