<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_number_uses_configured_prefix(): void
    {
        config(['orders.number_prefix' => 'INV-']);

        $number = Order::generateOrderNumber();

        // Format: <prefix><Ymd>-<0001 sequence>-<random suffix>.
        $this->assertMatchesRegularExpression(
            '/^INV-'.Carbon::now()->format('Ymd').'-0001-[A-Z0-9]{4}$/',
            $number
        );
    }

    public function test_order_number_defaults_to_ord_prefix(): void
    {
        $this->assertStringStartsWith('ORD-', Order::generateOrderNumber());
    }
}
