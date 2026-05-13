import { useState, useEffect } from 'react';
import { CloseIcon } from './Icons';
import api, { extractData, decodeApiText } from '../api/client';
import { appendMyOrderNumber } from '../utils/myOrders';
import { formatPrice } from '../utils/format';
import { useI18n } from '../i18n';

export default function CheckoutModal({
  zones,
  cartItems,
  cartTotal,
  onSuccess,
  onClose,
}) {
  const { t } = useI18n();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [orderType, setOrderType] = useState('table');
  const [tableNum, setTableNum] = useState('');
  const [address, setAddress] = useState('');
  const [savedAddress, setSavedAddress] = useState('');
  const [showAddressSuggestion, setShowAddressSuggestion] = useState(true);
  const [dlvType, setDlvType] = useState('immediate');
  const [scheduled, setScheduled] = useState('');
  const [zone, setZone] = useState('');
  const [note, setNote] = useState('');
  const [errors, setErrors] = useState({});
  const [apiError, setApiError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const id = setTimeout(() => setVisible(true), 10);
    return () => clearTimeout(id);
  }, []);

  /**
   * Reads name, phone, and optional delivery address from localStorage.
   */
  function readStoredCustomerInfo() {
    try {
      const raw = localStorage.getItem('customer_info');
      if (!raw) return { name: '', phone: '', address: '' };
      const o = JSON.parse(raw);
      return {
        name: String(o?.name ?? '').trim(),
        phone: String(o?.phone ?? '').trim(),
        address: String(o?.address ?? '').trim(),
      };
    } catch {
      return { name: '', phone: '', address: '' };
    }
  }

  /**
   * On open: load from GET /customer/me when session has a customer; otherwise
   * fill gaps from localStorage key `customer_info`.
   */
  useEffect(() => {
    let cancelled = false;
    (async () => {
      let apiName = '';
      let apiPhone = '';
      let apiDefaultAddress = '';
      try {
        const res = await api.get('/customer/me');
        const info = extractData(res.data);
        if (info && typeof info === 'object' && !Array.isArray(info)) {
          apiName = String(info.name ?? '').trim();
          apiPhone = String(info.phone ?? '').trim();
          apiDefaultAddress = String(info.default_address ?? '').trim();
        }
      } catch {
        /* network or server error — use storage below */
      }
      const stored = readStoredCustomerInfo();
      const resolvedSavedAddress =
        apiDefaultAddress || String(stored.address ?? '').trim();
      if (!cancelled) {
        setName(apiName || stored.name);
        setPhone(apiPhone || stored.phone);
        setSavedAddress(resolvedSavedAddress);
        setShowAddressSuggestion(!!resolvedSavedAddress);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const close = () => {
    setVisible(false);
    setTimeout(onClose, 280);
  };

  function validate() {
    const e = {};
    if (!name.trim()) e.name = t('reqName');
    if (!phone.trim()) e.phone = t('reqPhone');
    if (orderType === 'table' && !tableNum.trim()) e.tableNum = t('reqTable');
    if (orderType === 'delivery') {
      if (!address.trim()) e.address = t('reqAddress');
      if (dlvType === 'scheduled' && !scheduled) e.scheduled = t('reqScheduled');
    }
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  async function handleSubmit(ev) {
    ev.preventDefault();
    if (!cartItems?.length) {
      setApiError(t('emptyCart'));
      return;
    }
    if (!validate()) return;
    setSubmitting(true);
    setApiError('');
    try {
      const payload = {
        customer_name: name.trim(),
        customer_phone: phone.trim(),
        order_type: orderType,
        table_number: orderType === 'table' ? tableNum.trim() : null,
        address: orderType === 'delivery' ? address.trim() : null,
        delivery_type: orderType === 'delivery' ? dlvType : null,
        scheduled_at:
          orderType === 'delivery' && dlvType === 'scheduled' ? scheduled : null,
        estimated_delivery_fee:
          orderType === 'delivery' && zone ? Number(zone) : null,
        customer_note: note.trim() || null,
        items: cartItems.map((i) => ({
          product_id: i.product_id,
          quantity: i.quantity,
        })),
      };
      const res = await api.post('/orders', payload);
      const data = extractData(res.data) || res.data || {};
      const storedInfo = readStoredCustomerInfo();
      const addressToStore =
        orderType === 'delivery' ? address.trim() : storedInfo.address;
      try {
        localStorage.setItem(
          'customer_info',
          JSON.stringify({
            name: name.trim(),
            phone: phone.trim(),
            address: addressToStore,
          })
        );
      } catch {
        /* ignore */
      }
      const orderNo = data.order_number || data.id || t('orderUnknown');
      appendMyOrderNumber(orderNo);
      onSuccess(orderNo);
    } catch (err) {
      const rawMsg =
        err.response?.data?.message || err.message || t('failedLoad');
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

  const Field = ({ id, label, error, children }) => (
    <div className="form-group">
      <label className="form-label" htmlFor={id}>
        {label}
      </label>
      {children}
      {error && <div className="form-error">{error}</div>}
    </div>
  );

  return (
    <div
      className="checkout-overlay"
      style={{ opacity: visible ? 1 : 0, transition: 'opacity 0.25s' }}
      onClick={(e) => {
        if (e.target === e.currentTarget) close();
      }}
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
          <button
            type="button"
            className="icon-btn"
            onClick={close}
            aria-label={t('close')}
          >
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
              {savedAddress.trim() &&
                !address.trim() &&
                showAddressSuggestion && (
                  <button
                    type="button"
                    className="address-suggestion-chip"
                    onClick={() => {
                      setAddress(savedAddress);
                      setShowAddressSuggestion(false);
                    }}
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
                    const v = e.target.value;
                    setAddress(v);
                    if (v !== '') setShowAddressSuggestion(false);
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
                <Field
                  id="scheduled"
                  label={t('scheduledAt')}
                  error={errors.scheduled}
                >
                  <input
                    id="scheduled"
                    type="datetime-local"
                    className={`form-input${errors.scheduled ? ' error' : ''}`}
                    value={scheduled}
                    onChange={(e) => setScheduled(e.target.value)}
                  />
                </Field>
              )}
              <Field id="zone" label={t('deliveryZone')} error={null}>
                <select
                  id="zone"
                  className="form-select"
                  value={zone}
                  onChange={(e) => setZone(e.target.value)}
                >
                  <option value="">
                    {zones.length ? t('selectZone') : t('noZones')}
                  </option>
                  {zones.map((z) => (
                    <option
                      key={z.id || z.area_name}
                      value={z.estimated_fee}
                    >
                      {z.area_name} ({formatPrice(z.estimated_fee)})
                    </option>
                  ))}
                </select>
              </Field>
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
          <div
            style={{
              display: 'flex',
              justifyContent: 'space-between',
              marginBottom: 12,
              fontSize: 14,
            }}
          >
            <span>{t('subtotal')}</span>
            <strong style={{ fontSize: 18 }}>{formatPrice(cartTotal)}</strong>
          </div>
          <button type="submit" className="btn-submit" disabled={submitting}>
            {submitting ? (
              <>
                <span className="spinner" />
                {t('sending')}
              </>
            ) : (
              t('placeOrder')
            )}
          </button>
        </div>
      </form>
    </div>
  );
}
