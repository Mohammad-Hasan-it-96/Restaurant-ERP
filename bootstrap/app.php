<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ApiLoggingMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\InjectLogContext;
use App\Http\Middleware\MinifyHtml;
use App\Http\Middleware\ModeratorMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // Activity-log retention pruning.
        $schedule->command('activitylog:prune')->daily();

        // Daily encrypted backup (DB + uploaded images + .env) and cleanup.
        $schedule->command('backup:clean')->daily()->at(config('backup.schedule.clean_at', '01:30'));
        $schedule->command('backup:run')->daily()->at(config('backup.schedule.run_at', '02:00'));
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureInstalled::class,
            InjectLogContext::class,
            SetLocale::class,
            MinifyHtml::class,
        ]);

        $middleware->api(append: [
            InjectLogContext::class,
            SetLocale::class,
            ApiLoggingMiddleware::class,
        ]);

        // Register custom middleware
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'moderator' => ModeratorMiddleware::class,
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'customer.session' => \App\Http\Middleware\EnsureCustomerSession::class,
            'customer.start' => \App\Http\Middleware\CustomerSession::class,
            'customer.token' => \App\Http\Middleware\ResolveCustomerByToken::class,
            'cache.headers' => \App\Http\Middleware\SetCacheHeaders::class,
            'feature' => \App\Http\Middleware\FeatureGate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Raise a Telegram alert for uncaught/unhandled exceptions (the "fatal"
        // category) WITHOUT altering normal logging — we do not return false, so
        // Laravel's default report (with stack trace) still reaches the log file.
        // The gate is a no-op unless alerting is enabled for the environment.
        $exceptions->report(function (\Throwable $e) {
            try {
                app(\App\Services\TelegramAlertGate::class)->handle([
                    'level' => 'CRITICAL',
                    'level_value' => 500,
                    'message' => 'app.unhandled_exception',
                    'context' => [
                        'fatal' => true,
                        'exception' => [
                            'class' => get_class($e),
                            'message' => $e->getMessage(),
                            'file' => $e->getFile().':'.$e->getLine(),
                        ],
                    ],
                ]);
            } catch (\Throwable $ignored) {
                // Alerting must never interfere with exception handling.
            }
        });
    })
    ->create();
