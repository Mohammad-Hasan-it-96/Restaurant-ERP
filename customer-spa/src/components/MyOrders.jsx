import { useState, useEffect } from 'react';
import api, { decodeApiText } from '../api/client';
import { formatPrice } from '../utils/format';
import { readMyOrderObjects } from '../utils/myOrders';
import { useI18n } from '../i18n';
import { CloseIcon } from './Icons';

// ── Status badge helper ───────────────────────────────────────────────────────
function statusMod(status) {
  if (!status) return 'muted';
  if (['cancelled', 'cancelled_by_admin', 'cancelled_by_customer', 'rejected'].includes(status))
    return 'danger';
  if (['completed', 'delivered'].includes(status)) return 'success';
  if (status === 'ready')    return 'ready';
  if (['accepted', 'preparing'].includes(status)) return 'progress';
  return 'pending';
}

// ── Order detail modal ────────────────────────────────────────────────────────
function OrderDetailModal({ order, onClose }) {
  const { t, lang } = useI18n();
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const id = setTimeout(() => setVisible(true), 10);
    return () => clearTimeout(id);
  }, []);

  const close = () => {
    setVisible(false);
    setTimeout(onClose, 260);
  };

  const items = order.items ?? [];

  return (
    <div
      className="checkout-overlay"
      style={{ opacity: visible ? 1 : 0, transition: 'opacity 0.25s' }}
      onClick={(e) => { if (e.target === e.currentTarget) close(); }}
    >
      <div
        className="checkout-sheet"
        style={{
          transform:  visible ? 'translateY(0)' : 'translateY(100%)',
          transition: 'transform 0.28s cubic-bezier(0.4,0,0.2,1)',
        }}
      >
        {/* Header */}
        <div className="checkout-header">
          <div>
            <h2 className="checkout-title">{t('orderDetailTitle')}</h2>
            <span style={{ fontSize: 12, color: 'var(--text-muted)' }}>
              {order.order_number}
            </span>
          </div>
          <button type="button" className="icon-btn" onClick={close} aria-label={t('close')}>
            <CloseIcon />
          </button>
        </div>

        {/* Body */}
        <div className="checkout-body">
          {/* Status + Date row */}
          <div className="order-detail-meta">
            <span className={`order-status-badge status-${statusMod(order.status)}`}>
              {t(`orderStatus_${order.status}`) || order.status}
            </span>
            <span className="order-history-meta" style={{ margin: 0 }}>
              {order.created_at
                ? new Date(order.created_at).toLocaleString(
                    lang === 'ar' ? 'ar' : 'en-US',
                    { dateStyle: 'medium', timeStyle: 'short' }
                  )
                : ''}
            </span>
          </div>

          {/* Items table */}
          {items.length === 0 ? (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '24px 0' }}>
              {t('noItemsInOrder')}
            </p>
          ) : (
            <table className="order-items-table">
              <thead>
                <tr>
                  <th>{t('itemName')}</th>
                  <th style={{ textAlign: 'center' }}>{t('itemQty')}</th>
                  <th style={{ textAlign: 'end' }}>{t('itemTotal')}</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item, i) => (
                  <tr key={i}>
                    <td>{item.product_name}</td>
                    <td style={{ textAlign: 'center' }}>{item.quantity}</td>
                    <td style={{ textAlign: 'end', fontWeight: 600 }}>
                      {formatPrice(item.total)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        {/* Footer — total */}
        <div className="checkout-footer">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: 15, fontWeight: 600 }}>{t('orderTotal')}</span>
            <strong style={{ fontSize: 20, color: 'var(--primary)' }}>
              {formatPrice(order.total)}
            </strong>
          </div>
        </div>
      </div>
    </div>
  );
}

// ── Main page ─────────────────────────────────────────────────────────────────
export default function MyOrders() {
  const { t, lang } = useI18n();

  // Pre-fill instantly from localStorage — zero wait time
  const [orders,   setOrders]   = useState(() => readMyOrderObjects());
  const [loading,  setLoading]  = useState(true);
  const [selected, setSelected] = useState(null);

  useEffect(() => {
    let cancelled = false;

    api.get('/customer/orders')
      .then((res) => {
        if (cancelled) return;
        const data = res.data?.data;
        const list = Array.isArray(data) ? data : [];

        if (list.length > 0) {
          // ✅ Session alive — use full server data (most up-to-date statuses)
          setOrders(list);
        }
        // If [] (no session) — localStorage objects already shown, nothing to override
      })
      .catch(() => {
        // Network error — localStorage objects already shown, keep them
      })
      .finally(() => { if (!cancelled) setLoading(false); });

    return () => { cancelled = true; };
  }, []);

  return (
    <div className="orders-page">
      <div className="section-header" style={{ padding: '16px 16px 8px' }}>
        <h2 style={{ fontSize: 18, fontWeight: 700 }}>{t('ordersPage')}</h2>
      </div>

      {loading ? (
        <div style={{ padding: '0 16px', display: 'flex', flexDirection: 'column', gap: 12 }}>
          {[1, 2, 3].map((i) => (
            <div key={i} className="skeleton" style={{ height: 88 }} />
          ))}
        </div>
      ) : orders.length === 0 ? (
        <p className="order-history-empty">{t('noOrdersYet')}</p>
      ) : (
        <ul className="order-history-list" style={{ padding: '0 16px 16px' }}>
          {orders.map((order) => (
            <li
              key={order.order_number}
              className="order-history-card order-history-card--clickable"
              onClick={() => setSelected(order)}
              role="button"
              tabIndex={0}
              onKeyDown={(e) => e.key === 'Enter' && setSelected(order)}
            >
              <div className="order-history-card-top">
                <span className="order-history-number">{order.order_number}</span>
                <span className={`order-status-badge status-${statusMod(order.status)}`}>
                  {t(`orderStatus_${order.status}`) || order.status}
                </span>
              </div>
              <div className="order-history-meta">
                {order.created_at
                  ? new Date(order.created_at).toLocaleString(
                      lang === 'ar' ? 'ar' : 'en-US',
                      { dateStyle: 'short', timeStyle: 'short' }
                    )
                  : ''}
              </div>
              <div className="order-history-total">
                <span style={{ color: 'var(--text-muted)', fontSize: 13 }}>
                  {t('orderTotal')}
                </span>
                <strong>{formatPrice(order.total)}</strong>
              </div>
              <div className="order-card-tap-hint">{t('orderItems')} ›</div>
            </li>
          ))}
        </ul>
      )}

      {selected && (
        <OrderDetailModal order={selected} onClose={() => setSelected(null)} />
      )}
    </div>
  );
}

