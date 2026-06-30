<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureCustomerSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Regression tests for the Low-tier hardening:
 * L1 (SameSite coerced to lax/strict), L3 (random order-number suffix),
 * L5 (per-key config validation), L6 (stale session customer falls back to guest).
 */
class LowHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ─── L3: order numbers carry a random suffix; sequence still increments ──

    public function test_order_number_has_random_suffix_and_increments(): void
    {
        $n1 = Order::generateOrderNumber();
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-0001-[A-Z0-9]{4}$/', $n1);

        $customer = Customer::create(['full_name' => 'C', 'phone' => '0590000000']);
        Order::create([
            'order_number' => $n1,
            'customer_id' => $customer->id,
            'customer_name' => 'C',
            'phone' => '0590000000',
            'source' => 'spa',
            'order_type' => 'delivery',
            'status' => Order::STATUS_PENDING,
            'subtotal' => 10,
            'total' => 10,
        ]);

        $n2 = Order::generateOrderNumber();
        // Sequence advances despite the trailing random suffix.
        $this->assertStringContainsString('-0002-', $n2);
        $this->assertNotSame($n1, $n2);
    }

    // ─── L5: per-key config validation rejects bad values ────────────────────

    public function test_config_update_rejects_non_numeric_for_numeric_key(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemConfig::create(['key' => 'currency_decimals', 'value' => '2', 'group' => 'general']);

        $this->actingAs($admin)
            ->put('/admin/configs/update', ['config_currency_decimals' => 'abc'])
            ->assertSessionHas('error');

        // The bad value was rejected, not persisted.
        $this->assertSame('2', SystemConfig::where('key', 'currency_decimals')->value('value'));
    }

    public function test_config_update_accepts_valid_numeric(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemConfig::create(['key' => 'currency_decimals', 'value' => '2', 'group' => 'general']);

        $this->actingAs($admin)
            ->put('/admin/configs/update', ['config_currency_decimals' => '3'])
            ->assertSessionHas('success');

        $this->assertSame('3', SystemConfig::where('key', 'currency_decimals')->value('value'));
    }

    // ─── L1: SameSite can never be 'none'/null ──────────────────────────────

    public function test_session_same_site_is_lax_or_strict(): void
    {
        $this->assertContains(config('session.same_site'), ['lax', 'strict']);
    }

    // ─── L6: a stale session customer_id degrades to guest, not a 500 ────────

    public function test_stale_session_customer_falls_back_to_guest(): void
    {
        $session = $this->app['session']->driver();
        $session->put('customer_id', 999999); // no such customer

        $request = Request::create('/api/v1/cart', 'GET');
        $request->setLaravelSession($session);

        $called = false;
        $response = (new EnsureCustomerSession)->handle($request, function ($r) use (&$called) {
            $called = true;

            return new \Illuminate\Http\Response('ok');
        });

        $this->assertTrue($called, 'Request should pass through as a guest.');
        $this->assertSame('ok', $response->getContent());
        $this->assertNull($request->attributes->get('customer'));
        $this->assertFalse($session->has('customer_id'), 'Stale id should be forgotten.');
    }
}
