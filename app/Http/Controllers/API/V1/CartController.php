<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(protected CartService $cartService) {}

    /** GET /api/v1/cart */
    public function index(): JsonResponse
    {
        return $this->success($this->cartService->getCart());
    }

    /** POST /api/v1/cart/add — Body: { product_id, quantity? } */
    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity'   => ['sometimes', 'integer', 'min:1'],
        ]);

        try {
            $cart = $this->cartService->addItem(
                (int) $data['product_id'],
                (int) ($data['quantity'] ?? 1)
            );
            return $this->success($cart, __('app.cart_item_added'));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            report($e);
            return $this->error(__('app.server_error'), 500);
        }
    }

    /** POST /api/v1/cart/update — Body: { product_id, quantity } (quantity=0 removes) */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity'   => ['required', 'integer', 'min:0'],
        ]);

        try {
            $cart = $this->cartService->updateItem(
                (int) $data['product_id'],
                (int) $data['quantity']
            );
            return $this->success($cart, __('app.cart_item_updated'));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            report($e);
            return $this->error(__('app.server_error'), 500);
        }
    }

    /** POST /api/v1/cart/remove — Body: { product_id } */
    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $cart = $this->cartService->removeItem((int) $data['product_id']);
            return $this->success($cart, __('app.cart_item_removed'));
        } catch (\Throwable $e) {
            report($e);
            return $this->error(__('app.server_error'), 500);
        }
    }

    /** POST /api/v1/cart/clear */
    public function clear(): JsonResponse
    {
        try {
            $cart = $this->cartService->clearCart();
            return $this->success($cart, __('app.cart_cleared'));
        } catch (\Throwable $e) {
            report($e);
            return $this->error(__('app.server_error'), 500);
        }
    }
}

