<?php

namespace App\Services;

use App\Models\SystemConfig;
use Illuminate\Support\Carbon;

/**
 * SystemConfigService
 *
 * Centralised service for reading and writing system configurations.
 * Restaurant-specific helpers (opening hours, order acceptance) live here
 * so nothing else needs to know the raw storage format.
 *
 * Usage (via DI or helper):
 *   app(SystemConfigService::class)->isOpenAt()
 *   app(SystemConfigService::class)->getRejectionReasons()
 */
class SystemConfigService
{
    // ─── Generic CRUD ─────────────────────────────────────────────

    /**
     * Get a raw string config value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return SystemConfig::get($key, $default);
    }

    /**
     * Set a config value (arrays are auto-encoded to JSON).
     *
     * @param  mixed  $value  Scalar, array, or null
     * @param  string  $group  Config group (default: restaurant)
     */
    public function set(string $key, mixed $value, string $group = 'restaurant'): bool
    {
        return SystemConfig::set($key, $value, $group);
    }

    // ─── Typed helpers ─────────────────────────────────────────────

    /**
     * Get a config value decoded from JSON.
     */
    public function getJson(string $key, mixed $default = []): mixed
    {
        return SystemConfig::getJson($key, $default);
    }

    /**
     * Get a config value as a boolean.
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return SystemConfig::getBool($key, $default);
    }

    /**
     * Get a config value as a number.
     */
    public function getNumber(string $key, int|float $default = 0): int|float
    {
        return SystemConfig::getNumber($key, $default);
    }

    /**
     * Return the first non-empty text value from a list of keys.
     */
    public function getFirstText(array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            $value = $this->getText($key, '');
            if ($value !== '') {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get a config value as normalized plain text.
     *
     * This handles cases where text was accidentally stored as a JSON string
     * (for example: "\"\\u0627...\"").
     */
    public function getText(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            return $default;
        }

        if (! is_string($value)) {
            return (string) $value;
        }

        return $this->decodePossiblyEscapedText($value);
    }

    /**
     * Decode a raw text value that may be JSON-encoded or unicode-escaped.
     */
    protected function decodePossiblyEscapedText(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        // Treat explicit null-like strings as empty
        if (in_array(strtolower($trimmed), ['null', 'undefined'], true)) {
            return '';
        }

        // Case 1: full JSON string, e.g. "\"مطعم\"" or "\"\\u0627...\""
        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
            return trim($decoded);
        }

        // Case 2: raw string containing unicode escapes, e.g. "\\u0627\\u0644..."
        if (str_contains($trimmed, '\\u')) {
            $wrapped = '"'.str_replace('"', '\\"', $trimmed).'"';
            $decodedWrapped = json_decode($wrapped, true);

            if (json_last_error() === JSON_ERROR_NONE && is_string($decodedWrapped)) {
                return trim($decodedWrapped);
            }
        }

        return $trimmed;
    }

    // ─── Restaurant-specific helpers ───────────────────────────────

    /**
     * Return an associative array of all public-facing restaurant settings.
     *
     * Keys: restaurant_name, restaurant_logo, restaurant_phone,
     *       restaurant_whatsapp, is_accepting_orders, order_closed_message,
     *       delivery_note, opening_hours (decoded), rejection_reasons (decoded)
     */
    public function getPublicSettings(): array
    {
        return [
            'restaurant_name' => $this->getFirstText(['restaurant_name_ar', 'restaurant_name', 'site_name'], config('app.name', '')),
            'restaurant_name_en' => $this->getFirstText(['restaurant_name_en'], ''),
            'restaurant_logo' => $this->get('restaurant_logo'),
            'restaurant_phone' => $this->get('restaurant_phone'),
            'restaurant_whatsapp' => $this->get('restaurant_whatsapp'),
            'is_accepting_orders' => $this->isAcceptingOrders(),
            'order_closed_message' => $this->get('order_closed_message', ''),
            'delivery_note' => $this->get('delivery_note', ''),
            'opening_hours' => $this->getOpeningHours(),
            'rejection_reasons' => $this->getRejectionReasons(),
        ];
    }

    /**
     * Return the opening_hours config as an associative array.
     *
     * Shape: ['saturday' => ['is_open' => true, 'from' => '10:00', 'to' => '23:00'], ...]
     */
    public function getOpeningHours(): array
    {
        return SystemConfig::getJson('opening_hours', []);
    }

    /**
     * Return the rejection_reasons config as a plain list.
     *
     * @return string[]
     */
    public function getRejectionReasons(): array
    {
        return SystemConfig::getJson('rejection_reasons', []);
    }

    /**
     * Whether the restaurant is currently set to accept orders
     * (the boolean admin toggle, regardless of opening hours).
     */
    public function isAcceptingOrders(): bool
    {
        return SystemConfig::getBool('is_accepting_orders', true);
    }

    /**
     * Full availability check: is the restaurant open at the given moment?
     *
     * Checks (in order):
     *   1. is_accepting_orders global toggle
     *   2. opening_hours for the current day
     *   3. Current time vs from/to window
     *
     * @param  Carbon|null  $dateTime  Defaults to Carbon::now()
     */
    public function isOpenAt(?Carbon $dateTime = null): bool
    {
        // Global kill-switch
        if (! $this->isAcceptingOrders()) {
            return false;
        }

        $now = $dateTime ?? Carbon::now();
        $day = strtolower($now->format('l')); // e.g. "saturday"
        $hours = $this->getOpeningHours();

        // No opening_hours configured at all → treat as open 24/7 (no restriction)
        if (empty($hours)) {
            return true;
        }

        // Hours are configured but this day has no entry → treat as closed
        if (empty($hours[$day])) {
            return false;
        }

        $dayConfig = $hours[$day];

        // Day explicitly marked as closed
        if (! ($dayConfig['is_open'] ?? false)) {
            return false;
        }

        // Parse from/to – both must be present and valid HH:MM
        $from = $dayConfig['from'] ?? null;
        $to = $dayConfig['to'] ?? null;

        if (! $from || ! $to) {
            // Misconfigured – assume open all day if the day is marked open
            return true;
        }

        [$fromH, $fromM] = array_map('intval', explode(':', $from));
        [$toH,   $toM] = array_map('intval', explode(':', $to));

        $openMinutes = $fromH * 60 + $fromM;
        $closeMinutes = $toH * 60 + $toM;
        $nowMinutes = $now->hour * 60 + $now->minute;

        // Handle midnight-crossing windows (e.g. 22:00 – 02:00)
        if ($closeMinutes < $openMinutes) {
            return $nowMinutes >= $openMinutes || $nowMinutes < $closeMinutes;
        }

        return $nowMinutes >= $openMinutes && $nowMinutes < $closeMinutes;
    }
}
