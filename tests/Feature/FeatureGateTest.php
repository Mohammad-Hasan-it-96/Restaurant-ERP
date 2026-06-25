<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FeatureGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_passes_when_feature_enabled(): void
    {
        Route::middleware('feature:orders.modification')
            ->get('/__test/feature-gate', fn () => response('ok'));

        config(['system_features.orders.modification' => true]);

        $this->get('/__test/feature-gate')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_route_is_blocked_with_403_when_feature_disabled(): void
    {
        Route::middleware('feature:orders.modification')
            ->get('/__test/feature-gate', fn () => response('ok'));

        config(['system_features.orders.modification' => false]);

        $this->get('/__test/feature-gate')->assertForbidden();
    }

    public function test_public_settings_exposes_only_client_safe_features(): void
    {
        $response = $this->getJson('/api/v1/settings/public');

        $response->assertOk()
            ->assertJsonPath('data.features.orders.modification', true)
            ->assertJsonMissingPath('data.features.admin.permissions_system');
    }
}
