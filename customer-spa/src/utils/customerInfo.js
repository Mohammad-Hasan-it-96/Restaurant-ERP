const KEY = 'customer_info';

/**
 * Read cached customer profile from localStorage.
 */
export function readStoredCustomerInfo() {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return { name: '', phone: '', address: '' };
    const o = JSON.parse(raw);
    return {
      name:    String(o?.name    ?? '').trim(),
      phone:   String(o?.phone   ?? '').trim(),
      address: String(o?.address ?? '').trim(),
    };
  } catch {
    return { name: '', phone: '', address: '' };
  }
}

/**
 * Write customer profile to localStorage.
 */
export function writeStoredCustomerInfo({ name = '', phone = '', address = '' }) {
  try {
    const existing = readStoredCustomerInfo();
    localStorage.setItem(KEY, JSON.stringify({
      name:    name    || existing.name,
      phone:   phone   || existing.phone,
      address: address || existing.address,
    }));
  } catch {
    /* ignore quota / private mode */
  }
}

