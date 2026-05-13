<?php

namespace App\Providers;

use App\Services\SystemConfigService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind SystemConfigService as a singleton so a single instance
        // is reused for the lifetime of each request.
        $this->app->singleton(SystemConfigService::class, function () {
            return new SystemConfigService();
        });
    }

    public function boot(): void
    {
        // Use Bootstrap 5 pagination views across the entire application
        Paginator::useBootstrapFive();
    }
}
