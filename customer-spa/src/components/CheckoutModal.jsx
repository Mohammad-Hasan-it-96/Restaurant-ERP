import { useState, useEffect } from 'react';
import { CloseIcon } from './Icons';
import api, { extractData, decodeApiText } from '../api/client';
import { appendMyOrderNumber } from '../utils/myOrders';
import { readStoredCustomerInfo, writeStoredCustomerInfo } from '../utils/customerInfo';
import { formatPrice } from '../utils/format';
import { useI18n } from '../i18n';

// ── Defined OUTSIDE component so React never remounts inputs on re-render ──
function Field({ id, label, error, children }) {
  return (
    <div className="form-group">
      <label className="form-label" htmlFor={id}>{label}</label>
      {children}
      {error && <div className="form-error">{error}</div>}
    </div>
  );
}

export default function CheckoutModal({ zones, cartItems, cartTotal, onSuccess, onClose }) {
  const { t } = useI18n();

  const [name,       setName]       = useState('');
  const [phone,      setPhone]      = useState('');
  const [orderType,  setOrderType]  = useState('table');
  const [tableNum,   setTableNum]   = useState('');
  const [address,    setAddress]    = useState('');
  const [savedAddress, setSavedAddress]             = useState('');
  const [showAddressSuggestion, setShowAddressSuggestion] = useState(false);
  const [dlvType,    setDlvType]    = useState('immediate');
  const [scheduled,  setScheduled]  = useState('');
  const [note,       setNote]       = useState('');
  const [errors,     setErrors]     = useState({});
  const [apiError,   setApiError]   = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [visible,    setVisible]    = useState(false);

  // Slide-in animation
  useEffect(() => {
    const id = setTimeout(() => setVisible(true), 10);
    return () => clearTimeout(id);
  }, []);

  // Load customer info from API (session) → fallback to localStorage
  useEffect(() => {
    let cancelled = false;
    (async () => {
      let apiName = '', apiPhone = '', apiDefaultAddress = '';
      try {
        const res = await api.get('/customer/me');
        const info = res.data?.data;
        if (info && !Array.isArray(info)) {
          apiName           = String(info.name            ?? '').trim();
          apiPhone          = String(info.phone           ?? '').trim();
          apiDefaultAddress = String(info.default_address ?? '').trim();
        }
      } catch { /* ignore */ }

      const stored = readStoredCustomerInfo();
      const resolvedAddr = apiDefaultAddress || String(stored.address ?? '').trim();

      if (!cancelled) {
        setName(apiName || stored.name || '');
        setPhone(apiPhone || stored.phone || '');
        setSavedAddress(resolvedAddr);
        setShowAddressSuggestion(!!resolvedAddr);
      }
    })();
    return () => { cancelled = true; };
  }, []);

  const close = () => {
    setVisible(false);
    setTimeout(onClose, 280);
  };

  function validate() {
    const e = {};
    if (!name.trim())    e.name    = t('reqName');
    if (!phone.trim())   e.phone   = t('reqPhone');
    if (orderType === 'table'    && !tableNum.trim()) e.tableNum  = t('reqTable');
    if (orderType === 'delivery' && !address.trim())  e.address   = t('reqAddress');
    if (orderType === 'delivery' && dlvType === 'scheduled' && !scheduled)
      e.scheduled = t('reqScheduled');
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  async function handleSubmit(ev) {
    ev.preventDefault();
    if (!cartItems?.length) { setApiError(t('emptyCart')); return; }
    if (!validate()) return;
    setSubmitting(true);
    setApiError('');
    try {
      const payload = {
        customer_name:  name.trim(),
        customer_phone: phone.trim(),
        order_type:     orderType,
        table_number:   orderType === 'table'    ? tableNum.trim() : null,
        address:        orderType === 'delivery' ? address.trim()  : null,
        delivery_type:  orderType === 'delivery' ? dlvType         : null,
        scheduled_at:   orderType === 'delivery' && dlvType === 'scheduled' ? scheduled : null,
        customer_note:  note.trim() || null,
        items: cartItems.map((i) => ({ product_id: i.product_id, quantity: i.quantity })),
      };
      const res    = await api.post('/orders', payload);
      const data   = extractData(res.data) || res.data || {};
      // Store customer token for future authenticated requests
      if (data.customer_token) {
        localStorage.setItem('customer_token', data.customer_token);
      }
      const stored = readStoredCustomerInfo();
      writeStoredCustomerInfo({
        name:    name.trim(),
        phone:   phone.trim(),
        address: orderType === 'delivery' ? address.trim() : stored.address,
      });
      const orderNo = data.order_number || data.id || t('orderUnknown');
      appendMyOrderNumber(orderNo, data); // cache full order object locally
      onSuccess(orderNo);
    } catch (err) {
      const rawMsg = err.response?.data?.message || err.message || t('failedLoad');
      setApiError(decodeApiText(String(rawMsg), t('failedLoad')));
      if (err.response?.data?.errors) {
        const se = {};
        Object.entries(err.response.data.errors).forEach(([k, v]) => {
          const first = Array.isArray(v) ? v[0] : v;
          se[k] = decodeApiText(String(first ?? ''), String(first ?? ''));
        });
        setErrors((prev) => ({ ...prev, ...se }));
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div
      className="checkout-overlay"
      style={{ opacity: visible ? 1 : 0, transition: 'opacity 0.25s' }}
      onClick={(e) => { if (e.target === e.currentTarget) close(); }}
    >
      <form
        className="checkout-sheet"
        style={{
          transform:  visible ? 'translateY(0)' : 'translateY(100%)',
          transition: 'transform 0.3s cubic-bezier(0.4,0,0.2,1)',
        }}
        onSubmit={handleSubmit}
      >
        <div className="checkout-header">
          <h2 className="checkout-title">{t('orderDetails')}</h2>
          <button type="button" className="icon-btn" onClick={close} aria-label={t('close')}>
            <CloseIcon />
          </button>
        </div>

        <div className="checkout-body">
          {apiError && <div className="api-error">{apiError}</div>}

          <Field id="name" label={t('fullName')} error={errors.name}>
            <input
              id="name"
              className={`form-input${errors.name ? ' error' : ''}`}
              value={name}
              onChange={(e) => setName(e.target.value)}
              autoComplete="name"
            />
          </Field>

          <Field id="phone" label={t('phoneNumber')} error={errors.phone}>
            <input
              id="phone"
              className={`form-input${errors.phone ? ' error' : ''}`}
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              type="tel"
              dir="ltr"
              autoComplete="tel"
            />
          </Field>

          <Field id="orderType" label={t('orderType')} error={null}>
            <div className="order-type-btns">
              {['table', 'delivery', 'takeaway'].map((ot) => (
                <button
                  key={ot}
                  type="button"
                  className={`order-type-btn${orderType === ot ? ' active' : ''}`}
                  onClick={() => setOrderType(ot)}
                >
                  {t(ot)}
                </button>
              ))}
            </div>
          </Field>

          {orderType === 'table' && (
            <div className="conditional-section">
              <Field id="tableNum" label={t('tableNumber')} error={errors.tableNum}>
                <input
                  id="tableNum"
                  className={`form-input${errors.tableNum ? ' error' : ''}`}
                  value={tableNum}
                  onChange={(e) => setTableNum(e.target.value)}
                />
              </Field>
            </div>
          )}

          {orderType === 'delivery' && (
            <div className="conditional-section">
              {showAddressSuggestion && savedAddress && !address && (
                <button
                  type="button"
                  className="address-suggestion-chip"
                  onClick={() => { setAddress(savedAddress); setShowAddressSuggestion(false); }}
                  aria-label={`${t('previousAddressPrefix')} ${savedAddress} ${t('useThisAddress')}`}
                >
                  {t('previousAddressPrefix')}{' '}
                  <span className="address-suggestion-text">{savedAddress}</span>{' '}
                  {t('useThisAddress')}
                </button>
              )}

              <Field id="address" label={t('address')} error={errors.address}>
                <textarea
                  id="address"
                  className={`form-textarea${errors.address ? ' error' : ''}`}
                  rows={2}
                  value={address}
                  onChange={(e) => {
                    setAddress(e.target.value);
                    if (e.target.value) setShowAddressSuggestion(false);
                  }}
                />
              </Field>

              <Field id="dlvType" label={t('deliveryType')} error={null}>
                <select
                  id="dlvType"
                  className="form-select"
                  value={dlvType}
                  onChange={(e) => setDlvType(e.target.value)}
                >
                  <option value="immediate">{t('immediate')}</option>
                  <option value="scheduled">{t('scheduled')}</option>
                </select>
              </Field>

              {dlvType === 'scheduled' && (
                <Field id="scheduled" label={t('scheduledAt')} error={errors.scheduled}>
                  <input
                    id="scheduled"
                    type="datetime-local"
                    className={`form-input${errors.scheduled ? ' error' : ''}`}
                    value={scheduled}
                    onChange={(e) => setScheduled(e.target.value)}
                  />
                </Field>
              )}
            </div>
          )}

          <Field id="note" label={t('notes')} error={null}>
            <textarea
              id="note"
              className="form-textarea"
              rows={2}
              placeholder={t('notesPlaceholder')}
              value={note}
              onChange={(e) => setNote(e.target.value)}
            />
          </Field>
        </div>

        <div className="checkout-footer">
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 12, fontSize: 14 }}>
            <span>{t('subtotal')}</span>
            <strong style={{ fontSize: 18 }}>{formatPrice(cartTotal)}</strong>
          </div>
          <button type="submit" className="btn-submit" disabled={submitting}>
            {submitting ? (<><span className="spinner" />{t('sending')}</>) : t('placeOrder')}
          </button>
        </div>
      </form>
    </div>
  );
}
