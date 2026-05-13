import { useState, useEffect } from 'react';
import api, { extractArray, extractData } from '../api/client';

function decodeText(v) {
  if (!v) return '';
  const raw = String(v).trim();
  try { const d = JSON.parse(raw); if (typeof d === 'string') return d; } catch {}
  return raw;
}

export default function useRestaurantData() {
  const [settings, setSettings] = useState({});
  const [categories, setCategories] = useState([]);
  const [allProducts, setAllProducts] = useState([]);
  const [zones, setZones] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  async function fetchAll() {
    setLoading(true);
    setError(null);
    const [sR, cR, pR, zR] = await Promise.allSettled([
      api.get('/settings/public'),
      api.get('/categories'),
      api.get('/products'),
      api.get('/delivery-zones'),
    ]);
    if (sR.status === 'fulfilled') {
      const s = extractData(sR.value.data) || {};
      s.restaurant_name = decodeText(s.restaurant_name) || '';
      setSettings(s);
    }
    if (cR.status === 'fulfilled') {
      setCategories(extractArray(cR.value.data).map(c => ({
        ...c,
        id: Number(c.id),
        parent_id: c.parent_id != null ? Number(c.parent_id) : null,
      })));
    }
    if (pR.status === 'fulfilled') {
      const products = extractArray(pR.value.data).map((p) => ({
        ...p,
        id: Number(p.id),
        category_id: p.category_id != null ? Number(p.category_id) : null,
        price: parseFloat(p.price) || 0,
        discount_price: p.discount_price ? parseFloat(p.discount_price) : null,
        effective_price:
          parseFloat(p.effective_price || p.discount_price || p.price) || 0,
      }));
      setAllProducts(products);
    }
    if (zR.status === 'fulfilled') setZones(extractArray(zR.value.data));
    if ([sR, cR, pR, zR].some(r => r.status === 'rejected')) {
      setError('loadPartialError');
    }
    setLoading(false);
  }

  useEffect(() => { fetchAll().catch(() => setLoading(false)); }, []);

  return { settings, categories, allProducts, zones, loading, error, retry: fetchAll };
}
