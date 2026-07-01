<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the Medium-tier hardening:
 * M1 (customer tokens stored hashed), M3 (customer name allowlist),
 * M4 (per_page cap), M5 (search length cap), M7 (frontend-log payload bounds).
 */
class MediumHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ─── M1: tokens are stored hashed, plaintext still resolves ──────────

    public function test_guest_token_is_stored_hashed_and_resolves(): void
    {
        $res = $this->postJson('/api/v1/customer/guest', [])->assertOk();
        $plain = $res->json('data.customer_token');

        $this->assertNotEmpty($plain);
        // DB holds the hash, never the plaintext.
        $this->assertDatabaseMissing('customers', ['token' => $plain]);
        $this->assertDatabaseHas('customers', ['token' => hash('sha256', $plain)]);

        // The plaintext Bearer token still resolves via the middleware.
        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/customer/me')
            ->assertOk()
            ->assertJsonPath('data.is_guest', true);
    }

    public function test_update_promotes_guest_and_echoes_same_token(): void
    {
        $plain = $this->postJson('/api/v1/customer/guest', [])->json('data.customer_token');

        $res = $this->withHeader('Authorization', 'Bearer '.$plain)
            ->postJson('/api/v1/customer/update', ['name' => 'Real User', 'phone' => '0599123123'])
            ->assertOk();

        // The same token is echoed back (client keeps it); it still resolves.
        $this->assertSame($plain, $res->json('data.token'));
        $this->assertDatabaseHas('customers', [
            'phone' => '0599123123',
            'token' => hash('sha256', $plain),
        ]);
        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/customer/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '0599123123');
    }

    // ─── M3: customer name allowlist (keeps formula chars out of exports) ─

    public function test_update_rejects_formula_characters_in_name(): void
    {
        $plain = $this->postJson('/api/v1/customer/guest', [])->json('data.customer_token');

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->postJson('/api/v1/customer/update', ['name' => "=cmd|'/C calc'", 'phone' => '0599000000'])
            ->assertStatus(422);

        // A normal name is accepted.
        Customer::query()->delete();
        $plain2 = $this->postJson('/api/v1/customer/guest', [])->json('data.customer_token');
        $this->withHeader('Authorization', 'Bearer '.$plain2)
            ->postJson('/api/v1/customer/update', ['name' => 'Ahmed Al-Sayed', 'phone' => '0599000001'])
            ->assertOk();
    }

    // ─── M4 / M5: per_page cap + oversized search handled ────────────────

    public function test_per_page_is_capped_at_max(): void
    {
        $this->getJson('/api/v1/products?per_page=99999')
            ->assertOk()
            ->assertJsonPath('data.per_page', (int) config('api.max_per_page'));
    }

    public function test_oversized_search_term_is_handled(): void
    {
        $this->getJson('/api/v1/products?search='.str_repeat('a', 200))
            ->assertOk();
    }

    // ─── M7: frontend log payload bounds ─────────────────────────────────

    public function test_frontend_log_rejects_too_many_data_entries(): void
    {
        $this->postJson('/api/v1/logs', [
            'type' => 'error',
            'message' => 'boom',
            'data' => array_fill(0, 60, 'x'),
        ])->assertStatus(422);
    }

    public function test_frontend_log_accepts_bounded_payload(): void
    {
        $this->postJson('/api/v1/logs', [
            'type' => 'error',
            'message' => 'boom',
            'data' => ['blob' => str_repeat('a', 9000)], // large but within entry-count limit
        ])->assertOk();
    }
}
