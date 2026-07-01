<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;

/**
 * EnsureInstalled — the install gate.
 *
 * Not installed → send all web traffic to the wizard. Installed → lock the
 * wizard (redirect /install* back home). Appended to the web group.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $onInstaller = $request->is('install', 'install/*');

        if (Installer::isInstalled()) {
            return $onInstaller ? redirect('/') : $next($request);
        }

        return $onInstaller ? $next($request) : redirect()->route('install.index');
    }
}
