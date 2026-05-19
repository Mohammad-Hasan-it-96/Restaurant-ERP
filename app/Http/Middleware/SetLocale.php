<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Closure;

class SetLocale
{
    /** Supported locale codes */
    private const ALLOWED = ['ar', 'en'];

    public function handle(Request $request, Closure $next)
    {
        // 1. API requests: honour Accept-Language header first (sent by SPA)
        $localeFromHeader = $this->parseAcceptLanguage($request);

        // 2. Fall back to session (web), then DB default
        $locale = $localeFromHeader
            ?? session('locale')
            ?? $this->dbDefault();

        if ($locale && in_array($locale, self::ALLOWED, true)) {
            App::setLocale($locale);
            // Keep session in sync for web routes
            if (! $request->is('api/*')) {
                session(['locale' => $locale]);
            }
        }

        return $next($request);
    }

    /** Extract first matching locale from Accept-Language header, e.g. "ar,en;q=0.9" */
    private function parseAcceptLanguage(Request $request): ?string
    {
        $header = $request->header('Accept-Language', '');
        if (! $header) return null;

        foreach (explode(',', $header) as $part) {
            $code = strtolower(trim(explode(';', $part)[0]));
            // Accept "ar", "ar-SY", "ar-*" → normalise to 2-char code
            $short = substr($code, 0, 2);
            if (in_array($short, self::ALLOWED, true)) {
                return $short;
            }
        }
        return null;
    }

    private function dbDefault(): ?string
    {
        $lang = Language::query()->where('is_default', 1)->first();
        return $lang?->code;
    }
}
