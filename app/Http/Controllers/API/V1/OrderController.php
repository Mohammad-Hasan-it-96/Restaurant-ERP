<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\SystemConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderService        $orderService,
        protected SystemConfigService $config
    ) {}

    /**
     * POST /api/v1/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return $this->success(
                new OrderResource($order->load('items')),
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
    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return $this->error(__('app.order_not_found'), 404);
        }

        $order->load('items');

        return $this->success(new OrderResource($order));
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
        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return $this->error(__('app.order_not_found'), 404);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return $this->error(__('app.order_cancel_not_allowed'), 422);
        }

        // Scheduled delivery: enforce cancellation window
        if (
            $order->order_type   === Order::TYPE_DELIVERY
            && $order->delivery_type === 'scheduled'
            && $order->scheduled_at  !== null
        ) {
            $cancelBeforeMinutes = (int) $this->config->getNumber('customer_cancel_before_minutes', 0);
            $deadline            = Carbon::parse($order->scheduled_at)->subMinutes($cancelBeforeMinutes);

            if (now()->greaterThanOrEqualTo($deadline)) {
                return $this->error(__('app.order_cancel_window_passed'), 422);
            }
        }

        $order->update([
            'status'       => 'cancelled_by_customer',
            'cancelled_at' => now(),
        ]);

        return $this->success(null, __('app.order_cancelled_by_customer'));
    }
}
