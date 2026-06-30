<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\SystemConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the centralized model-event cache invalidation: every write self-
 * invalidates its domain cache, with no caller having to call clearCache().
 */
class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    // ─── SystemConfig: per-group cache (the fixed staleness bug) ──────────

    public function test_creating_config_invalidates_group_cache(): void
    {
        // Prime the cached group list.
        $this->assertArrayNotHasKey('new_key', SystemConfig::getGroup('general'));

        SystemConfig::create(['key' => 'new_key', 'value' => 'v1', 'group' => 'general']);

        // Must appear immediately — not after the 24h TTL.
        $this->assertSame('v1', SystemConfig::getGroup('general')['new_key'] ?? null);
    }

    public function test_updating_config_invalidates_group_cache(): void
    {
        SystemConfig::set('hours', 'old', 'general');
        $this->assertSame('old', SystemConfig::getGroup('general')['hours'] ?? null);

        SystemConfig::set('hours', 'new', 'general');

        $this->assertSame('new', SystemConfig::getGroup('general')['hours'] ?? null);
    }

    public function test_deleting_config_invalidates_group_cache(): void
    {
        $config = SystemConfig::create(['key' => 'temp', 'value' => '1', 'group' => 'general']);
        $this->assertArrayHasKey('temp', SystemConfig::getGroup('general'));

        $config->delete();

        $this->assertArrayNotHasKey('temp', SystemConfig::getGroup('general'));
    }

    public function test_regrouping_config_invalidates_old_group_cache(): void
    {
        $config = SystemConfig::create(['key' => 'movable', 'value' => '1', 'group' => 'general']);
        $this->assertArrayHasKey('movable', SystemConfig::getGroup('general'));

        $config->update(['group' => 'restaurant']);

        $this->assertArrayNotHasKey('movable', SystemConfig::getGroup('general'));
        $this->assertArrayHasKey('movable', SystemConfig::getGroup('restaurant'));
    }

    // ─── DeliveryZone: new server-side cache + event invalidation ─────────

    public function test_delivery_zone_endpoint_reflects_writes(): void
    {
        $zone = DeliveryZone::create(['area_name' => 'Old Town', 'estimated_fee' => 5, 'is_active' => true]);

        // Prime the cached endpoint.
        $this->getJson('/api/v1/delivery-zones')
            ->assertOk()
            ->assertJsonFragment(['area_name' => 'Old Town']);

        $zone->update(['area_name' => 'New Town']);

        // Cache flushed by the model event → updated value on next request.
        $this->getJson('/api/v1/delivery-zones')
            ->assertOk()
            ->assertJsonFragment(['area_name' => 'New Town'])
            ->assertJsonMissing(['area_name' => 'Old Town']);
    }

    // ─── Category: existing behavior still holds under config-driven TTL ──

    public function test_category_endpoint_reflects_writes(): void
    {
        Category::create(['name_ar' => 'قديم', 'name_en' => 'Old', 'is_active' => true]);

        // Assert on name_en (always present); `name` is locale-resolved.
        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonFragment(['name_en' => 'Old']);

        Category::query()->first()->update(['name_en' => 'Fresh']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonFragment(['name_en' => 'Fresh'])
            ->assertJsonMissing(['name_en' => 'Old']);
    }
}
