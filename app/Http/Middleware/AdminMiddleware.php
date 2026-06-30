<?php

namespace App\Http\Middleware;

use App\Support\Feature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Single-role mode: when the permissions system is disabled, the
        // admin/moderator distinction collapses — but access is still limited to
        // staff (admin or moderator). It never elevates a viewer/no-role account,
        // so a low-privilege user can't reach admin-only actions just by logging in.
        if (Auth::check() && Feature::disabled('admin.permissions_system')) {
            if (in_array(Auth::user()->role, ['admin', 'moderator'], true)) {
                return $next($request);
            }

            return redirect()->route('admin.dashboard')->with('error', 'You do not have permission to access this page.');
        }

        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        return redirect()->route('admin.dashboard')->with('error', 'You do not have permission to access this page.');
    }
}
