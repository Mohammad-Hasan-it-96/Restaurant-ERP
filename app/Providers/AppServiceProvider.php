<?php

namespace App\Providers;

use App\Services\SystemConfigService;
use App\Support\Feature;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind SystemConfigService as a singleton so a single instance
        // is reused for the lifetime of each request.
        $this->app->singleton(SystemConfigService::class, function () {
            return new SystemConfigService;
        });
    }

    public function boot(): void
    {
        // Use Bootstrap 5 pagination views across the entire application
        Paginator::useBootstrapFive();

        // @feature('orders.modification') ... @endfeature — wrap Blade blocks
        // that should only render when a system feature flag is enabled.
        Blade::if('feature', fn (string $path) => Feature::enabled($path));

        // Route EVERY failed queue job into the "queue" alert category centrally
        // (critical level → Telegram when alerting is enabled).
        Queue::failing(function (JobFailed $event) {
            logService()->critical('queue.job.failed', [
                'connection' => $event->connectionName,
                'queue' => method_exists($event->job, 'getQueue') ? $event->job->getQueue() : null,
                'job' => $event->job->resolveName(),
            ], $event->exception);
        });

        // Surface backup health through the standard logger (mail is disabled in
        // config/backup.php). This is also the seam for future Telegram alerting.
        Event::listen(BackupWasSuccessful::class, function (BackupWasSuccessful $event) {
            logService()->info('backup.completed', [
                'disk' => $event->backupDestination->diskName(),
            ]);
        });
        Event::listen(BackupHasFailed::class, function (BackupHasFailed $event) {
            logService()->error('backup.failed', [], $event->exception);
        });
        Event::listen(CleanupHasFailed::class, function (CleanupHasFailed $event) {
            logService()->error('backup.cleanup_failed', [], $event->exception);
        });
    }
}
