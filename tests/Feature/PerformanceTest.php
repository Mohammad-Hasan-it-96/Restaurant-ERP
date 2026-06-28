<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Smoke test for the dashboard after caching the heavy aggregates +
     * collapsing the per-day/per-type count loops — it must still render.
     */
    public function test_dashboard_renders_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    /**
     * Regression guard: the public read-only API GETs carry browser/CDN cache
     * headers (cache.headers:300 applied in routes/api.php).
     */
    public function test_public_endpoints_send_cache_headers(): void
    {
        foreach (['/api/v1/version', '/api/v1/categories', '/api/v1/settings/public'] as $url) {
            $response = $this->getJson($url);

            $response->assertOk();
            // Symfony reorders Cache-Control directives, so assert membership.
            $cacheControl = (string) $response->headers->get('Cache-Control');
            $this->assertStringContainsString('public', $cacheControl);
            $this->assertStringContainsString('max-age=300', $cacheControl);
            $this->assertStringContainsString('Accept-Language', (string) $response->headers->get('Vary'));
        }
    }
}
