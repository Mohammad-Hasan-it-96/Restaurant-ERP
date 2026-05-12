<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\SystemConfigService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected SystemConfigService $config
    ) {}

    /**
     * Create a new order inside a DB transaction.
     *
     * @param  array  $data  Validated data from StoreOrderRequest
     * @return Order
     *
     * @throws ValidationException|\Throwable
     */
    public function createOrder(array $data): Order
    {
        Log::info('order.create.start', [
            'customer_phone' => $data['customer_phone'],
            'order_type'     => $data['order_type'],
            'items_count'    => count($data['items']),
        ]);

        try {
            $order = DB::transaction(function () use ($data) {

            // ── 1. Resolve customer ──────────────────────────────────
            $customer = Customer::firstOrCreate(
                ['phone' => $data['customer_phone']],
                ['full_name' => $data['customer_name']]
            );

            // Update name if it changed
            if ($customer->full_name !== $data['customer_name']) {
                $customer->update(['full_name' => $data['customer_name']]);
            }

            if ($customer->is_blocked) {
                throw ValidationException::withMessages([
                    'phone' => ['هذا الرقم محظور من الطلب'],
                ]);
            }

            // ── 2. Resolve & validate products ───────────────────────
            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products   = Product::whereIn('id', $productIds)
                            ->where('is_active', true)
                            ->where('is_available', true)
                            ->get()
                            ->keyBy('id');

            $missingIds = array_diff($productIds, $products->keys()->toArray());
            if (count($missingIds)) {
                throw ValidationException::withMessages([
                    'items' => [__('app.some_products_not_found')],
                ]);
            }
            // Note: quantity (stock) is intentionally ignored — no stock checks.

            // ── 3. Check opening hours ───────────────────────────────
            $checkAt = null;

            if (
                ($data['order_type'] === Order::TYPE_DELIVERY)
                && (($data['delivery_type'] ?? null) === 'scheduled')
                && ! empty($data['scheduled_at'])
            ) {
                $checkAt = Carbon::parse($data['scheduled_at']);
            }
            // else: immediate / table / takeaway → check now()

            if (! $this->config->isOpenAt($checkAt)) {
                $closedMsg = $this->config->get('order_closed_message')
                    ?: __('app.restaurant_closed_message');

                throw ValidationException::withMessages([
                    'order_type' => [$closedMsg],
                ]);
            }

            // ── 4. Build order items & calculate totals ──────────────
            $itemsData = [];
            $subtotal  = 0;

            foreach ($data['items'] as $itemInput) {
                /** @var Product $product */
                $product = $products[$itemInput['product_id']];
                $price   = (float) $product->effective_price;
                $qty     = (int) $itemInput['quantity'];
                $total   = round($price * $qty, 2);

                $itemsData[] = [
                    'product_id'    => $product->id,
                    'product_name'  => $product->display_name,
                    'product_price' => $price,
                    'quantity'      => $qty,
                    'total'         => $total,
                ];

                $subtotal += $total;
            }

            $subtotal = round($subtotal, 2);

            // ── 5. Generate unique order number ──────────────────────
            $orderNumber = Order::generateOrderNumber();

            // ── 6. Persist order ─────────────────────────────────────
            $order = Order::create([
                'order_number'          => $orderNumber,
                'customer_id'           => $customer->id,
                'source'                => 'website',
                'order_type'            => $data['order_type'],
                'table_number'          => $data['table_number']   ?? null,
                'address'               => $data['address']        ?? null,
                'delivery_type'         => $data['delivery_type']  ?? null,
                'scheduled_at'          => isset($data['scheduled_at'])
                                            ? Carbon::parse($data['scheduled_at'])
                                            : null,
                'customer_note'         => $data['customer_note']  ?? null,
                'status'                => Order::STATUS_PENDING,
                'payment_status'        => Order::PAYMENT_UNPAID,
                'subtotal'              => $subtotal,
                'estimated_delivery_fee'=> $data['estimated_delivery_fee'] ?? null,
                'delivery_fee'          => null,
                'discount'              => 0,
                'total'                 => $subtotal,
            ]);

            // ── 7. Persist order items ───────────────────────────────
            foreach ($itemsData as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            return $order->fresh('items', 'customer');
            });

            Log::info('order.create.success', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'total'        => $order->total,
            ]);

            return $order;

        } catch (\Throwable $e) {
            Log::error('order.create.failed', [
                'error'   => $e->getMessage(),
                'payload' => $data,
            ]);

            throw $e;
        }
    }
}

