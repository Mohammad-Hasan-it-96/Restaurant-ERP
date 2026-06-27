<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * InjectLogContext
 *
 * Populates Laravel's Context with the per-request fields we want on EVERY log
 * line — app logs, framework logs, and uncaught-exception logs alike. Because
 * the Context facade feeds Monolog automatically, no individual log call needs
 * to repeat ip / user_agent / route / request_id ever again.
 *
 * Runs at the front of both the `web` and `api` middleware groups (registered
 * in bootstrap/app.php), before ApiLoggingMiddleware, so the request/response
 * log lines are already enriched.
 *
 * Fields added here:
 *   - request_id : correlation id, also returned as the X-Request-Id header
 *   - route      : route name when available, else the request path
 *   - ip         : client IP
 *   - user_agent : client UA string
 *   - user_id    : authenticated admin/user id (null for guests)
 *
 * customer_id is added later by the customer-resolving middleware
 * (ResolveCustomerByToken / EnsureCustomerSession) since it is only known after
 * those run. order_id is added per-call via LogService context, or pushed to
 * Context by controllers that route-model-bind an Order.
 */
class InjectLogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        Context::add('request_id', $requestId);
        Context::add('route', optional($request->route())->getName() ?? $request->path());
        Context::add('ip', $request->ip());
        Context::add('user_agent', (string) $request->userAgent());
        Context::add('user_id', $request->user()?->getAuthIdentifier());

        $response = $next($request);

        // Expose the correlation id so clients (and the SPA's frontend logger)
        // can echo it back, tying browser-side errors to this backend request.
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
