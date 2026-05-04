<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    // ─── Typed getters ────────────────────────────────────────────

    /**
     * Get a value decoded as an associative array/list from JSON.
     * Returns $default if the key is missing or the value is not valid JSON.
     */
    public static function getJson(string $key, $default = []): mixed
    {
        $raw = self::get($key);

        if ($raw === null || $raw === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);

        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
    }

    /**
     * Get a value as a boolean.
     * Truthy strings: "true", "1", "yes", "on"  (case-insensitive).
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $raw = self::get($key);

        if ($raw === null) {
            return $default;
        }

        return in_array(strtolower((string) $raw), ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Get a value cast to a number (int or float depending on content).
     */
    public static function getNumber(string $key, int|float $default = 0): int|float
    {
        $raw = self::get($key);

        if ($raw === null || $raw === '') {
            return $default;
        }

        return is_numeric($raw) ? ($raw + 0) : $default;
    }

    /**
     * Cache duration in seconds (default: 24 hours)
     */
    protected static $cacheDuration = 86400;

    /**
     * Cache key prefix
     */
    protected static $cachePrefix = 'system_config_';

    /**
     * Get a configuration value by key
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value
     */
    public static function get($key, $default = null)
    {
        // Try to get from cache first
        $cacheKey = self::$cachePrefix . $key;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // If not in cache, get from database
        $config = self::where('key', $key)->first();

        if (!$config) {
            return $default;
        }

        // Store in cache for future requests
        Cache::put($cacheKey, $config->value, self::$cacheDuration);

        return $config->value;
    }

    /**
     * Set a configuration value.
     * Arrays and objects are automatically JSON-encoded.
     *
     * @param string $key   The configuration key
     * @param mixed  $value The value to set (arrays auto-encoded to JSON)
     * @param string $group The group this config belongs to
     * @return bool Success status
     */
    public static function set($key, $value, $group = 'general')
    {
        // Auto-encode arrays/objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Update or create the config
        $config = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        // Update the cache
        $cacheKey = self::$cachePrefix . $key;
        Cache::put($cacheKey, $value, self::$cacheDuration);

        return $config ? true : false;
    }

    /**
     * Get all configurations by group
     *
     * @param string $group The group name
     * @return array Configurations in the specified group
     */
    public static function getGroup($group = 'general')
    {
        $cacheKey = self::$cachePrefix . 'group_' . $group;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $configs = self::where('group', $group)->get()->pluck('value', 'key')->toArray();

        Cache::put($cacheKey, $configs, self::$cacheDuration);

        return $configs;
    }

    /**
     * Clear the cache for a specific key or group
     *
     * @param string|null $key   The key to clear (or null to clear all)
     * @param string|null $group The group to clear
     * @return void
     */
    public static function clearCache($key = null, $group = null)
    {
        if ($key) {
            Cache::forget(self::$cachePrefix . $key);
        } elseif ($group) {
            Cache::forget(self::$cachePrefix . 'group_' . $group);
        } else {
            // Clear all system config cache
            $keys = self::all()->pluck('key')->toArray();
            foreach ($keys as $k) {
                Cache::forget(self::$cachePrefix . $k);
            }

            // Clear all group caches
            $groups = self::distinct()->pluck('group')->toArray();
            foreach ($groups as $g) {
                Cache::forget(self::$cachePrefix . 'group_' . $g);
            }
        }
    }
}
