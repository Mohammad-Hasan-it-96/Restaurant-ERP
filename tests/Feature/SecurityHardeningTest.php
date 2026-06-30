<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regression tests for the Critical + High security fixes:
 * C1 (no public registration), C2/H4 (login & reset throttling), C3/C4/H2/H3
 * (role-gated admin routes), H1 (single-role mode still excludes viewers),
 * C5 (guest-merge account takeover blocked).
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login'); // isolate throttle state between tests
    }

    // ─── C1: public registration removed ─────────────────────────────────

    public function test_register_routes_are_gone(): void
    {
        $this->get('/auth/register')->assertNotFound();
        $this->post('/auth/register', [
            'name' => 'Mallory',
            'email' => 'mallory@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'mallory@example.com']);
    }

    // ─── C2: login brute-force throttle ──────────────────────────────────

    public function test_login_is_rate_limited(): void
    {
        $payload = ['email' => 'admin@example.com', 'password' => 'wrong-password'];

        // 5 attempts/minute allowed; the 6th is blocked with 429.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/auth/login', $payload)->assertStatus(302);
        }

        $this->post('/auth/login', $payload)->assertStatus(429);
    }

    // ─── H4: password reset is generic (no enumeration) ──────────────────

    public function test_forgot_password_returns_generic_response(): void
    {
        $this->post('/auth/forgot-password', ['email' => 'does-not-exist@example.com'])
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
    }

    // ─── C3/C4/H2/H3: role gating on admin routes ────────────────────────

    public static function protectedRoutes(): array
    {
        return [
            'orders' => ['/admin/orders'],
            'customers' => ['/admin/customers'],
            'reports' => ['/admin/reports'],
            'log viewer' => ['/admin/system-secure-metrics-health-logs'],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_viewer_role_is_blocked_from_staff_routes(string $url): void
    {
        $viewer = User::factory()->create(); // factory default role = 'user' (viewer)

        $this->actingAs($viewer)->get($url)->assertRedirect(route('admin.dashboard'));
    }

    #[DataProvider('protectedRoutes')]
    public function test_guest_is_redirected_to_login(string $url): void
    {
        $this->get($url)->assertRedirect(route('auth.view_login'));
    }

    public function test_staff_can_reach_customers_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/customers')->assertOk();

        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($moderator)->get('/admin/customers')->assertOk();
    }

    // ─── H1: single-role mode never elevates a viewer ────────────────────

    public function test_single_role_mode_still_excludes_viewers(): void
    {
        config(['system_features.admin.permissions_system' => false]);

        $viewer = User::factory()->create(); // role 'user'
        $this->actingAs($viewer)->get('/admin/customers')->assertRedirect(route('admin.dashboard'));

        // …but a moderator is allowed through in single-role mode.
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($moderator)->get('/admin/customers')->assertOk();
    }

    // ─── C5: guest-merge account takeover blocked ────────────────────────

    public function test_guest_cannot_claim_an_existing_customers_phone(): void
    {
        $victim = Customer::create([
            'full_name' => 'Victim',
            'phone' => '0599000111',
            'token' => 'victim-secret-token',
            'default_address' => 'Victim St',
        ]);
        Customer::create(['full_name' => 'guest', 'phone' => null, 'token' => 'guest-token']);

        $res = $this->withHeader('Authorization', 'Bearer guest-token')
            ->postJson('/api/v1/customer/update', [
                'name' => 'Attacker',
                'phone' => '0599000111',
            ]);

        $res->assertStatus(409);
        // The victim's record and token must be untouched and never disclosed.
        $victim->refresh();
        $this->assertSame('Victim', $victim->full_name);
        $this->assertSame('Victim St', $victim->default_address);
        $this->assertSame('victim-secret-token', $victim->token);
        $this->assertStringNotContainsString('victim-secret-token', $res->getContent());
    }

    public function test_anonymous_caller_cannot_claim_an_existing_phone(): void
    {
        Customer::create(['full_name' => 'Victim', 'phone' => '0599000222', 'token' => 'tok-2']);

        // No Authorization header → no resolved customer. Rejected before any token
        // is issued: the validation unique rule (422) for this no-session path, or
        // the ownership guard (409) for the guest path. Either way: no takeover,
        // no token disclosed.
        $res = $this->postJson('/api/v1/customer/update', ['name' => 'X', 'phone' => '0599000222']);

        $this->assertContains($res->getStatusCode(), [409, 422], 'Expected the claim to be rejected.');
        $this->assertStringNotContainsString('tok-2', $res->getContent());
    }

    public function test_guest_can_still_promote_with_a_fresh_phone(): void
    {
        Customer::create(['full_name' => 'guest', 'phone' => null, 'token' => 'guest-token-2']);

        $res = $this->withHeader('Authorization', 'Bearer guest-token-2')
            ->postJson('/api/v1/customer/update', [
                'name' => 'New User',
                'phone' => '0599888777',
            ]);

        $res->assertOk();
        // Same row promoted in place — keeps its own token, no cross-account transfer.
        $this->assertDatabaseHas('customers', [
            'token' => 'guest-token-2',
            'phone' => '0599888777',
            'full_name' => 'New User',
        ]);
    }
}
