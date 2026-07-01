<?php

namespace Tests\Unit;

use App\Support\Feature;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_enabled_reflects_config(): void
    {
        config(['system_features.orders.modification' => true]);
        $this->assertTrue(Feature::enabled('orders.modification'));
        $this->assertFalse(Feature::disabled('orders.modification'));

        config(['system_features.orders.modification' => false]);
        $this->assertFalse(Feature::enabled('orders.modification'));
        $this->assertTrue(Feature::disabled('orders.modification'));
    }

    public function test_unknown_path_is_treated_as_disabled(): void
    {
        $this->assertFalse(Feature::enabled('does.not.exist'));
    }

    public function test_missing_inverted_flag_can_fail_closed_via_default(): void
    {
        // admin.permissions_system is INVERTED — a missing key must keep the
        // permission system ON (role separation intact), not collapse it. The
        // explicit default param is how the auth middlewares request that.
        // Drop the whole admin group so the key is genuinely absent (a present
        // null would be returned verbatim, bypassing the default).
        config(['system_features.admin' => []]);

        // Capability default (false) would fail open for this inverted flag…
        $this->assertTrue(Feature::disabled('admin.permissions_system'));
        // …but the fail-closed default the middlewares pass keeps it enabled.
        $this->assertTrue(Feature::enabled('admin.permissions_system', true));
        $this->assertFalse(Feature::disabled('admin.permissions_system', true));
    }

    public function test_feature_global_helper_wraps_resolver(): void
    {
        config(['system_features.products.options' => false]);
        $this->assertFalse(feature('products.options'));

        config(['system_features.products.options' => true]);
        $this->assertTrue(feature('products.options'));
    }

    public function test_client_safe_excludes_admin_only_flags(): void
    {
        $safe = Feature::clientSafe();

        // Flattened paths present in the client-safe map.
        $paths = [];
        foreach ($safe as $group => $flags) {
            foreach ($flags as $key => $value) {
                $paths[] = "{$group}.{$key}";
            }
        }

        $this->assertContains('orders.modification', $paths);
        $this->assertContains('customer.profile', $paths);
        $this->assertContains('localization.languages', $paths);

        // Admin-only flags must never leak to the public payload.
        $this->assertNotContains('admin.permissions_system', $paths);
        $this->assertNotContains('orders.admin_cancel', $paths);
    }

    public function test_feature_or_fail_passes_when_enabled(): void
    {
        config(['system_features.orders.admin_cancel' => true]);

        feature_or_fail('orders.admin_cancel');
        $this->assertTrue(true); // no exception thrown
    }

    public function test_feature_or_fail_aborts_403_when_disabled(): void
    {
        config(['system_features.orders.admin_cancel' => false]);

        try {
            feature_or_fail('orders.admin_cancel');
            $this->fail('Expected a 403 HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
