import { useState, useEffect } from 'react';
import api from '../api/client';
import { writeStoredCustomerInfo } from '../utils/customerInfo';
import { useI18n } from '../i18n';
import OrderHistory from './OrderHistory';

export default function MyProfile() {
  const { t } = useI18n();

  const [loading, setLoading]     = useState(true);
  const [saving,  setSaving]      = useState(false);
  const [success, setSuccess]     = useState(false);
  const [apiError, setApiError]   = useState('');
  const [apiOrders, setApiOrders] = useState(null);

  const [form, setForm]   = useState({ name: '', phone: '', address: '' });
  const [errors, setErrors] = useState({});

  function applyProfile(data) {
    setForm({
      name:    data?.name            || '',
      phone:   data?.phone           || '',
      address: data?.default_address || '',
    });
  }

  // Load profile from API on mount — single source of truth
  useEffect(() => {
    let cancelled = false;

    api.get('/customer/me')
      .then((res) => {
        if (cancelled) return;
        const data = res.data?.data;
        // data is [] when no session, or object when session exists
        if (data && !Array.isArray(data) && (data.name || data.phone)) {
          applyProfile(data);
          writeStoredCustomerInfo({
            name:    data.name,
            phone:   data.phone,
            address: data.default_address,
          });
          if (Array.isArray(data.orders)) {
            setApiOrders(data.orders);
          }
        }
      })
      .catch(() => {/* network error — form stays empty */})
      .finally(() => { if (!cancelled) setLoading(false); });

    return () => { cancelled = true; };
  }, []);

  function validate() {
    const e = {};
    if (!form.name.trim())  e.name  = t('reqName');
    if (!form.phone.trim()) e.phone = t('reqPhone');
    return e;
  }

  async function handleSubmit(ev) {
    ev.preventDefault();
    const e = validate();
    if (Object.keys(e).length) { setErrors(e); return; }
    setErrors({});
    setApiError('');
    setSuccess(false);
    setSaving(true);

    try {
      const res = await api.post('/customer/update', {
        name:    form.name.trim(),
        phone:   form.phone.trim(),
        address: form.address.trim(),
      });
      const data = res.data?.data;
      if (data && !Array.isArray(data)) {
        applyProfile(data);
        writeStoredCustomerInfo({
          name:    data.name,
          phone:   data.phone,
          address: data.default_address,
        });
      }
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
    } catch (err) {
      const msg = err.response?.data?.message
               || err.response?.data?.errors?.phone?.[0]
               || err.response?.data?.errors?.name?.[0]
               || t('failedLoad');
      setApiError(msg);
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="profile-page">
      {/* ── Profile form ───────────────────────────────── */}
      <section className="profile-section">
        <div className="section-header">
          <h2>{t('myAccount')}</h2>
        </div>

        {loading ? (
          <div className="profile-skeleton">
            <div className="skeleton" style={{ height: 48, marginBottom: 12 }} />
            <div className="skeleton" style={{ height: 48, marginBottom: 12 }} />
            <div className="skeleton" style={{ height: 48 }} />
          </div>
        ) : (
          <form className="profile-form" onSubmit={handleSubmit} noValidate>
            {apiError && <div className="api-error">{apiError}</div>}
            {success  && <div className="api-success">{t('profileSaved')}</div>}

            <div className="form-group">
              <label className="form-label">{t('fullName')}</label>
              <input
                className={`form-input${errors.name ? ' error' : ''}`}
                type="text"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                placeholder={t('fullName')}
              />
              {errors.name && <p className="form-error">{errors.name}</p>}
            </div>

            <div className="form-group">
              <label className="form-label">{t('phoneNumber')}</label>
              <input
                className={`form-input${errors.phone ? ' error' : ''}`}
                type="tel"
                value={form.phone}
                onChange={(e) => setForm({ ...form, phone: e.target.value })}
                placeholder={t('phoneNumber')}
                dir="ltr"
              />
              {errors.phone && <p className="form-error">{errors.phone}</p>}
            </div>

            <div className="form-group">
              <label className="form-label">{t('address')}</label>
              <input
                className="form-input"
                type="text"
                value={form.address}
                onChange={(e) => setForm({ ...form, address: e.target.value })}
                placeholder={t('address')}
              />
            </div>

            <button type="submit" className="btn-submit" disabled={saving}>
              {saving ? <span className="spinner" /> : null}
              {saving ? t('saving') : t('saveProfile')}
            </button>
          </form>
        )}
      </section>

      {/* ── Order history ───────────────────────────────── */}
      <OrderHistory orders={apiOrders} />
    </div>
  );
}
