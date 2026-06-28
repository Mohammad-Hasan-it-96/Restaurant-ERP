<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_matches_config(): void
    {
        $this->assertSame(config('version.current'), Version::current());
        $this->assertSame(config('version.released_at'), Version::releasedAt());
    }

    public function test_current_is_valid_semver(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Version::current());
    }

    public function test_public_version_endpoint_returns_version_and_date(): void
    {
        $this->getJson('/api/v1/version')
            ->assertOk()
            ->assertJsonPath('data.version', Version::current())
            ->assertJsonPath('data.released_at', Version::releasedAt());
    }

    public function test_health_report_includes_app_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson('/api/health')
            ->assertJsonPath('versions.app', Version::current());
    }

    public function test_release_notes_page_loads_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.release-notes.index'))
            ->assertOk()
            ->assertSee('v'.Version::current());
    }

    public function test_release_notes_page_is_gated_for_guests(): void
    {
        $this->get(route('admin.release-notes.index'))->assertRedirect();
    }
}
