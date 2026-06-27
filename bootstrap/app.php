<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ApiLoggingMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\InjectLogContext;
use App\Http\Middleware\MinifyHtml;
use App\Http\Middleware\ModeratorMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetLocale;
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
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
        //
    })
    ->create();
