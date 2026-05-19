const KEY_NUMBERS = 'my_orders';
const KEY_OBJECTS = 'my_orders_data';

const MAX_STORED = 30;

/**
 * Reads stored order numbers (newest-first) from localStorage.
 */
export function readMyOrderNumbers() {
  try {
    const raw = localStorage.getItem(KEY_NUMBERS);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed.map((n) => String(n ?? '').trim()).filter(Boolean);
  } catch {
    return [];
  }
}

/**
 * Reads full order objects cached locally (newest-first).
 */
export function readMyOrderObjects() {
  try {
    const raw = localStorage.getItem(KEY_OBJECTS);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

/**
 * Prepends an order number to `my_orders`, deduped.
 * Optionally stores the full order object.
 */
export function appendMyOrderNumber(orderNumber, orderObject = null) {
  const n = String(orderNumber ?? '').trim();
  if (!n || n === '---') return;

  try {
    // Store number
    const existing = readMyOrderNumbers();
    const next = [n, ...existing.filter((x) => x !== n)].slice(0, MAX_STORED);
    localStorage.setItem(KEY_NUMBERS, JSON.stringify(next));

    // Store full object if provided
    if (orderObject && typeof orderObject === 'object') {
      const objs = readMyOrderObjects();
      const nextObjs = [
        orderObject,
        ...objs.filter((o) => o.order_number !== n),
      ].slice(0, MAX_STORED);
      localStorage.setItem(KEY_OBJECTS, JSON.stringify(nextObjs));
    }
  } catch {
    /* ignore quota / private mode */
  }
}
