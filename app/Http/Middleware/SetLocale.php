<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Closure;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get locale from session
        $locale = session('locale');

        if ($locale) {
            // Check if the language exists and is active
            $language = Language::where('code', $locale)
                ->where('status', 1)
                ->first();

            if ($language) {
                App::setLocale($locale);
                session(['site_direction' => $language->direction]);
            }
        } else {
            // If no locale in session, use default language
            $defaultLanguage = Language::where('is_default', 1)->first();

            if ($defaultLanguage) {
                $locale = $defaultLanguage->code;
                App::setLocale($locale);
                session(['locale' => $locale]);
                session(['site_direction' => $defaultLanguage->direction]);
            }
        }

        return $next($request);
    }
}
