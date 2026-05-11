<?php
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
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Restaurant public settings
    Route::get('settings/public', \App\Http\Controllers\API\V1\PublicSettingsController::class)
         ->name('settings.public');

    // Menu
    Route::get('categories',     [\App\Http\Controllers\API\V1\CategoryController::class,     'index'])->name('categories.index');
    Route::get('products',       [\App\Http\Controllers\API\V1\ProductController::class,      'index'])->name('products.index');
    Route::get('delivery-zones', [\App\Http\Controllers\API\V1\DeliveryZoneController::class, 'index'])->name('delivery-zones.index');

    // Orders
    Route::post('orders',                          [\App\Http\Controllers\API\V1\OrderController::class, 'store']) ->name('orders.store');
    Route::get ('orders/{order_number}',           [\App\Http\Controllers\API\V1\OrderController::class, 'show'])  ->name('orders.show');
    Route::post('orders/{order_number}/cancel',    [\App\Http\Controllers\API\V1\OrderController::class, 'cancel'])->name('orders.cancel');

    // Frontend error logging
    Route::post('logs/frontend', \App\Http\Controllers\API\V1\FrontendLogController::class)->name('logs.frontend');
});

