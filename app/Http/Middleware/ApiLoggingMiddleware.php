<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = [
            'method'     => $request->method(),
            'url'        => $request->fullUrl(),
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // For order creation requests, log items count only (avoid logging full body)
        if ($request->is('api/v1/orders') && $request->isMethod('POST')) {
            $items = $request->input('items', []);
            $context['items_count'] = is_array($items) ? count($items) : 0;
        }

        Log::info('api.request', $context);

        /** @var Response $response */
        $response = $next($request);

        Log::info('api.response', [
            'method' => $request->method(),
            'url'    => $request->fullUrl(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}

