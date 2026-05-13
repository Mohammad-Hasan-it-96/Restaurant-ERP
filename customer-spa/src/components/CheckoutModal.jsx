import { useState, useEffect } from 'react';
import { CloseIcon } from './Icons';
import api, { extractData, formatPrice } from '../api/client';
import { createT } from '../i18n';

export default function CheckoutModal({ zones, cartItems, cartTotal, onSuccess, onClose, isRtl }) {
  const t = createT(isRtl);

  const [name, setName]             = useState('');
  const [phone, setPhone]           = useState('');
  const [orderType, setOrderType]   = useState('table');
  const [tableNum, setTableNum]     = useState('');
  const [address, setAddress]       = useState('');
  const [dlvType, setDlvType]       = useState('immediate');
  const [scheduled, setScheduled]   = useState('');
  const [zone, setZone]             = useState('');
  const [note, setNote]             = useState('');
  const [errors, setErrors]         = useState({});
  const [apiError, setApiError]     = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [visible, setVisible]       = useState(false);

  useEffect(() => { setTimeout(() => setVisible(true), 10); }, []);

  // Auto-fill from server session or localStorage
  useEffect(() => {
    fetch('/api/v1/customer/me', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(r => r.ok ? r.json() : null)
      .then(body => {
        const info = body?.data;
        if (info?.name)  setName(info.name);
        if (info?.phone) setPhone(info.phone);
      })
      .catch(() => {
        try {
          const saved = JSON.parse(localStorage.getItem('customer_info') || 'null');
          if (saved?.name)  setName(saved.name);
          if (saved?.phone) setPhone(saved.phone);
        } catch {}
      });
  }, []);

  const close = () => { setVisible(false); setTimeout(onClose, 280); };

  function validate() {
    const e = {};
    if (!name.trim())  e.name  = t('reqName');
    if (!phone.trim()) e.phone = t('reqPhone');
    if (orderType === 'table' && !tableNum.trim())
      e.tableNum = t('reqTable');
    if (orderType === 'delivery') {
      if (!address.trim()) e.address = t('reqAddress');
      if (dlvType === 'scheduled' && !scheduled) e.scheduled = t('reqScheduled');
    }
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  async function handleSubmit(ev) {
    ev.preventDefault();
    if (!validate()) return;
    setSubmitting(true);
    setApiError('');
    try {
      const payload = {
        customer_name:  name.trim(),
        customer_phone: phone.trim(),
        order_type:     orderType,
        table_number:   orderType === 'table'     ? tableNum.trim() : null,
        address:        orderType === 'delivery'  ? address.trim()  : null,
        delivery_type:  orderType === 'delivery'  ? dlvType : null,
        scheduled_at:   orderType === 'delivery' && dlvType === 'scheduled' ? scheduled : null,
        estimated_delivery_fee: orderType === 'delivery' && zone ? Number(zone) : null,
        customer_note:  note.trim() || null,
        items: cartItems.map(i => ({ product_id: i.product_id, quantity: i.quantity })),
      };
      const res  = await api.post('/orders', payload);
      const data = extractData(res.data) || res.data || {};
      try { localStorage.setItem('customer_info', JSON.stringify({ name: name.trim(), phone: phone.trim() })); } catch {}
      onSuccess(data.order_number || data.id || '---');
    } catch (err) {
      const msg = err.response?.data?.message || err.message || t('failedLoad');
      setApiError(msg);
      if (err.response?.data?.errors) {
        const se = {};
        Object.entries(err.response.data.errors).forEach(([k, v]) => {
          se[k] = Array.isArray(v) ? v[0] : v;
        });
        setErrors(prev => ({ ...prev, ...se }));
      }
    } finally { setSubmitting(false); }
  }

  const Field = ({ id, label, error, children }) => (
    <div className="form-group">
      <label className="form-label" htmlFor={id}>{label}</label>
      {children}
      {error && <div className="form-error">{error}</div>}
    </div>
  );

  return (
    <div
      className="checkout-overlay"
      style={{ opacity: visible ? 1 : 0, transition: 'opacity 0.25s' }}
      onClick={e => { if (e.target === e.currentTarget) close(); }}
    >
      <form
        className="checkout-sheet"
        style={{
          transform: visible ? 'translateY(0)' : 'translateY(100%)',
          transition: 'transform 0.3s cubic-bezier(0.4,0,0.2,1)',
        }}
        onSubmit={handleSubmit}
      >
        <div className="checkout-header">
          <h2 className="checkout-title">{t('orderDetails')}</h2>
          <button type="button" className="icon-btn" onClick={close}><CloseIcon /></button>
        </div>

        <div className="checkout-body">
          {apiError && <div className="api-error">{apiError}</div>}

          <Field id="name" label={t('fullName')} error={errors.name}>
            <input id="name" className={`form-input${errors.name?' error':''}`}
                   value={name} onChange={e => setName(e.target.value)} autoComplete="name" />
          </Field>

          <Field id="phone" label={t('phoneNumber')} error={errors.phone}>
            <input id="phone" className={`form-input${errors.phone?' error':''}`}
                   value={phone} onChange={e => setPhone(e.target.value)}
                   type="tel" autoComplete="tel" />
          </Field>

          <Field id="orderType" label={t('orderType')} error={null}>
            <div className="order-type-btns">
              {['table','delivery','takeaway'].map(ot => (
                <button key={ot} type="button"
                        className={`order-type-btn${orderType===ot?' active':''}`}
                        onClick={() => setOrderType(ot)}>
                  {t(ot)}
                </button>
              ))}
            </div>
          </Field>

          {orderType === 'table' && (
            <div className="conditional-section">
              <Field id="tableNum" label={t('tableNumber')} error={errors.tableNum}>
                <input id="tableNum" className={`form-input${errors.tableNum?' error':''}`}
                       value={tableNum} onChange={e => setTableNum(e.target.value)} />
              </Field>
            </div>
          )}

          {orderType === 'delivery' && (
            <div className="conditional-section">
              <Field id="address" label={t('address')} error={errors.address}>
                <textarea id="address" className={`form-textarea${errors.address?' error':''}`}
                          rows={2} value={address} onChange={e => setAddress(e.target.value)} />
              </Field>
              <Field id="dlvType" label={t('deliveryType')} error={null}>
                <select id="dlvType" className="form-select"
                        value={dlvType} onChange={e => setDlvType(e.target.value)}>
                  <option value="immediate">{t('immediate')}</option>
                  <option value="scheduled">{t('scheduled')}</option>
                </select>
              </Field>
              {dlvType === 'scheduled' && (
                <Field id="scheduled" label={t('scheduledAt')} error={errors.scheduled}>
                  <input id="scheduled" type="datetime-local"
                         className={`form-input${errors.scheduled?' error':''}`}
                         value={scheduled} onChange={e => setScheduled(e.target.value)} />
                </Field>
              )}
              <Field id="zone" label={t('deliveryZone')} error={null}>
                <select id="zone" className="form-select"
                        value={zone} onChange={e => setZone(e.target.value)}>
                  <option value="">{zones.length ? t('selectZone') : t('noZones')}</option>
                  {zones.map(z => (
                    <option key={z.id || z.area_name} value={z.estimated_fee}>
                      {z.area_name} ({formatPrice(z.estimated_fee, isRtl)})
                    </option>
                  ))}
                </select>
              </Field>
            </div>
          )}

          <Field id="note" label={t('notes')} error={null}>
            <textarea id="note" className="form-textarea" rows={2}
                      placeholder={t('notesPlaceholder')}
                      value={note} onChange={e => setNote(e.target.value)} />
          </Field>
        </div>

        <div className="checkout-footer">
          <div style={{ display:'flex', justifyContent:'space-between', marginBottom:12, fontSize:14 }}>
            <span>{t('subtotal')}</span>
            <strong style={{ fontSize:18 }}>{formatPrice(cartTotal, isRtl)}</strong>
          </div>
          <button type="submit" className="btn-submit" disabled={submitting}>
            {submitting ? (
              <><span className="spinner" />{t('sending')}</>
            ) : t('placeOrder')}
          </button>
        </div>
      </form>
    </div>
  );
}
