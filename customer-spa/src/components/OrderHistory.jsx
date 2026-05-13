import { useState, useEffect } from 'react';
import api, { extractData, decodeApiText } from '../api/client';
import { formatPrice } from '../utils/format';
import { readMyOrderNumbers } from '../utils/myOrders';
import { useI18n } from '../i18n';

/**
 * Maps API status to a CSS modifier class for the badge.
 */
function statusBadgeModifier(status) {
  if (!status) return 'muted';
  if (
    status === 'cancelled' ||
    status === 'cancelled_by_admin' ||
    status === 'cancelled_by_customer' ||
    status === 'rejected'
  ) {
    return 'danger';
  }
  if (status === 'completed' || status === 'delivered') return 'success';
  if (status === 'ready') return 'ready';
  if (status === 'accepted' || status === 'preparing') return 'progress';
  return 'pending';
}

export default function OrderHistory() {
  const { t, lang } = useI18n();
  const [rows, setRows] = useState(() =>
    readMyOrderNumbers().map((number) => ({
      number,
      loading: true,
      order: null,
      error: null,
    }))
  );

  useEffect(() => {
    let cancelled = false;
    const numbers = readMyOrderNumbers();
    if (!numbers.length) {
      setRows([]);
      return;
    }
    setRows(
      numbers.map((number) => ({
        number,
        loading: true,
        order: null,
        error: null,
      }))
    );

    (async () => {
      const results = await Promise.all(
        numbers.map(async (number) => {
          try {
            const res = await api.get(
              `/orders/${encodeURIComponent(number)}`
            );
            const order = extractData(res.data);
            return {
              number,
              loading: false,
              order,
              error: null,
            };
          } catch (err) {
            const raw =
              err.response?.data?.message || err.message || t('failedLoad');
            return {
              number,
              loading: false,
              order: null,
              error: decodeApiText(String(raw), t('failedLoad')),
            };
          }
        })
      );
      if (!cancelled) setRows(results);
    })();

    return () => {
      cancelled = true;
    };
  }, [t]);

  if (rows.length === 0) {
    return (
      <section
        className="order-history-section"
        aria-labelledby="order-history-heading"
      >
        <div className="section-header">
          <h2 id="order-history-heading">{t('myOrders')}</h2>
        </div>
        <p className="order-history-empty">{t('noOrdersYet')}</p>
      </section>
    );
  }

  return (
    <section
      className="order-history-section"
      aria-labelledby="order-history-heading"
    >
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
                <span className="order-status-badge status-muted">
                  {t('loading')}
                </span>
              ) : error ? (
                <span className="order-status-badge status-danger">
                  {t('orderLoadError')}
                </span>
              ) : order ? (
                <span
                  className={`order-status-badge status-${statusBadgeModifier(order.status)}`}
                >
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
