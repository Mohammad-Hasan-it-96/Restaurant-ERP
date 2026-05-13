<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * CustomerSession
 *
 * Boots a Laravel session for the customer SPA API routes using a
 * SEPARATE cookie name ('customer_spa_session') so it never touches
 * the admin panel's 'restaurant_session' cookie.
 *
 * Without this separation, POST /api/v1/orders would open the admin's
 * session record (same cookie, same DB row), write customer_id into it,
 * and effectively corrupt the admin's auth payload — logging them out.
 */
class CustomerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Override the session cookie name BEFORE StartSession boots.
        // This causes Laravel to read/write a completely separate cookie
        // and a separate sessions table record from the admin web session.
        config(['session.cookie' => 'customer_spa_session']);

        return app(StartSession::class)->handle($request, $next);
    }
}

