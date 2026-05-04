<?php

namespace App\Providers;

use App\Services\SystemConfigService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind SystemConfigService as a singleton so a single instance
        // is reused for the lifetime of each request.
        $this->app->singleton(SystemConfigService::class, function () {
            return new SystemConfigService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
