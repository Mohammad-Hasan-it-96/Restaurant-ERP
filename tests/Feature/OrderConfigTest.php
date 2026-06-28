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

        $this->assertStringStartsWith('INV-'.Carbon::now()->format('Ymd').'-', $number);
        $this->assertStringEndsWith('0001', $number);
    }

    public function test_order_number_defaults_to_ord_prefix(): void
    {
        $this->assertStringStartsWith('ORD-', Order::generateOrderNumber());
    }
}
