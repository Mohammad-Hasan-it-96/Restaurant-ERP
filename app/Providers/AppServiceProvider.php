<?php

namespace App\Providers;

use App\Services\SystemConfigService;
use App\Support\Feature;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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

        // Brute-force protection for admin login. Two stacked limits:
        //  • 5/min per email+IP — bounds guessing against a single account.
        //  • 20/min per IP — caps total attempts from one source so spraying a
        //    shared password across many staff emails can't dodge the per-email
        //    bucket. Applied via the `throttle:login` middleware on the login route.
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by('ip:'.$request->ip()),
            ];
        });

        // Token-authenticated customer API: key the limit on the customer's token
        // (hashed, so the secret isn't stored in cache keys) rather than IP alone,
        // so customers behind shared NAT don't share a budget. Falls back to IP for
        // anything without a token. Same rate as the default v1 group throttle.
        RateLimiter::for('customer_api', function (Request $request) {
            $perMinute = (int) explode(',', (string) config('api.throttle.default', '60,1'))[0];
            $token = $request->bearerToken();
            $key = $token ? 'tok:'.sha1($token) : 'ip:'.$request->ip();

            return Limit::perMinute($perMinute)->by($key);
        });

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
