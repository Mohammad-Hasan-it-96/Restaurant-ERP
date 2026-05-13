import axios from 'axios';

const api = axios.create({ baseURL: '/api/v1' });

export default api;

/** Extract an array from various API response shapes */
export function extractArray(body) {
  if (!body) return [];
  if (Array.isArray(body)) return body;
  if (Array.isArray(body.data)) return body.data;
  if (Array.isArray(body.data?.data)) return body.data.data;
  if (Array.isArray(body.data?.data?.data)) return body.data.data.data;
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

/**
 * Decodes API strings that may arrive as JSON-escaped Unicode (e.g. "\\u0627...").
 */
export function decodeApiText(value, fallback = '') {
  if (value == null || value === '') return fallback;
  const raw = String(value).trim();
  if (!raw) return fallback;
  try {
    const d = JSON.parse(raw);
    if (typeof d === 'string') return d;
  } catch {
    /* ignore */
  }
  if (raw.includes('\\u')) {
    try {
      const w = JSON.parse(`"${raw.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`);
      if (typeof w === 'string') return w;
    } catch {
      /* ignore */
    }
  }
  return raw;
}

