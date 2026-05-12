<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\LanguageController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('public.home');

// Move the language change route outside the auth middleware
Route::get('/language/{locale}', [LanguageController::class, 'changeLanguage'])->name('language.change');

Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    Route::get('login', [AuthController::class, 'view_login'])->name('view_login');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::get('register', [AuthController::class, 'view_register'])->name('view_register');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
    Route::post('forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('forgot-password.submit');
    // Add these to your auth group
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');
    Route::get('forgot-password', [AuthController::class, 'forgot_password'])->name('forgot-password');
    Route::get('/reset-password/{token}', [AuthController::class, 'view_resetPassword'])->name('password.reset');
});

Route::group(['middleware' => 'auth', 'prefix' => 'admin', 'as' => 'admin.'], function () {
    // Dashboard - accessible by all authenticated users
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // Products routes
    Route::group(['prefix' => 'products', 'as' => 'products.'], function () {
        Route::get('', [AdminProductController::class, 'index'])->name('index');
        Route::get('export', [AdminProductController::class, 'export'])->name('export');
        Route::get('import', [AdminProductController::class, 'import'])->name('import');
        Route::get('template', [AdminProductController::class, 'downloadTemplate'])->name('template');
        Route::post('import', [AdminProductController::class, 'processImport'])->name('import.process');

        Route::middleware(['moderator'])->group(function () {
            Route::get('create', [AdminProductController::class, 'create'])->name('create');
            Route::post('store', [AdminProductController::class, 'store'])->name('store');
            Route::get('edit/{id}', [AdminProductController::class, 'edit'])->name('edit');
            Route::put('update/{id}', [AdminProductController::class, 'update'])->name('update');
            Route::delete('delete/{id}', [AdminProductController::class, 'destroy'])->name('delete');
        });
    });

    // Profile routes - accessible by all authenticated users
    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
        Route::get('edit', [ProfileController::class, 'edit'])->name('edit');
        Route::post('update', [ProfileController::class, 'update'])->name('update');
        Route::post('delete', [ProfileController::class, 'destroy'])->name('delete');
    });

    // Users routes - only for admin
    Route::group(['middleware' => 'admin', 'prefix' => 'users', 'as' => 'users.'], function () {
        Route::get('', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::post('store', [UserController::class, 'store'])->name('store');
        Route::get('edit/{id}', [UserController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('delete/{id}', [UserController::class, 'destroy'])->name('delete');
    });

    // Languages routes
    Route::group(['prefix' => 'languages', 'as' => 'languages.'], function () {
        // List route - accessible by all authenticated users
        Route::get('', [LanguageController::class, 'index'])->name('index');

        // Create, edit, update, delete - only for admin and moderator
        Route::middleware(['moderator'])->group(function () {
            Route::get('create', [LanguageController::class, 'create'])->name('create');
            Route::post('store', [LanguageController::class, 'store'])->name('store');
            Route::get('edit/{id}', [LanguageController::class, 'edit'])->name('edit');
            Route::post('update/{id}', [LanguageController::class, 'update'])->name('update');
            Route::post('destroy/{id}', [LanguageController::class, 'destroy'])->name('destroy');
        });
    });

    // Categories routes
    Route::group(['prefix' => 'categories', 'as' => 'categories.'], function () {
        Route::get('', [App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('index');
        Route::middleware(['moderator'])->group(function () {
            Route::get('create', [App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('create');
            Route::post('', [App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('store');
            Route::get('{category}/edit', [App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('edit');
            Route::put('{category}', [App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('update');
            Route::delete('{category}', [App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('destroy');
        });
    });

    // Customers routes
    Route::group(['prefix' => 'customers', 'as' => 'customers.'], function () {
        Route::get('',                              [CustomerController::class, 'index'])->name('index');
        Route::get('{customer}',                    [CustomerController::class, 'show'])->name('show');
        Route::post('{customer}/toggle-block',      [CustomerController::class, 'toggleBlock'])->name('toggle-block');
    });

    // Orders routes
    Route::group(['prefix' => 'orders', 'as' => 'orders.'], function () {
        Route::get('', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('index');
        Route::get('{order}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->name('show');
        Route::get('{order}/invoice', [App\Http\Controllers\Admin\OrderController::class, 'invoice'])->name('invoice');
        Route::middleware(['moderator'])->group(function () {
            Route::post('{order}/accept',   [App\Http\Controllers\Admin\OrderController::class, 'accept'])->name('accept');
            Route::post('{order}/reject',   [App\Http\Controllers\Admin\OrderController::class, 'reject'])->name('reject');
            Route::post('{order}/cancel',   [App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('cancel');
            Route::post('{order}/complete', [App\Http\Controllers\Admin\OrderController::class, 'complete'])->name('complete');
        });
    });

    // Delivery Zones routes
    Route::group(['prefix' => 'delivery-zones', 'as' => 'delivery-zones.'], function () {
        Route::get('', [App\Http\Controllers\Admin\DeliveryZoneController::class, 'index'])->name('index');
        Route::middleware(['moderator'])->group(function () {
            Route::get('create', [App\Http\Controllers\Admin\DeliveryZoneController::class, 'create'])->name('create');
            Route::post('', [App\Http\Controllers\Admin\DeliveryZoneController::class, 'store'])->name('store');
            Route::get('{deliveryZone}/edit', [App\Http\Controllers\Admin\DeliveryZoneController::class, 'edit'])->name('edit');
            Route::put('{deliveryZone}', [App\Http\Controllers\Admin\DeliveryZoneController::class, 'update'])->name('update');
            Route::delete('{deliveryZone}', [App\Http\Controllers\Admin\DeliveryZoneController::class, 'destroy'])->name('destroy');
        });
    });

    // System Configs Routes
    Route::prefix('configs')->name('configs.')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ConfigController::class, 'index'])->name('index');
        Route::get('/group/{group}', [App\Http\Controllers\Admin\ConfigController::class, 'group'])->name('group');
        Route::put('/update', [App\Http\Controllers\Admin\ConfigController::class, 'update'])->name('update');
        Route::post('/store', [App\Http\Controllers\Admin\ConfigController::class, 'store'])->name('store');
        Route::delete('/{id}', [App\Http\Controllers\Admin\ConfigController::class, 'destroy'])->name('destroy');
    });
});
