<?php

namespace App\Support;

/**
 * Feature
 *
 * The single resolver for system feature flags. Everything that needs to
 * know "is feature X on?" goes through Feature::enabled() — controllers,
 * services, middleware, the global feature() helper and the @feature Blade
 * directive all funnel here.
 *
 * v1 reads flags straight from config/system_features.php (no DB hit, fast).
 * This class is deliberately the ONLY place that resolves a flag, so a future
 * per-restaurant / DB override layer can be added here without touching any
 * call site (see the plan's "Future Scalability" section).
 *
 * Usage:
 *   Feature::enabled('orders.modification')
 *   feature('orders.modification')          // global helper wrapper
 *
 *   @feature('products.options') ... @endfeature
 */
class Feature
{
    /**
     * Is the given feature flag enabled?
     *
     * @param  string  $path  Dot path, e.g. "orders.modification".
     * @param  bool  $default  Value to assume when the config key is absent.
     *                         Defaults to false (fail-closed) for capability
     *                         flags; pass true for INVERTED flags such as
     *                         admin.permissions_system, where a missing key
     *                         must keep the restriction in place.
     */
    public static function enabled(string $path, bool $default = false): bool
    {
        // ── Future seam ──────────────────────────────────────────────
        // A per-restaurant/DB override would be resolved here first and
        // fall back to config below. v1 is config-only.
        return (bool) config("system_features.{$path}", $default);
    }

    /**
     * Convenience inverse of enabled().
     */
    public static function disabled(string $path, bool $default = false): bool
    {
        return ! static::enabled($path, $default);
    }

    /**
     * The full flag tree (every group), resolved to booleans.
     * For tooling/debugging — NOT for exposing to frontends.
     *
     * @return array<string, array<string, bool>>
     */
    public static function all(): array
    {
        $groups = config('system_features', []);
        unset($groups['client_safe']);

        $resolved = [];
        foreach ($groups as $group => $flags) {
            if (! is_array($flags)) {
                continue;
            }
            foreach ($flags as $key => $value) {
                $resolved[$group][$key] = (bool) $value;
            }
        }

        return $resolved;
    }

    /**
     * The whitelisted subset of flags safe to expose to public frontends
     * (the customer SPA). Admin-only flags are never included. Returned as a
     * nested map mirroring the config groups so the SPA reads
     * `settings.features.orders.modification`.
     *
     * @return array<string, array<string, bool>>
     */
    public static function clientSafe(): array
    {
        $safe = [];

        foreach (config('system_features.client_safe', []) as $path) {
            [$group, $key] = array_pad(explode('.', $path, 2), 2, null);

            if ($group === null || $key === null) {
                continue;
            }

            $safe[$group][$key] = static::enabled($path);
        }

        return $safe;
    }
}
