import { useState, useEffect, useCallback } from 'react';

const STORAGE_KEY = 'restaurant_cart_v1';

/**
 * Derives a stable cart-line key. Weight-based lines are keyed per weight so
 * the same product can appear multiple times with different weights.
 */
function makeCartKey(productId, weightId) {
  return weightId ? `${productId}_w${weightId}` : String(productId);
}

/**
 * Normalizes a cart line from storage (supports legacy `{ id, name, price }` shape).
 */
function normalizeCartItem(raw) {
  if (!raw || typeof raw !== 'object') return null;
  const product_id = Number(raw.product_id ?? raw.id);
  if (!Number.isFinite(product_id)) return null;
  const weight_id   = raw.weight_id ? Number(raw.weight_id) : null;
  const cart_key    = raw.cart_key || makeCartKey(product_id, weight_id);
  return {
    cart_key,
    product_id,
    product_name:    String(raw.product_name    ?? raw.name    ?? '').trim(),
    product_name_ar: String(raw.product_name_ar ?? raw.name_ar ?? '').trim(),
    product_name_en: String(raw.product_name_en ?? raw.name_en ?? '').trim(),
    product_price:   parseFloat(raw.product_price ?? raw.price) || 0,
    product_image:   raw.product_image ?? raw.image ?? null,
    quantity:        Math.max(1, Math.floor(Number(raw.quantity ?? 1))),
    weight_id,
    weight_name: raw.weight_name || null,
  };
}

/**
 * Resolves unit price from product (effective → discount → base).
 */
function unitPriceFromProduct(product) {
  if (!product) return 0;
  const v = product.effective_price ?? product.discount_price ?? product.price ?? 0;
  const n = parseFloat(v);
  return Number.isNaN(n) ? 0 : n;
}

export default function useCart() {
  const [cartItems, setCartItems] = useState(() => {
    try {
      const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
      if (!Array.isArray(raw)) return [];
      return raw.map(normalizeCartItem).filter(Boolean);
    } catch {
      return [];
    }
  });

  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(cartItems));
    } catch {
      /* quota or private mode */
    }
  }, [cartItems]);

  /**
   * Adds a product to the cart.
   * weightOption = { id, name, value_kg } for weight-based products.
   * Returns false if product is unavailable.
   */
  const addToCart = useCallback((product, qty = 1, weightOption = null) => {
    if (!product || product.is_available === false) return false;
    const addQty = Math.max(1, Math.floor(Number(qty)) || 1);

    const weight_id  = weightOption ? Number(weightOption.id) : null;
    const cart_key   = makeCartKey(product.id, weight_id);
    const price      = weightOption
      ? parseFloat(weightOption.value_kg) * parseFloat(product.price_per_kg || 0)
      : unitPriceFromProduct(product);

    setCartItems((prev) => {
      const existing = prev.find((i) => i.cart_key === cart_key);
      if (existing) {
        return prev.map((i) =>
          i.cart_key === cart_key ? { ...i, quantity: i.quantity + addQty } : i
        );
      }
      return [
        ...prev,
        {
          cart_key,
          product_id:      product.id,
          product_name_ar: String(product.name_ar ?? '').trim(),
          product_name_en: String(product.name_en ?? '').trim(),
          product_name:    String(product.name    ?? '').trim(),
          product_price:   parseFloat(price) || 0,
          product_image:   product.image || null,
          quantity:        addQty,
          weight_id,
          weight_name: weightOption ? weightOption.name : null,
        },
      ];
    });
    return true;
  }, []);

  /** Remove by cart_key (unique per product+weight combination). */
  const removeFromCart = useCallback((cartKey) => {
    setCartItems((prev) => prev.filter((i) => i.cart_key !== cartKey));
  }, []);

  /** Adjust quantity by delta; removes line when quantity reaches 0. */
  const changeQty = useCallback((cartKey, delta) => {
    const d = Number(delta);
    setCartItems((prev) =>
      prev.reduce((acc, item) => {
        if (item.cart_key !== cartKey) return [...acc, item];
        const newQty = item.quantity + d;
        return newQty <= 0 ? acc : [...acc, { ...item, quantity: newQty }];
      }, [])
    );
  }, []);

  const clearCart = useCallback(() => setCartItems([]), []);

  /**
   * Replace the cart with items taken directly from an existing order.
   * Order items shape: { product_id, product_name, product_price, quantity, weight_id?, weight_name? }
   */
  const loadFromOrder = useCallback((orderItems) => {
    const items = (orderItems ?? [])
      .map((item) => {
        const product_id = Number(item.product_id);
        const weight_id  = item.weight_id ? Number(item.weight_id) : null;
        const cart_key   = makeCartKey(product_id, weight_id);
        return {
          cart_key,
          product_id,
          product_name:    String(item.product_name ?? '').trim(),
          product_name_ar: String(item.product_name ?? '').trim(),
          product_name_en: String(item.product_name ?? '').trim(),
          product_price:   parseFloat(item.product_price ?? 0) || 0,
          product_image:   null,
          quantity:        Math.max(1, Math.floor(Number(item.quantity) || 1)),
          weight_id,
          weight_name: item.weight_name || null,
        };
      })
      .filter((i) => i.product_id > 0 && i.quantity > 0);
    setCartItems(items);
  }, []);

  const cartCount = cartItems.reduce((s, i) => s + i.quantity, 0);
  const cartTotal = cartItems.reduce(
    (sum, item) => sum + (parseFloat(item.product_price) || 0) * item.quantity,
    0
  );

  return {
    cartItems,
    addToCart,
    removeFromCart,
    changeQty,
    clearCart,
    loadFromOrder,
    cartCount,
    cartTotal,
  };
}
