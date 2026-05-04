<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Helpers
{
    /**
     * Get the default language code
     *
     * @return string
     */
    public static function default_lang()
    {
        return config('app.locale', 'en');
    }

    /**
     * Remove invalid characters from a string
     *
     * @param string $str
     * @return string
     */
    public static function remove_invalid_charcaters($str)
    {
        return str_replace(["'", '"', ',', ';', '<', '>', '?'], '', $str);
    }

    /**
     * Translate a key to the current locale
     *
     * @param string $key
     * @return string
     */
    public static function translate($key, $replace = [], $locale = null)
    {
        // Get the current locale or use the provided one
        $locale = $locale ?: App::getLocale();

        // First try to get from app.php translations
        $translation = __("app.$key", $replace, $locale);

        // If the translation is the same as the key with 'app.' prefix, it means it wasn't found
        if ($translation === "app.$key") {
            // Try to get from other translation files without prefix
            $translation = __($key, $replace, $locale);

            // If still not found, return the key itself
            if ($translation === $key) {
                // For debugging - log missing translations
                Log::warning("Translation missing for key: $key in locale: $locale");
                return $key;
            }
        }

        return $translation;
    }
}
