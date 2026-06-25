<?php

namespace App\Http\Middleware;

use App\Support\Feature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureGate
{
    /**
     * Hard-block a route when its system feature flag is disabled.
     *
     * Usage (route middleware): `feature:orders.modification`
     * The parameter is a dot-path flag understood by App\Support\Feature.
     *
     * Applied to customer-facing WRITE routes so a disabled capability can
     * never be reached over the wire (the frontends also hide it, and
     * services no-op as defence-in-depth). Aborts 403 when off.
     */
    public function handle(Request $request, Closure $next, string $flag): Response
    {
        abort_unless(Feature::enabled($flag), 403, 'This feature is disabled.');

        return $next($request);
    }
}
