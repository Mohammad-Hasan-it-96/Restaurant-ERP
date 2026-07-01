<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_is_ok_with_versions_and_all_checks(): void
    {
        $report = app(HealthService::class)->report();

        $this->assertSame('ok', $report['status']);
        $this->assertArrayHasKey('php', $report['versions']);
        $this->assertArrayHasKey('laravel', $report['versions']);

        foreach (['database', 'cache', 'storage', 'queue', 'disk'] as $check) {
            $this->assertArrayHasKey($check, $report['checks']);
        }
    }

    public function test_high_disk_usage_is_degraded_not_error(): void
    {
        config(['health.thresholds.disk_warn_percent' => 0]);

        $report = app(HealthService::class)->report();

        $this->assertSame('degraded', $report['status']);
        $this->assertSame('degraded', $report['checks']['disk']['status']);
        // Criticals stay healthy → never a 503-worthy error.
        $this->assertSame('ok', $report['checks']['database']['status']);
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/health')->assertUnauthorized();
    }

    public function test_non_admin_is_blocked(): void
    {
        $user = User::factory()->create(['role' => 'moderator']);

        // AdminMiddleware redirects non-admins away (not 200 / no report leaked).
        $this->actingAs($user)->get('/api/health')->assertRedirect();
    }

    public function test_admin_gets_full_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => ['database', 'cache', 'queue', 'storage', 'disk'],
                'versions' => ['php', 'laravel'],
            ]);
    }
}
