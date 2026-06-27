<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModifyOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\SystemConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderService $orderService,
        protected SystemConfigService $config
    ) {}

    /**
     * POST /api/v1/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            $orderData = new OrderResource($order->load('items'));

            return $this->success(
                array_merge($orderData->resolve(), [
                    'customer_token' => $order->customer->token,
                ]),
                __('app.order_placed_successfully'),
                201
            );

        } catch (ValidationException $e) {
            return $this->error(
                collect($e->errors())->flatten()->first(),
                422,
                $e->errors()
            );

        } catch (\Throwable $e) {
            report($e);

            return $this->error(__('app.order_failed'), 500);
        }
    }

    /**
     * GET /api/v1/orders/{order_number}
     */
    public function show($orderNumber, Request $request)
    {
        $customer = $request->attributes->get('customer');

        if (! $customer) {
            return $this->error('Unauthorized', 403);
        }

        $order = Order::query()->where('order_number', $orderNumber)->first();

        if (! $order) {
            return $this->error(__('app.order_not_found'), 404);
        }

        // Return 404 (not 403) to avoid leaking order existence
        if ($order->customer_id !== $customer->id) {
            return $this->error(__('app.order_not_found'), 404);
        }

        return $this->success(new OrderResource($order->load('items')));
    }

    /**
     * POST /api/v1/orders/{order_number}/cancel
     *
     * Rules:
     *  - Only allowed if status = pending
     *  - Scheduled delivery: allowed only if scheduled_at > now + customer_cancel_before_minutes
     *  - Immediate / table / takeaway: allowed while still pending (before acceptance)
     */
    public function cancel(string $orderNumber): JsonResponse
    {
        $customer = request()->attributes->get('customer');
        $order = Order::query()->where('order_number', $orderNumber)->first();

        if (! $order) {
            return $this->error(__('app.order_not_found'), 404);
        }

        // Return 404 (not 403) to avoid leaking order existence
        if (! $customer || $order->customer_id !== $customer->id) {
            return $this->error(__('app.order_not_found'), 404);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return $this->error(__('app.order_cancel_not_allowed'), 422);
        }

        // Scheduled delivery: enforce cancellation window
        if (
            $order->order_type === Order::TYPE_DELIVERY
            && $order->delivery_type === 'scheduled'
            && $order->scheduled_at !== null
        ) {
            $cancelBeforeMinutes = (int) $this->config->getNumber('customer_cancel_before_minutes', 0);
            $deadline = Carbon::parse($order->scheduled_at)->subMinutes($cancelBeforeMinutes);

            if (now()->greaterThanOrEqualTo($deadline)) {
                return $this->error(__('app.order_cancel_window_passed'), 422);
            }
        }

        $order->update([
            'status' => 'cancelled_by_customer',
            'cancelled_at' => now(),
        ]);

        return $this->success(null, __('app.order_cancelled_by_customer'));
    }

    /**
     * POST /api/v1/orders/{order_number}/modify
     *
     * Creates a new replacement order and marks the original as "modified".
     * Allowed only while the original is still pending and within the
     * customer_cancel_before_minutes window.
     */
    public function modify(ModifyOrderRequest $request, string $orderNumber): JsonResponse
    {
        $customer = $request->attributes->get('customer');

        if (! $customer) {
            return $this->error('Unauthorized', 403);
        }

        $oldOrder = Order::query()->where('order_number', $orderNumber)->first();

        if (! $oldOrder) {
            return $this->error(__('app.order_not_found'), 404);
        }

        if ($oldOrder->customer_id !== $customer->id) {
            return $this->error(__('app.order_not_found'), 404);
        }

        // Fill name/phone from original order when not sent by the frontend
        $data = $request->validated();
        if (empty($data['customer_name'])) {
            $data['customer_name'] = $oldOrder->customer_name;
        }
        if (empty($data['customer_phone'])) {
            $data['customer_phone'] = $oldOrder->phone;
        }

        try {
            $newOrder = $this->orderService->modifyOrder($oldOrder, $data, $customer);

            $orderData = new OrderResource($newOrder->load('items', 'customer'));

            return $this->success(
                array_merge($orderData->resolve(), [
                    'old_order_number' => $oldOrder->order_number,
                    'customer_token' => $newOrder->customer->token ?? $customer->token,
                ]),
                __('app.order_modified_successfully'),
                201
            );

        } catch (ValidationException $e) {
            return $this->error(
                collect($e->errors())->flatten()->first(),
                422,
                $e->errors()
            );

        } catch (\Throwable $e) {
            report($e);

            return $this->error(__('app.order_failed'), 500);
        }
    }
}
