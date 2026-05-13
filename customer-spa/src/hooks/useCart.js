import { useState, useEffect, useCallback } from 'react';

const STORAGE_KEY = 'restaurant_cart_v1';

export default function useCart() {
  const [cartItems, setCartItems] = useState(() => {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
    catch { return []; }
  });

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cartItems));
  }, [cartItems]);

  const addToCart = useCallback((product, qty = 1) => {
    setCartItems(prev => {
      const existing = prev.find(i => i.product_id === product.id);
      if (existing) {
        return prev.map(i =>
          i.product_id === product.id ? { ...i, quantity: i.quantity + qty } : i
        );
      }
      return [...prev, {
        product_id: product.id,
        product_name: product.name || '',
        product_price: Number(product.effective_price || product.price || 0),
        product_image: product.image || null,
        quantity: qty,
      }];
    });
  }, []);

  const removeFromCart = useCallback((productId) => {
    setCartItems(prev => prev.filter(i => i.product_id !== productId));
  }, []);

  const changeQty = useCallback((productId, delta) => {
    setCartItems(prev =>
      prev.reduce((acc, item) => {
        if (item.product_id !== productId) return [...acc, item];
        const newQty = item.quantity + delta;
        return newQty <= 0 ? acc : [...acc, { ...item, quantity: newQty }];
      }, [])
    );
  }, []);

  const clearCart = useCallback(() => setCartItems([]), []);

  const cartCount = cartItems.reduce((s, i) => s + i.quantity, 0);
  const cartTotal = cartItems.reduce((s, i) => s + i.product_price * i.quantity, 0);

  return { cartItems, addToCart, removeFromCart, changeQty, clearCart, cartCount, cartTotal };
}
