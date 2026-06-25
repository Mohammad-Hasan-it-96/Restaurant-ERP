<?php

namespace Tests\Feature;

use App\Http\Resources\V1\ProductResource;
use App\Models\Customer;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Order;
use App\Models\Product;
use App\Models\Weight;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeatureEnforcementTest extends TestCase
{
    use RefreshDatabase;

    // ── ProductResource: weights ─────────────────────────────────────────────

    private function weightBasedProduct(): Product
    {
        $product = new Product([
            'name_ar' => 'منتج', 'name_en' => 'Product',
            'is_weight_based' => true, 'price' => 5, 'price_per_kg' => 10,
        ]);
        $product->setRelation('weights', collect([
            new Weight(['name' => '1kg', 'value_kg' => 1]),
        ]));

        return $product;
    }

    public function test_weights_are_exposed_when_feature_enabled(): void
    {
        config(['system_features.products.weight_products' => true]);

        $data = (new ProductResource($this->weightBasedProduct()))->toArray(Request::create('/'));

        $this->assertTrue($data['is_weight_based']);
        $this->assertSame(10.0, $data['price_per_kg']);
        $this->assertArrayHasKey('weights', $data);
    }

    public function test_weights_are_stripped_when_feature_disabled(): void
    {
        config(['system_features.products.weight_products' => false]);

        $data = (new ProductResource($this->weightBasedProduct()))->toArray(Request::create('/'));

        // Treated as a normal, fixed-price product — no weight fields leak.
        $this->assertFalse($data['is_weight_based']);
        $this->assertSame(5.0, $data['price']);
        $this->assertArrayNotHasKey('price_per_kg', $data);
        $this->assertArrayNotHasKey('weights', $data);
    }

    // ── ProductResource: options ─────────────────────────────────────────────

    private function productWithOptions(): Product
    {
        $option = new Option(['name' => 'Cut']);
        $value = new OptionValue(['name' => 'Thin']);
        $value->setRelation('option', $option);

        $product = new Product(['name_ar' => 'منتج', 'name_en' => 'Product', 'price' => 5]);
        $product->setRelation('optionValues', collect([$value]));

        return $product;
    }

    public function test_options_are_stripped_when_feature_disabled(): void
    {
        config(['system_features.products.options' => true]);
        $enabled = (new ProductResource($this->productWithOptions()))->toArray(Request::create('/'));
        $this->assertArrayHasKey('options', $enabled);

        config(['system_features.products.options' => false]);
        $disabled = (new ProductResource($this->productWithOptions()))->toArray(Request::create('/'));
        $this->assertArrayNotHasKey('options', $disabled);
    }

    // ── Notifications ────────────────────────────────────────────────────────

    public function test_push_not_dispatched_when_feature_disabled(): void
    {
        Queue::fake();
        config(['system_features.notifications.push' => false]);

        $customer = Customer::create([
            'full_name' => 'T', 'phone' => '0100', 'token' => (string) Str::uuid(), 'fcm_token' => 'tok',
        ]);
        $order = new Order(['status' => Order::STATUS_ACCEPTED, 'order_number' => 'ORD-1']);
        $order->setRelation('customer', $customer);

        app(NotificationService::class)->notifyOrderStatus($order);

        Queue::assertNothingPushed();
    }
}
