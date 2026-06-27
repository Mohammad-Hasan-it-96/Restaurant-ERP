<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Weight;
use App\Support\Feature;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     *
     * @throws ValidationException|\Throwable
     */
    public function createOrder(array $data): Order
    {
        logService()->info('order.create.start', [
            'customer_phone' => $data['customer_phone'],
            'order_type' => $data['order_type'],
            'items_count' => count($data['items']),
        ]);

        // ── Order-type feature gate (bypass-proof) ───────────────────────────
        // Reject an order whose type's channel feature is disabled, even if the
        // request bypassed StoreOrderRequest's dynamic rule.
        if (! $this->orderTypeEnabled($data['order_type'])) {
            throw ValidationException::withMessages([
                'order_type' => [__('app.order_type_unavailable')],
            ]);
        }

        // ── Duplicate order guard (5-second window) ──────────────────────────
        $dupKey = 'order_dup_'.md5(
            $data['customer_phone']
            .'|'.$data['order_type']
            .'|'.implode(',', array_map(
                fn ($i) => $i['product_id'].'x'.$i['quantity'],
                $data['items']
            ))
        );

        if (Cache::has($dupKey)) {
            logService()->warning('order.create.duplicate', [
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

                // Block check first — a blocked customer throws (rolling back the
                // transaction), so there is no point writing any changes for them.
                if ($customer->is_blocked) {
                    throw ValidationException::withMessages([
                        'phone' => [__('app.order_unavailable_message')],
                    ]);
                }

                // Bind the resolved customer to the session so subsequent requests
                // (e.g. cart ↔ order correlation) don't need to look up by phone again.
                session()->put('customer_id', $customer->id);

                // Collect every customer change and persist them in ONE update,
                // instead of up to three separate UPDATE queries.
                $customerChanges = [];

                // Update name if it changed
                if ($customer->full_name !== $data['customer_name']) {
                    $customerChanges['full_name'] = $data['customer_name'];
                }

                // Generate a persistent token if the customer doesn't have one yet
                if (! $customer->token) {
                    $customerChanges['token'] = (string) Str::uuid();
                }

                // Store the FCM token sent by the SPA (guests pass it here on first order)
                if (! empty($data['fcm_token']) && $customer->fcm_token !== $data['fcm_token']) {
                    $customerChanges['fcm_token'] = $data['fcm_token'];
                }

                if ($customerChanges) {
                    $customer->fill($customerChanges)->save();

                    if (isset($customerChanges['token'])) {
                        logService()->info('customer.token.generated', ['customer_id' => $customer->id]);
                    }
                }

                // ── 2. Resolve & validate products ───────────────────────
                $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
                $products = Product::query()->whereIn('id', $productIds)
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
                // All config reads below go through SystemConfigService, which
                // loads the whole config map once and memoises it for the rest of
                // the request — so isOpenAt()/get() here cost no extra DB queries.
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
                [$itemsData, $subtotal] = $this->buildItemsData($data['items'], $products);

                // ── 5. Generate unique order number ──────────────────────
                $orderNumber = Order::generateOrderNumber();

                // ── 6. Persist order ─────────────────────────────────────
                $order = Order::create([
                    'order_number' => $orderNumber,
                    'customer_id' => $customer->id,
                    'customer_name' => $data['customer_name'] ?? $customer->full_name,
                    'phone' => $data['customer_phone'] ?? $customer->phone,
                    'source' => 'website',
                    'order_type' => $data['order_type'],
                    'table_number' => $data['table_number'] ?? null,
                    'address' => $data['address'] ?? null,
                    'delivery_type' => $data['delivery_type'] ?? null,
                    'scheduled_at' => isset($data['scheduled_at'])
                                                ? Carbon::parse($data['scheduled_at'])
                                                : null,
                    'customer_note' => $data['customer_note'] ?? null,
                    'status' => Order::STATUS_PENDING,
                    'payment_status' => Order::PAYMENT_UNPAID,
                    'subtotal' => $subtotal,
                    'estimated_delivery_fee' => $data['estimated_delivery_fee'] ?? null,
                    'delivery_fee' => null,
                    'discount' => 0,
                    'total' => $subtotal,
                ]);

                // ── 7. Persist order items (single bulk insert) ──────────
                $this->persistItems($order, $itemsData);

                return $order->fresh('items', 'customer');
            });

            // ── Persist customer binding in session (outside transaction) ──
            session()->put('customer_id', $order->customer_id);

            logService()->info('order.create.success', [
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total,
            ]);

            return $order;

        } catch (\Throwable $e) {
            // Release the duplicate-guard lock on real errors so the user can retry
            Cache::forget($dupKey);

            // Log only non-sensitive shape of the payload (no PII / full body);
            // the exception summary is attached by LogService.
            logService()->error('order.create.failed', [
                'order_type' => $data['order_type'] ?? null,
                'items_count' => isset($data['items']) ? count($data['items']) : 0,
            ], $e);

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
     * @param  Order  $oldOrder  The order to be superseded.
     * @param  array  $data  Validated payload (same shape as createOrder).
     * @param  Customer  $customer  Token-resolved customer.
     * @return Order The newly created replacement order.
     *
     * @throws ValidationException|\Throwable
     */
    public function modifyOrder(Order $oldOrder, array $data, Customer $customer): Order
    {
        // ── 0. Feature gate ───────────────────────────────────────────────────
        // Defence-in-depth: the API route is already gated by the
        // `feature:orders.modification` middleware, but guard internal callers too.
        if (Feature::disabled('orders.modification')) {
            throw ValidationException::withMessages([
                'order' => [__('app.order_modify_not_allowed')],
            ]);
        }

        // Reject a switch to a disabled order-type channel.
        if (isset($data['order_type']) && ! $this->orderTypeEnabled($data['order_type'])) {
            throw ValidationException::withMessages([
                'order_type' => [__('app.order_type_unavailable')],
            ]);
        }

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
                $oldOrder->order_type === Order::TYPE_DELIVERY
                && $oldOrder->delivery_type === 'scheduled'
                && $oldOrder->scheduled_at !== null
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

        logService()->info('order.modify.start', [
            'old_order_id' => $oldOrder->id,
            'old_order_number' => $oldOrder->order_number,
            'customer_id' => $customer->id,
        ]);

        $newOrder = DB::transaction(function () use ($oldOrder, $data, $customer) {

            // ── 4. Resolve & validate products ───────────────────────────────
            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products = Product::query()->whereIn('id', $productIds)
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
            [$itemsData, $subtotal] = $this->buildItemsData($data['items'], $products);

            // ── 6. Mark old order as modified ─────────────────────────────────
            $oldOrder->update(['status' => Order::STATUS_MODIFIED]);

            // ── 7. Create new (replacement) order ─────────────────────────────
            $orderNumber = Order::generateOrderNumber();

            $newOrder = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'modified_from_order_id' => $oldOrder->id,
                'customer_name' => $data['customer_name'] ?? $oldOrder->customer_name,
                'phone' => $data['customer_phone'] ?? $oldOrder->phone,
                'source' => 'website',
                'order_type' => $data['order_type'],
                'table_number' => $data['table_number'] ?? null,
                'address' => $data['address'] ?? null,
                'delivery_type' => $data['delivery_type'] ?? null,
                'scheduled_at' => isset($data['scheduled_at'])
                                              ? Carbon::parse($data['scheduled_at'])
                                              : null,
                'customer_note' => $data['customer_note'] ?? null,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_UNPAID,
                'subtotal' => $subtotal,
                'estimated_delivery_fee' => $data['estimated_delivery_fee'] ?? null,
                'delivery_fee' => null,
                'discount' => 0,
                'total' => $subtotal,
            ]);

            // ── 8. Persist items (single bulk insert) ─────────────────────────
            $this->persistItems($newOrder, $itemsData);

            return $newOrder->fresh('items', 'customer');
        });

        logService()->info('order.modify.success', [
            'customer_id' => $customer->id,
            'old_order_id' => $oldOrder->id,
            'new_order_id' => $newOrder->id,
            'new_order_num' => $newOrder->order_number,
        ]);

        return $newOrder;
    }

    /**
     * Build the $itemsData array and compute the subtotal from raw item inputs.
     *
     * For weight-based products the price is price_per_kg × weight.value_kg.
     * For normal products the price is effective_price (discount applied).
     *
     * @param  array  $itemInputs  Each element has product_id, quantity, weight_id?
     * @param  \Illuminate\Support\Collection  $products  Keyed by id
     * @return array{0: array, 1: float}
     */
    /**
     * Whether the given order type's channel feature is enabled.
     * Maps Order::TYPE_* to the matching core.* feature flag. An unknown
     * type is treated as disabled.
     */
    private function orderTypeEnabled(string $orderType): bool
    {
        $flag = match ($orderType) {
            Order::TYPE_DELIVERY => 'core.delivery',
            Order::TYPE_TAKEAWAY => 'core.takeaway',
            Order::TYPE_TABLE => 'core.table_ordering',
            default => null,
        };

        return $flag !== null && Feature::enabled($flag);
    }

    private function buildItemsData(array $itemInputs, $products): array
    {
        // Feature enforcement (bypass-proof backend layer): if a feature is off
        // we ignore any weight/option input a forged request might carry and
        // price the product normally — even if the frontend or API somehow sent it.
        $weightEnabled = Feature::enabled('products.weight_products');
        $optionsEnabled = Feature::enabled('products.options');

        // Pre-load all weights referenced across the item list in one query
        $weightIds = $weightEnabled
            ? collect($itemInputs)->pluck('weight_id')->filter()->unique()->toArray()
            : [];
        $weights = $weightIds
            ? Weight::whereIn('id', $weightIds)->get()->keyBy('id')
            : collect();

        $itemsData = [];
        $subtotal = 0;

        foreach ($itemInputs as $itemInput) {
            /** @var Product $product */
            $product = $products[$itemInput['product_id']];
            $qty = (int) $itemInput['quantity'];

            $weightName = null;
            $weightValueKg = null;

            if ($weightEnabled && $product->is_weight_based) {
                $weightId = $itemInput['weight_id'] ?? null;
                $weight = $weightId ? $weights->get($weightId) : null;

                if (! $weight) {
                    throw ValidationException::withMessages([
                        'items' => [__('app.weight_required_for_product')],
                    ]);
                }

                $price = round((float) $product->price_per_kg * (float) $weight->value_kg, 2);
                $weightName = $weight->name;
                $weightValueKg = (float) $weight->value_kg;
            } else {
                $price = (float) $product->effective_price;
            }

            $total = round($price * $qty, 2);

            $itemsData[] = [
                'product_id' => $product->id,
                'product_name' => $product->display_name,
                'product_price' => $price,
                'quantity' => $qty,
                'total' => $total,
                'weight_name' => $weightName,
                'weight_value_kg' => $weightValueKg,
                'option_name' => $optionsEnabled ? ($itemInput['option_name'] ?? null) : null,
            ];

            $subtotal += $total;
        }

        return [$itemsData, round($subtotal, 2)];
    }

    /**
     * Persist all order items in a single bulk INSERT instead of one query per
     * item. OrderItem has no model events or mutators (only read-side decimal
     * casts), so insert() is safe here; we just supply order_id + timestamps,
     * which Eloquent would otherwise add per row.
     *
     * @param  array  $itemsData  Rows from buildItemsData() (without order_id)
     */
    private function persistItems(Order $order, array $itemsData): void
    {
        if (! $itemsData) {
            return;
        }

        $now = now();

        $rows = array_map(
            fn (array $item) => $item + [
                'order_id' => $order->id,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $itemsData
        );

        OrderItem::insert($rows);
    }
}
