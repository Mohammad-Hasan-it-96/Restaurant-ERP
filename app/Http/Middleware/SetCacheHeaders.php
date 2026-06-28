<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Add public browser-caching headers to safe, cacheable responses.
     *
     * Usage (route middleware): `cache.headers` or `cache.headers:600`
     * The optional parameter is max-age in seconds (default 300 / 5 minutes).
     *
     * Only GET/HEAD requests with a successful (2xx) response are cached so we
     * never hand a stale error or a mutation result to the browser. A
     * `Vary: Accept-Language` header is added because the cached payloads
     * (products, categories) are locale-dependent — the SPA picks the language
     * via the Accept-Language header — and we must not let a shared cache serve
     * one language's response for another.
     */
    public function handle(Request $request, Closure $next, ?int $maxAge = null): Response
    {
        $maxAge ??= (int) config('api.http_cache_max_age', 300);
        $response = $next($request);

        if (! $request->isMethodCacheable() || ! $response->isSuccessful()) {
            return $response;
        }

        $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
        $response->setVary('Accept-Language', false);

        return $response;
    }
}
