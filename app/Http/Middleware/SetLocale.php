<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/*')) {
            // API requests: honour Accept-Language header first (sent by SPA),
            // then fall back to session or DB default
            $locale = $this->parseAcceptLanguage($request)
                ?? session('locale')
                ?? $this->dbDefault();
        } else {
            // Web requests: session (user-selected) takes priority,
            // then DB default — never override with browser's Accept-Language
            $locale = session('locale')
                ?? $this->dbDefault();
        }

        // Normalize to lowercase for comparison
        $locale = $locale ? strtolower($locale) : null;

        // Validate against active languages in the DB
        if ($locale && $this->isAllowed($locale)) {
            App::setLocale($locale);
            // Keep session in sync for web routes (normalised to lowercase)
            if (! $request->is('api/*')) {
                session(['locale' => $locale]);
            }
        }

        return $next($request);
    }

    /** Check if the locale is an active language in the database */
    private function isAllowed(string $locale): bool
    {
        return Language::query()
            ->where('status', 1)
            ->whereRaw('LOWER(code) = ?', [$locale])
            ->exists();
    }

    /** Extract first matching locale from Accept-Language header, e.g. "ar,en;q=0.9" */
    private function parseAcceptLanguage(Request $request): ?string
    {
        $header = $request->header('Accept-Language', '');
        if (! $header) {
            return null;
        }

        // Get list of active language codes (lowercase) from DB
        $activeCodes = Language::query()
            ->where('status', 1)
            ->pluck('code')
            ->map(fn ($c) => strtolower($c))
            ->toArray();

        foreach (explode(',', $header) as $part) {
            $code = strtolower(trim(explode(';', $part)[0]));
            // Accept "ar", "ar-SY", "ar-*" → normalise to 2-char code
            $short = substr($code, 0, 2);
            if (in_array($short, $activeCodes, true)) {
                return $short;
            }
        }

        return null;
    }

    private function dbDefault(): ?string
    {
        $lang = Language::query()->where('is_default', 1)->first();

        return $lang ? strtolower($lang->code) : null;
    }
}
