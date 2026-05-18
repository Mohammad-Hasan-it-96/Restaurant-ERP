import { useState, useEffect } from 'react';
import api, { extractData, decodeApiText } from '../api/client';
import { formatPrice } from '../utils/format';
import { useI18n } from '../i18n';

function statusBadgeModifier(status) {
  if (!status) return 'muted';
  if (
    status === 'cancelled' ||
    status === 'cancelled_by_admin' ||
    status === 'cancelled_by_customer' ||
    status === 'rejected'
  ) return 'danger';
  if (status === 'completed' || status === 'delivered') return 'success';
  if (status === 'ready') return 'ready';
  if (status === 'accepted' || status === 'preparing') return 'progress';
  return 'pending';
}

/**
 * @param {{ orders?: Array|null }} props
 *   orders – pre-fetched list from /me API (optional).
 *            If provided, rendered directly without further API calls.
 *            If null/undefined, calls GET /customer/orders automatically.
 */
export default function OrderHistory({ orders: prefetchedOrders = null }) {
  const { t, lang } = useI18n();
  const [rows, setRows] = useState([]);
  const [fetching, setFetching] = useState(false);

  useEffect(() => {
    // If caller passed orders from /me, use them directly
    if (prefetchedOrders !== null) {
      setRows(
        prefetchedOrders.map((o) => ({
          number: o.order_number,
          loading: false,
          order: o,
          error: null,
        }))
      );
      return;
    }

    // Otherwise fetch from /customer/orders
    let cancelled = false;
    setFetching(true);

    api.get('/customer/orders')
      .then((res) => {
        if (cancelled) return;
        const data = res.data?.data;
        const list = Array.isArray(data) ? data : [];
        setRows(
          list.map((o) => ({
            number: o.order_number,
            loading: false,
            order: o,
            error: null,
          }))
        );
      })
      .catch(() => {
        if (!cancelled) setRows([]);
      })
      .finally(() => { if (!cancelled) setFetching(false); });

    return () => { cancelled = true; };
  }, [prefetchedOrders]);

  if (fetching) {
    return (
      <section className="order-history-section">
        <div className="section-header">
          <h2>{t('myOrders')}</h2>
        </div>
        <div className="skeleton" style={{ height: 80, marginBottom: 10 }} />
        <div className="skeleton" style={{ height: 80 }} />
      </section>
    );
  }

  if (rows.length === 0) {
    return (
      <section className="order-history-section" aria-labelledby="order-history-heading">
        <div className="section-header">
          <h2 id="order-history-heading">{t('myOrders')}</h2>
        </div>
        <p className="order-history-empty">{t('noOrdersYet')}</p>
      </section>
    );
  }

  return (
    <section className="order-history-section" aria-labelledby="order-history-heading">
      <div className="section-header">
        <h2 id="order-history-heading">{t('myOrders')}</h2>
      </div>
      <ul className="order-history-list">
        {rows.map(({ number, loading, order, error }) => (
          <li key={number} className="order-history-card">
            <div className="order-history-card-top">
              <span className="order-history-number">
                {t('orderNumberDisplay')}: {number}
              </span>
              {loading ? (
                <span className="order-status-badge status-muted">{t('loading')}</span>
              ) : error ? (
                <span className="order-status-badge status-danger">{t('orderLoadError')}</span>
              ) : order ? (
                <span className={`order-status-badge status-${statusBadgeModifier(order.status)}`}>
                  {t(`orderStatus_${order.status}`) || order.status}
                </span>
              ) : null}
            </div>
            {error ? (
              <p className="order-history-error">{error}</p>
            ) : order ? (
              <>
                <div className="order-history-meta">
                  {order.created_at
                    ? new Date(order.created_at).toLocaleString(
                        lang === 'ar' ? 'ar' : 'en-US',
                        { dateStyle: 'short', timeStyle: 'short' }
                      )
                    : ''}
                </div>
                <div className="order-history-total">
                  <span>{t('orderTotal')}</span>
                  <strong>{formatPrice(order.total)}</strong>
                </div>
              </>
            ) : null}
          </li>
        ))}
      </ul>
    </section>
  );
}
