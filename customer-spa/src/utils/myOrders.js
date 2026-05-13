/**
 * Reads stored order numbers (newest-first) from localStorage.
 */
export function readMyOrderNumbers() {
  try {
    const raw = localStorage.getItem('my_orders');
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed
      .map((n) => String(n ?? '').trim())
      .filter(Boolean);
  } catch {
    return [];
  }
}

/**
 * Prepends an order number to `my_orders`, deduped.
 */
export function appendMyOrderNumber(orderNumber) {
  const n = String(orderNumber ?? '').trim();
  if (!n || n === '---') return;
  try {
    const existing = readMyOrderNumbers();
    const next = [n, ...existing.filter((x) => x !== n)];
    localStorage.setItem('my_orders', JSON.stringify(next));
  } catch {
    /* ignore quota / private mode */
  }
}
