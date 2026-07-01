<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the phone-as-identity hardening on the unauthenticated
 * order route (OrderService::createOrder):
 *   • a stale session identity never wins over the phone on the order, so a
 *     shared browser/kiosk can't attach customer B's order to customer A;
 *   • an anonymous order under an existing customer's phone never overwrites
 *     their profile nor re-exposes a token;
 *   • a genuinely new customer is still issued (and returned) a token once.
 */
class OrderIdentityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        return Product::create([
            'name' => 'Test Product',
            'name_ar' => 'منتج',
            'name_en' => 'Product',
            'details' => 'x',
            'price' => 10.00,
            'is_active' => true,
            'is_available' => true,
            'user_id' => User::factory()->create()->id,
        ]);
    }

    private function orderPayload(string $phone, string $name, Product $product): array
    {
        return [
            'customer_name' => $name,
            'customer_phone' => $phone,
            'order_type' => 'takeaway',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];
    }

    // ─── Shared-device: session identity must not override the order's phone ──

    public function test_stale_session_customer_does_not_capture_another_phones_order(): void
    {
        $product = $this->makeProduct();

        // Customer A ordered earlier on this browser and their id lingers in the session.
        $alice = Customer::create(['full_name' => 'Alice', 'phone' => '0590000001']);
        $alice->issueToken();
        $alice->save();

        session()->put('customer_id', $alice->id);

        // Customer B now orders with B's OWN phone on the same browser.
        $order = app(OrderService::class)->createOrder(
            $this->orderPayload('0590000002', 'Bob', $product)
        );

        // The order must belong to B, not the session's lingering A.
        $this->assertNotSame($alice->id, $order->customer_id);
        $this->assertSame('0590000002', $order->customer->phone);

        // A's record is untouched and their token never re-issued.
        $alice->refresh();
        $this->assertSame('Alice', $alice->full_name);
        $this->assertSame('0590000001', $alice->phone);
    }

    // ─── Existing customer: anonymous order can't overwrite profile or leak token ──

    public function test_anonymous_order_does_not_overwrite_existing_customer_name_or_leak_token(): void
    {
        $product = $this->makeProduct();

        $victim = Customer::create(['full_name' => 'Original', 'phone' => '0590000003']);
        $victim->issueToken();
        $victim->save();
        $victimTokenHash = $victim->token;

        $res = $this->postJson(
            '/api/v1/orders',
            $this->orderPayload('0590000003', 'Hacker', $product)
        );

        $res->assertStatus(201);

        // The pre-existing profile name is preserved…
        $victim->refresh();
        $this->assertSame('Original', $victim->full_name);
        // …their token is unchanged…
        $this->assertSame($victimTokenHash, $victim->token);
        // …and no token is returned to the anonymous caller.
        $this->assertNull($res->json('data.customer_token'));
    }

    // ─── New customer: token is still issued and returned exactly once ───────

    public function test_brand_new_customer_is_issued_and_returned_a_token(): void
    {
        $product = $this->makeProduct();

        $res = $this->postJson(
            '/api/v1/orders',
            $this->orderPayload('0590000004', 'Fresh Customer', $product)
        );

        $res->assertStatus(201);

        $token = $res->json('data.customer_token');
        $this->assertNotEmpty($token);

        $customer = Customer::where('phone', '0590000004')->first();
        $this->assertNotNull($customer);
        $this->assertSame('Fresh Customer', $customer->full_name);
        // The returned plaintext matches the stored hash (token stored hashed).
        $this->assertSame(Customer::hashToken($token), $customer->token);
    }
}
