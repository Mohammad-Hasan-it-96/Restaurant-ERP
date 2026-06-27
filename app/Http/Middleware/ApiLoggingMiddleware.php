<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Shared fields (request_id, route, ip, user_agent, user_id, customer_id)
     * are auto-attached via InjectLogContext + the Context facade, so we only
     * record the bits that aren't already there: method, path, and status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
        ];

        // For order creation requests, log items count only (avoid logging full body)
        if ($request->is('api/v1/orders') && $request->isMethod('POST')) {
            $items = $request->input('items', []);
            $context['items_count'] = is_array($items) ? count($items) : 0;
        }

        logService()->info('api.request', $context);

        /** @var Response $response */
        $response = $next($request);

        logService()->info('api.response', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
