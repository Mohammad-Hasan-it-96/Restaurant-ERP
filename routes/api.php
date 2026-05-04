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
    // Orders – no auth required (customer-facing)
    Route::post('orders', [\App\Http\Controllers\API\V1\OrderController::class, 'store'])
         ->name('orders.store');
});

