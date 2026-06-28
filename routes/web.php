<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\LanguageController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('public.home');

// Comprehensive health report (database, cache, queue, storage, disk, versions).
// Admin-gated via the web session — automated external liveness must use /up.
Route::get('api/health', \App\Http\Controllers\API\HealthController::class)
    ->middleware(['auth', 'admin'])
    ->name('api.health');

// ── Installer wizard ────────────────────────────────────────────────────────
// Reachable only while not installed (gated by EnsureInstalled). Runs before the
// DB/.env are configured; see the "Installer Wizard" section in CLAUDE.md.
Route::prefix('install')->name('install.')
    ->controller(\App\Http\Controllers\InstallController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('database', 'database')->name('database');
        Route::post('database', 'storeDatabase')->name('database.store');
        Route::get('app', 'appUrl')->name('app');
        Route::post('app', 'storeApp')->name('app.store');
        Route::get('restaurant', 'restaurant')->name('restaurant');
        Route::post('restaurant', 'storeRestaurant')->name('restaurant.store');
        Route::get('admin', 'admin')->name('admin');
        Route::post('admin', 'storeAdmin')->name('admin.store');
        Route::get('finish', 'finish')->name('finish');
    });

// Move the language change route outside the auth middleware
Route::middleware('feature:localization.languages')->group(function () {
    Route::get('/language/{locale}', [LanguageController::class, 'changeLanguage'])->name('language.change');
    Route::get('/lang/{locale}', [LanguageController::class, 'changeLanguage'])->name('lang.change');
});

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
    // Redirect bare /admin to dashboard
    Route::get('', fn () => redirect()->route('admin.dashboard'))->name('index');

    // Dashboard - accessible by all authenticated users
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('system-secure-metrics-health-logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);

    // Activity Log (business audit trail) — admin only
    Route::get('activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])
        ->middleware('admin')
        ->name('activity-logs.index');
    // Reports & Analytics
    Route::get('reports', [ReportController::class, 'index'])->name('reports');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Products routes
    Route::group(['prefix' => 'products', 'as' => 'products.'], function () {
        Route::get('', [AdminProductController::class, 'index'])->name('index');
        Route::get('export', [AdminProductController::class, 'export'])->name('export');
        Route::get('import', [AdminProductController::class, 'import'])->name('import');
        Route::get('template', [AdminProductController::class, 'downloadTemplate'])->name('template');
        Route::post('import', [AdminProductController::class, 'processImport'])->name('import.process');
        Route::post('{product}/toggle-availability', [AdminProductController::class, 'toggleAvailability'])->name('toggle-availability');

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

    // Languages routes (gated by the localization feature)
    Route::group(['prefix' => 'languages', 'as' => 'languages.', 'middleware' => 'feature:localization.languages'], function () {
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
        Route::get('', [CustomerController::class, 'index'])->name('index');
        Route::get('{customer}', [CustomerController::class, 'show'])->name('show');
        Route::post('{customer}/block', [CustomerController::class, 'block'])->name('block');
        Route::post('{customer}/unblock', [CustomerController::class, 'unblock'])->name('unblock');
        Route::post('{customer}/toggle-block', [CustomerController::class, 'toggleBlock'])->name('toggle-block');
    });

    // Orders routes
    Route::group(['prefix' => 'orders', 'as' => 'orders.'], function () {
        Route::get('', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('index');
        Route::get('{order}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->name('show');
        Route::get('{order}/invoice', [App\Http\Controllers\Admin\OrderController::class, 'invoice'])->name('invoice');
        Route::post('{order}/accept', [App\Http\Controllers\Admin\OrderController::class, 'accept'])->name('accept');
        Route::post('{order}/reject', [App\Http\Controllers\Admin\OrderController::class, 'reject'])
            ->middleware('feature:orders.admin_cancel')
            ->name('reject');
        Route::patch('{order}/delivery-fee', [App\Http\Controllers\Admin\OrderController::class, 'setDeliveryFee'])->name('delivery-fee');
        // ── Strict forward workflow transitions: accepted → ready → delivered → completed ──
        Route::patch('{order}/ready', [App\Http\Controllers\Admin\OrderController::class, 'markReady'])->name('ready');
        Route::patch('{order}/delivered', [App\Http\Controllers\Admin\OrderController::class, 'markDelivered'])->name('delivered');
        Route::patch('{order}/completed', [App\Http\Controllers\Admin\OrderController::class, 'markCompleted'])->name('completed');
        Route::patch('{order}/mark-paid', [App\Http\Controllers\Admin\OrderController::class, 'markAsPaid'])->name('mark-paid');
        // ── Test push notification (admin-only dev/QA tool) ───────────────────
        Route::post('test-notification', [App\Http\Controllers\Admin\OrderController::class, 'testNotification'])
            ->middleware('admin')
            ->name('test-notification');
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

    // Weights routes (gated by the weight-products feature)
    Route::group(['prefix' => 'weights', 'as' => 'weights.', 'middleware' => 'feature:products.weight_products'], function () {
        Route::get('', [App\Http\Controllers\Admin\WeightController::class, 'index'])->name('index');
        Route::middleware(['moderator'])->group(function () {
            Route::get('create', [App\Http\Controllers\Admin\WeightController::class, 'create'])->name('create');
            Route::post('', [App\Http\Controllers\Admin\WeightController::class, 'store'])->name('store');
            Route::get('{weight}/edit', [App\Http\Controllers\Admin\WeightController::class, 'edit'])->name('edit');
            Route::put('{weight}', [App\Http\Controllers\Admin\WeightController::class, 'update'])->name('update');
            Route::delete('{weight}', [App\Http\Controllers\Admin\WeightController::class, 'destroy'])->name('destroy');
        });
    });

    // Options routes (product options like cutting type) — gated by feature
    Route::group(['prefix' => 'options', 'as' => 'options.', 'middleware' => 'feature:products.options'], function () {
        Route::get('', [App\Http\Controllers\Admin\OptionController::class, 'index'])->name('index');
        Route::middleware(['moderator'])->group(function () {
            Route::get('create', [App\Http\Controllers\Admin\OptionController::class, 'create'])->name('create');
            Route::post('', [App\Http\Controllers\Admin\OptionController::class, 'store'])->name('store');
            Route::get('{option}/edit', [App\Http\Controllers\Admin\OptionController::class, 'edit'])->name('edit');
            Route::put('{option}', [App\Http\Controllers\Admin\OptionController::class, 'update'])->name('update');
            Route::delete('{option}', [App\Http\Controllers\Admin\OptionController::class, 'destroy'])->name('destroy');
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
