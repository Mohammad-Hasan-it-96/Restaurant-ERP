import { useState, useEffect, useCallback } from 'react';

const STORAGE_KEY = 'restaurant_cart_v1';

/**
 * Normalizes a cart line from storage (supports legacy `{ id, name, price }` shape).
 */
function normalizeCartItem(raw) {
  if (!raw || typeof raw !== 'object') return null;
  const product_id = Number(raw.product_id ?? raw.id);
  if (!Number.isFinite(product_id)) return null;
  return {
    product_id,
    product_name: String(raw.product_name ?? raw.name ?? '').trim(),
    product_name_ar: String(
      raw.product_name_ar ?? raw.name_ar ?? ''
    ).trim(),
    product_name_en: String(
      raw.product_name_en ?? raw.name_en ?? ''
    ).trim(),
    product_price: parseFloat(raw.product_price ?? raw.price) || 0,
    product_image: raw.product_image ?? raw.image ?? null,
    quantity: Math.max(1, Math.floor(Number(raw.quantity ?? 1))),
  };
}

/**
 * Resolves unit price from product (effective → discount → base).
 */
function unitPriceFromProduct(product) {
  if (!product) return 0;
  const v =
    product.effective_price ?? product.discount_price ?? product.price ?? 0;
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
   * Adds a product to the cart. Returns false if product is unavailable.
   */
  const addToCart = useCallback((product, qty = 1) => {
    if (!product || product.is_available === false) return false;
    const addQty = Math.max(1, Math.floor(Number(qty)) || 1);
    const price = unitPriceFromProduct(product);

    setCartItems((prev) => {
      const existing = prev.find((i) => i.product_id === product.id);
      if (existing) {
        return prev.map((i) =>
          i.product_id === product.id
            ? { ...i, quantity: i.quantity + addQty }
            : i
        );
      }
      return [
        ...prev,
        {
          product_id: product.id,
          product_name_ar: String(product.name_ar ?? '').trim(),
          product_name_en: String(product.name_en ?? '').trim(),
          product_name: String(product.name ?? '').trim(),
          product_price: parseFloat(price) || 0,
          product_image: product.image || null,
          quantity: addQty,
        },
      ];
    });
    return true;
  }, []);

  const removeFromCart = useCallback((productId) => {
    const pid = Number(productId);
    setCartItems((prev) => prev.filter((i) => i.product_id !== pid));
  }, []);

  const changeQty = useCallback((productId, delta) => {
    const pid = Number(productId);
    const d = Number(delta);
    setCartItems((prev) =>
      prev.reduce((acc, item) => {
        if (item.product_id !== pid) return [...acc, item];
        const newQty = item.quantity + d;
        return newQty <= 0 ? acc : [...acc, { ...item, quantity: newQty }];
      }, [])
    );
  }, []);

  const clearCart = useCallback(() => setCartItems([]), []);

  /**
   * Replace the cart with items taken directly from an existing order.
   * Order items shape: { product_id, product_name, product_price, quantity }
   */
  const loadFromOrder = useCallback((orderItems) => {
    const items = (orderItems ?? [])
      .map((item) => ({
        product_id:      Number(item.product_id),
        product_name:    String(item.product_name    ?? '').trim(),
        product_name_ar: String(item.product_name    ?? '').trim(),
        product_name_en: String(item.product_name    ?? '').trim(),
        product_price:   parseFloat(item.product_price ?? 0) || 0,
        product_image:   null,
        quantity:        Math.max(1, Math.floor(Number(item.quantity) || 1)),
      }))
      .filter((i) => i.product_id > 0 && i.quantity > 0);
    setCartItems(items);
  }, []);

  const cartCount = cartItems.reduce((s, i) => s + i.quantity, 0);
  const cartTotal = cartItems.reduce((sum, item) => {
    return sum + (parseFloat(item.product_price) || 0) * item.quantity;
  }, 0);

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
