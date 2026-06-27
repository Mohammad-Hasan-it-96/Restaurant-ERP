<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    /**
     * The session key used to store the cart.
     */
    private const SESSION_KEY = 'cart';

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return the current cart contents together with a calculated subtotal.
     *
     * @return array{items: array, subtotal: float}
     */
    public function getCart(): array
    {
        $items = session()->get(self::SESSION_KEY, []);

        return [
            'items' => array_values($items),
            'subtotal' => $this->calculateSubtotal($items),
        ];
    }

    /**
     * Add a product to the cart or increase its quantity if already present.
     *
     * @throws \InvalidArgumentException When the product is not found or unavailable.
     */
    public function addItem(int $productId, int $quantity): array
    {
        $product = $this->resolveProduct($productId);

        $items = session()->get(self::SESSION_KEY, []);

        if (isset($items[$productId])) {
            $items[$productId]['quantity'] += $quantity;
        } else {
            $items[$productId] = $this->buildItem($product, $quantity);
        }

        session()->put(self::SESSION_KEY, $items);

        logService()->info('cart.item_added', [
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

        return $this->buildResponse($items);
    }

    /**
     * Set the quantity of a cart item explicitly.
     * Passing quantity = 0 removes the item.
     *
     * @throws \InvalidArgumentException When the product is not found or unavailable.
     */
    public function updateItem(int $productId, int $quantity): array
    {
        $this->resolveProduct($productId);   // validates existence & availability

        $items = session()->get(self::SESSION_KEY, []);

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            if (! isset($items[$productId])) {
                throw new \InvalidArgumentException(__('app.cart_item_not_found'));
            }
            $items[$productId]['quantity'] = $quantity;
        }

        session()->put(self::SESSION_KEY, $items);

        logService()->info('cart.item_updated', [
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

        return $this->buildResponse($items);
    }

    /**
     * Remove a single item from the cart.
     */
    public function removeItem(int $productId): array
    {
        $items = session()->get(self::SESSION_KEY, []);

        unset($items[$productId]);

        session()->put(self::SESSION_KEY, $items);

        logService()->info('cart.item_removed', ['product_id' => $productId]);

        return $this->buildResponse($items);
    }

    /**
     * Empty the cart entirely.
     */
    public function clearCart(): array
    {
        session()->forget(self::SESSION_KEY);

        logService()->info('cart.cleared');

        return $this->buildResponse([]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Find the product and ensure it is available.
     *
     * @throws \InvalidArgumentException
     */
    private function resolveProduct(int $productId): Product
    {
        $product = Product::where('id', $productId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->first();

        if (! $product) {
            throw new \InvalidArgumentException(
                __('app.product_not_found_or_unavailable')
            );
        }

        return $product;
    }

    /**
     * Build the item array that is stored in the session.
     */
    private function buildItem(Product $product, int $quantity): array
    {
        return [
            'product_id' => $product->id,
            'name' => $product->display_name,
            'price' => (float) $product->effective_price,
            'quantity' => $quantity,
            'image' => $product->image,
        ];
    }

    /**
     * Calculate the cart subtotal.
     */
    private function calculateSubtotal(array $items): float
    {
        return round(
            array_sum(
                array_map(fn ($item) => $item['price'] * $item['quantity'], $items)
            ),
            2
        );
    }

    /**
     * Build the standard cart response payload.
     *
     * @return array{items: array, subtotal: float}
     */
    private function buildResponse(array $items): array
    {
        return [
            'items' => array_values($items),
            'subtotal' => $this->calculateSubtotal($items),
        ];
    }
}
