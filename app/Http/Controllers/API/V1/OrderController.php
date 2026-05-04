<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * POST /api/v1/orders
     *
     * Place a new order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return response()->json([
                'success'      => true,
                'message'      => __('app.order_placed_successfully'),
                'order_number' => $order->order_number,
                'status'       => $order->status,
                'subtotal'     => (float) $order->subtotal,
                'total'        => (float) $order->total,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('app.order_failed'),
            ], 500);
        }
    }
}

