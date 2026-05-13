import axios from 'axios';

const api = axios.create({ baseURL: '/api/v1' });

export default api;

/** Extract an array from various API response shapes */
export function extractArray(body) {
  if (!body) return [];
  if (Array.isArray(body)) return body;
  if (Array.isArray(body.data)) return body.data;
  if (Array.isArray(body.data?.data)) return body.data.data;
  if (Array.isArray(body.data?.items)) return body.data.items;
  return [];
}

/** Extract a single object from various API response shapes */
export function extractData(body) {
  if (!body) return null;
  const d = body.success !== undefined ? body.data : body;
  if (!d) return null;
  if (!Array.isArray(d) && Array.isArray(d.data)) return null;
  return d;
}

/** Format a price number */
export function formatPrice(value, isRtl = false) {
  const n = Number(value ?? 0);
  return n.toLocaleString(isRtl ? 'ar-SY' : 'en-US');
}

