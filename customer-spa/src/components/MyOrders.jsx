import { useState, useEffect } from 'react';
import api from '../api/client';
import { formatPrice } from '../utils/format';
import { readMyOrderObjects } from '../utils/myOrders';
import { featureEnabled } from '../utils/features';
import { useI18n } from '../i18n';
import { CloseIcon } from './Icons';

// ── Status badge helper ───────────────────────────────────────────────────────
function statusMod(status) {
  if (!status) return 'muted';
  if (['cancelled', 'cancelled_by_admin', 'cancelled_by_customer', 'rejected', 'modified'].includes(status))
    return 'danger';
  if (['completed', 'delivered'].includes(status)) return 'success';
  if (status === 'ready')    return 'ready';
  if (['accepted', 'preparing'].includes(status)) return 'progress';
  return 'pending';
}

/**
 * Returns true if the order is still within the cancellation window.
 * - Non-scheduled (immediate/table/takeaway): allowed whenever status = pending.
 * - Scheduled delivery: allowed only if now < scheduled_at - cancelBeforeMinutes.
 */
function canCancelOrder(order, cancelBeforeMinutes) {
  if (order.status !== 'pending') return false;
  if (
    order.order_type === 'delivery' &&
    order.delivery_type === 'scheduled' &&
    order.scheduled_at
  ) {
    const deadline = new Date(order.scheduled_at).getTime() - cancelBeforeMinutes * 60 * 1000;
    if (Date.now() >= deadline) return false;
  }
  return true;
}

// ── Order detail modal ────────────────────────────────────────────────────────
function OrderDetailModal({ order, onClose, onModify, onCancelled, cancelBeforeMinutes, settings = {} }) {
  const { t, lang } = useI18n();
  const [visible, setVisible] = useState(false);
  const [confirmCancel, setConfirmCancel] = useState(false);
  const [cancelling, setCancelling]       = useState(false);
  const [cancelError, setCancelError]     = useState('');

  useEffect(() => {
    const id = setTimeout(() => setVisible(true), 10);
    return () => clearTimeout(id);
  }, []);

  const close = () => {
    setVisible(false);
    setTimeout(onClose, 260);
  };

  // Gate on the order state AND the feature flag — when a feature is disabled
  // the backend route also 403s, so hiding the button keeps the UI honest.
  const canModify = order.status === 'pending' && featureEnabled(settings, 'orders.modification');
  const canCancel = canCancelOrder(order, cancelBeforeMinutes) && featureEnabled(settings, 'orders.customer_cancellation');
  const items = order.items ?? [];

  const handleCancel = async () => {
    setCancelling(true);
    setCancelError('');
    try {
      await api.post(`/orders/${order.order_number}/cancel`);
      // Notify parent to update order in list
      onCancelled(order.order_number);
      close();
    } catch (err) {
      const msg = err.response?.data?.message || t('cancelNotAllowed');
      setCancelError(msg);
    } finally {
      setCancelling(false);
      setConfirmCancel(false);
    }
  };

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
          {/* Status + Date */}
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
                {items.map((item, i) => {
                  const weightEnabled = featureEnabled(settings, 'products.weight_products');
                  const isWeighted    = !!item.weight_name && weightEnabled;
                  const kgUnit        = lang === 'ar' ? 'كغ' : 'kg';
                  const wKg        = item.weight_value_kg ? parseFloat(item.weight_value_kg) : null;
                  return (
                    <tr key={i}>
                      <td>
                        <div>{item.product_name}</div>
                        {isWeighted && item.price_per_kg ? (
                          <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 2 }}>
                            {formatPrice(item.price_per_kg)} / {kgUnit}
                          </div>
                        ) : null}
                        {item.option_name && (
                          <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 2 }}>
                            {item.option_name}
                          </div>
                        )}
                      </td>
                      <td style={{ textAlign: 'center' }}>
                        {isWeighted ? (
                          <span>
                            {item.weight_name}
                            {wKg !== null ? (
                              <span style={{ display: 'block', fontSize: 11, color: 'var(--text-muted)' }}>
                                ({wKg} {kgUnit})
                              </span>
                            ) : null}
                          </span>
                        ) : (
                          item.quantity
                        )}
                      </td>
                      <td style={{ textAlign: 'end', fontWeight: 600 }}>
                        {formatPrice(item.total)}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}

          {/* Cancel error */}
          {cancelError && (
            <p style={{ color: 'var(--danger, #dc2626)', fontSize: 13, marginTop: 12, textAlign: 'center' }}>
              {cancelError}
            </p>
          )}

          {/* Cancel confirmation prompt */}
          {confirmCancel && (
            <div style={{
              marginTop: 16,
              padding: '12px 16px',
              background: 'var(--danger-light, #fee2e2)',
              borderRadius: 10,
              textAlign: 'center',
            }}>
              <p style={{ margin: '0 0 12px', fontWeight: 600, color: 'var(--danger, #dc2626)', fontSize: 14 }}>
                {t('cancelOrderPrompt')}
              </p>
              <div style={{ display: 'flex', gap: 8, justifyContent: 'center' }}>
                <button
                  type="button"
                  className="btn-submit"
                  style={{ background: 'var(--danger, #dc2626)', flex: 1, marginTop: 0 }}
                  onClick={handleCancel}
                  disabled={cancelling}
                >
                  {cancelling ? t('cancelling') : t('cancelOrderConfirm')}
                </button>
                <button
                  type="button"
                  className="btn-submit"
                  style={{ background: 'var(--text-muted, #6b7280)', flex: 1, marginTop: 0 }}
                  onClick={() => setConfirmCancel(false)}
                  disabled={cancelling}
                >
                  {t('cancelOrderBack')}
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="checkout-footer">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: (canModify || canCancel) ? 12 : 0 }}>
            <span style={{ fontSize: 15, fontWeight: 600 }}>{t('orderTotal')}</span>
            <strong style={{ fontSize: 20, color: 'var(--primary)' }}>
              {formatPrice(order.total)}
            </strong>
          </div>

          {/* Modify button */}
          {canModify && (
            <button
              type="button"
              className="btn-submit"
              style={{ background: 'var(--warning, #f59e0b)', marginTop: 0, marginBottom: canCancel ? 8 : 0 }}
              onClick={() => { close(); onModify(order); }}
            >
              {t('modifyOrder')}
            </button>
          )}

          {/* Cancel button */}
          {canCancel && !confirmCancel && (
            <button
              type="button"
              className="btn-submit"
              style={{ background: 'transparent', color: 'var(--danger, #dc2626)', border: '1.5px solid var(--danger, #dc2626)', marginTop: 0 }}
              onClick={() => { setCancelError(''); setConfirmCancel(true); }}
            >
              {t('cancelOrder')}
            </button>
          )}

          {/* WhatsApp contact button */}
          {featureEnabled(settings, 'notifications.whatsapp_in_order_view') &&
           settings?.restaurant_whatsapp && (
            <a
              href={`https://wa.me/${String(settings.restaurant_whatsapp).replace(/\D/g, '')}?text=${encodeURIComponent(
                `${t('whatsappOrderMsg')} ${order.order_number}`
              )}`}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-submit"
              style={{
                background: '#25D366',
                color: '#fff',
                textDecoration: 'none',
                display: 'block',
                textAlign: 'center',
                marginTop: 8,
              }}
            >
              {t('contactWhatsapp')}
            </a>
          )}
        </div>
      </div>
    </div>
  );
}

// ── Main page ─────────────────────────────────────────────────────────────────
export default function MyOrders({ onModify, onMenuClick, settings = {} }) {
  const { t, lang } = useI18n();

  const cancelBeforeMinutes = Number(settings.customer_cancel_before_minutes) || 0;

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
        if (list.length > 0) setOrders(list);
      })
      .catch(() => {})
      .finally(() => { if (!cancelled) setLoading(false); });
    return () => { cancelled = true; };
  }, []);

  /** Called when cancel succeeds — update that order's status in local list */
  const handleCancelled = (orderNumber) => {
    setOrders((prev) =>
      prev.map((o) =>
        o.order_number === orderNumber
          ? { ...o, status: 'cancelled_by_customer' }
          : o
      )
    );
    // Also update selected so the badge refreshes if the modal is still animating out
    setSelected((prev) =>
      prev && prev.order_number === orderNumber
        ? { ...prev, status: 'cancelled_by_customer' }
        : prev
    );
  };

  return (
    <div className="orders-page">
      <div className="page-topbar">
        {onMenuClick && (
          <button type="button" className="back-to-menu-btn" onClick={onMenuClick}>
            <span className="back-arrow">→</span>
            {t('backToMenu')}
          </button>
        )}
        <h2 className="page-topbar-title">{t('ordersPage')}</h2>
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
                <span style={{ color: 'var(--text-muted)', fontSize: 13 }}>{t('orderTotal')}</span>
                <strong>{formatPrice(order.total)}</strong>
              </div>
              <div className="order-card-tap-hint">{t('orderItems')} ›</div>
            </li>
          ))}
        </ul>
      )}

      {selected && (
        <OrderDetailModal
          order={selected}
          onClose={() => setSelected(null)}
          onModify={onModify}
          onCancelled={handleCancelled}
          cancelBeforeMinutes={cancelBeforeMinutes}
          settings={settings}
        />
      )}
    </div>
  );
}
