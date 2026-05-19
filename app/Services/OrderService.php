<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\SystemConfigService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        // ── Duplicate order guard (5-second window) ──────────────────────────
        $dupKey = 'order_dup_' . md5(
            $data['customer_phone']
            . '|' . $data['order_type']
            . '|' . implode(',', array_map(
                fn($i) => $i['product_id'] . 'x' . $i['quantity'],
                $data['items']
            ))
        );

        if (Cache::has($dupKey)) {
            Log::warning('order.create.duplicate', [
                'customer_phone' => $data['customer_phone'],
            ]);
            throw ValidationException::withMessages([
                'items' => [__('app.duplicate_order_message')],
            ]);
        }

        // Mark this combination as "in-flight" for 5 seconds
        Cache::put($dupKey, true, now()->addSeconds(5));

        try {
            $order = DB::transaction(function () use ($data) {

            // ── 1. Resolve customer ──────────────────────────────────
            // If a previous order already bound a customer to this session, reuse it.
            $sessionCustomerId = session()->get('customer_id');

            if ($sessionCustomerId) {
                $customer = Customer::findOrFail($sessionCustomerId);
            }

            // Fall back to phone lookup / creation (covers first-time & session-less clients)
            if (empty($customer)) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $data['customer_phone']],
                    ['full_name' => $data['customer_name']]
                );
            }

            // Update name if it changed
            if ($customer->full_name !== $data['customer_name']) {
                $customer->update(['full_name' => $data['customer_name']]);
            }

            if ($customer->is_blocked) {
                throw ValidationException::withMessages([
                    'phone' => [__('app.order_unavailable_message')],
                ]);
            }

            // Bind the resolved customer to the session so subsequent requests
            // (e.g. cart ↔ order correlation) don't need to look up by phone again.
            session()->put('customer_id', $customer->id);

            // Generate a persistent token if the customer doesn't have one yet
            if (! $customer->token) {
                $customer->update(['token' => (string) Str::uuid()]);
                Log::debug('customer.token.generated', ['customer_id' => $customer->id]);
            }

            // ── 2. Resolve & validate products ───────────────────────
            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products   = Product::query()->whereIn('id', $productIds)
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
                'customer_name'         => $data['customer_name']  ?? $customer->full_name,
                'phone'                 => $data['customer_phone'] ?? $customer->phone,
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

            // ── Persist customer binding in session (outside transaction) ──
            session()->put('customer_id', $order->customer_id);

            Log::info('order.create.success', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'total'        => $order->total,
            ]);

            return $order;

        } catch (\Throwable $e) {
            // Release the duplicate-guard lock on real errors so the user can retry
            Cache::forget($dupKey);

            Log::error('order.create.failed', [
                'error'   => $e->getMessage(),
                'payload' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Modify an existing pending order.
     *
     * Rules:
     *  - Order must belong to $customer.
     *  - Order status must be "pending".
     *  - Modification must happen within the customer_cancel_before_minutes window.
     *
     * On success:
     *  - Old order status → "modified".
     *  - New order created (same base data + modifications) with modified_from_order_id.
     *
     * @param  Order    $oldOrder  The order to be superseded.
     * @param  array    $data      Validated payload (same shape as createOrder).
     * @param  Customer $customer  Token-resolved customer.
     * @return Order               The newly created replacement order.
     *
     * @throws ValidationException|\Throwable
     */
    public function modifyOrder(Order $oldOrder, array $data, Customer $customer): Order
    {
        // ── 1. Ownership check ────────────────────────────────────────────────
        if ($oldOrder->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'order' => [__('app.order_not_found')],
            ]);
        }

        // ── 2. Status check ───────────────────────────────────────────────────
        if ($oldOrder->status !== Order::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'order' => [__('app.order_modify_not_allowed')],
            ]);
        }

        // ── 3. Time-window check ──────────────────────────────────────────────
        $cancelBeforeMinutes = (int) $this->config->getNumber('customer_cancel_before_minutes', 0);

        if ($cancelBeforeMinutes > 0) {
            if (
                $oldOrder->order_type   === Order::TYPE_DELIVERY
                && $oldOrder->delivery_type === 'scheduled'
                && $oldOrder->scheduled_at  !== null
            ) {
                // Scheduled: must be before (scheduled_at - window)
                $deadline = Carbon::parse($oldOrder->scheduled_at)->subMinutes($cancelBeforeMinutes);
                if (now()->greaterThanOrEqualTo($deadline)) {
                    throw ValidationException::withMessages([
                        'order' => [__('app.order_modify_window_passed')],
                    ]);
                }
            } else {
                // Immediate / table / takeaway: must be within X minutes of placing
                $deadline = Carbon::parse($oldOrder->created_at)->addMinutes($cancelBeforeMinutes);
                if (now()->greaterThan($deadline)) {
                    throw ValidationException::withMessages([
                        'order' => [__('app.order_modify_window_passed')],
                    ]);
                }
            }
        }

        Log::info('order.modify.start', [
            'old_order_id'     => $oldOrder->id,
            'old_order_number' => $oldOrder->order_number,
            'customer_id'      => $customer->id,
        ]);

        $newOrder = DB::transaction(function () use ($oldOrder, $data, $customer) {

            // ── 4. Resolve & validate products ───────────────────────────────
            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products   = Product::query()->whereIn('id', $productIds)
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

            // ── 5. Build items & totals ───────────────────────────────────────
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

            // ── 6. Mark old order as modified ─────────────────────────────────
            $oldOrder->update(['status' => Order::STATUS_MODIFIED]);

            // ── 7. Create new (replacement) order ─────────────────────────────
            $orderNumber = Order::generateOrderNumber();

            $newOrder = Order::create([
                'order_number'           => $orderNumber,
                'customer_id'            => $customer->id,
                'modified_from_order_id' => $oldOrder->id,
                'customer_name'          => $data['customer_name']  ?? $oldOrder->customer_name,
                'phone'                  => $data['customer_phone'] ?? $oldOrder->phone,
                'source'                 => 'website',
                'order_type'             => $data['order_type'],
                'table_number'           => $data['table_number']   ?? null,
                'address'                => $data['address']        ?? null,
                'delivery_type'          => $data['delivery_type']  ?? null,
                'scheduled_at'           => isset($data['scheduled_at'])
                                              ? Carbon::parse($data['scheduled_at'])
                                              : null,
                'customer_note'          => $data['customer_note']  ?? null,
                'status'                 => Order::STATUS_PENDING,
                'payment_status'         => Order::PAYMENT_UNPAID,
                'subtotal'               => $subtotal,
                'estimated_delivery_fee' => $data['estimated_delivery_fee'] ?? null,
                'delivery_fee'           => null,
                'discount'               => 0,
                'total'                  => $subtotal,
            ]);

            // ── 8. Persist items ──────────────────────────────────────────────
            foreach ($itemsData as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $newOrder->id]));
            }

            return $newOrder->fresh('items', 'customer');
        });

        Log::info('order.modify.success', [
            'old_order_id'  => $oldOrder->id,
            'new_order_id'  => $newOrder->id,
            'new_order_num' => $newOrder->order_number,
        ]);

        return $newOrder;
    }
}

