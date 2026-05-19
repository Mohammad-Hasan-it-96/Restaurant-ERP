import { useEffect, useState } from 'react';
import { CloseIcon, TrashIcon, PlusIcon, MinusIcon, ImageIcon } from './Icons';
import { formatPrice, localName } from '../utils/format';
import { useI18n } from '../i18n';

export default function CartDrawer({
  items,
  onClose,
  onChangeQty,
  onRemove,
  onClear,
  onCheckout,
  modifyingOrder,
}) {
  const { t, dir, lang } = useI18n();
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const id = setTimeout(() => setOpen(true), 10);
    return () => clearTimeout(id);
  }, []);

  const close = () => {
    setOpen(false);
    setTimeout(onClose, 300);
  };

  const subtotal = items.reduce(
    (s, i) => s + (parseFloat(i.product_price) || 0) * i.quantity,
    0
  );
  const slideStyle = {
    transition: 'transform 0.3s cubic-bezier(0.4,0,0.2,1)',
    transform: open
      ? 'translateX(0)'
      : dir === 'rtl'
        ? 'translateX(-100%)'
        : 'translateX(100%)',
  };

  return (
    <>
      <div
        className="cart-overlay"
        style={{ opacity: open ? 1 : 0, transition: 'opacity 0.3s' }}
        onClick={close}
      />
      <div className="cart-drawer" style={slideStyle}>
        <div className="cart-header">
          <div>
            <h2 className="cart-title">{t('yourOrder')}</h2>
            {modifyingOrder && (
              <span style={{
                display: 'inline-block', fontSize: 11, fontWeight: 700,
                background: '#f59e0b', color: '#fff', borderRadius: 4,
                padding: '1px 7px', marginTop: 2,
              }}>
                {t('modifyOrder')} · {modifyingOrder.order_number}
              </span>
            )}
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            {items.length > 0 && (
              <button
                type="button"
                className="icon-btn"
                onClick={onClear}
                title={t('clearCart')}
              >
                <TrashIcon size={18} />
              </button>
            )}
            <button
              type="button"
              className="icon-btn"
              onClick={close}
              aria-label={t('close')}
            >
              <CloseIcon size={20} />
            </button>
          </div>
        </div>

        <div className="cart-body">
          {items.length === 0 ? (
            <div className="cart-empty">
              <span className="cart-empty-icon" aria-hidden>
                🛒
              </span>
              <p className="cart-empty-text">{t('emptyCart')}</p>
              <p
                style={{
                  fontSize: 13,
                  marginTop: 6,
                  color: 'var(--text-muted)',
                }}
              >
                {t('emptyCartHint')}
              </p>
            </div>
          ) : (
            items.map((item) => {
              const lineName = localName(
                {
                  name_ar: item.product_name_ar,
                  name_en: item.product_name_en,
                },
                lang
              );
              return (
                <div key={item.product_id} className="cart-item">
                {item.product_image ? (
                  <img
                    className="cart-item-img"
                    src={item.product_image}
                    alt={lineName}
                    onError={(e) => {
                      e.target.style.display = 'none';
                      e.target.nextSibling.style.display = 'flex';
                    }}
                  />
                ) : null}
                <div
                  className="cart-item-img-placeholder"
                  style={item.product_image ? { display: 'none' } : {}}
                >
                  <ImageIcon size={20} />
                </div>

                <div className="cart-item-info">
                  <div className="cart-item-name">{lineName}</div>
                  <div className="cart-item-price">
                    {formatPrice(item.product_price)} {t('times')} {item.quantity}
                  </div>
                </div>

                <div className="cart-item-right">
                  <span className="cart-item-total">
                    {formatPrice(
                      (parseFloat(item.product_price) || 0) * item.quantity
                    )}
                  </span>
                  <div className="cart-item-controls">
                    <button
                      type="button"
                      className="cart-qty-btn"
                      onClick={() => onChangeQty(item.product_id, -1)}
                    >
                      <MinusIcon size={12} />
                    </button>
                    <span className="cart-qty-val">{item.quantity}</span>
                    <button
                      type="button"
                      className="cart-qty-btn"
                      onClick={() => onChangeQty(item.product_id, 1)}
                    >
                      <PlusIcon size={12} />
                    </button>
                  </div>
                  <button
                    type="button"
                    className="cart-item-delete"
                    onClick={() => onRemove(item.product_id)}
                    aria-label={t('remove')}
                  >
                    <TrashIcon size={14} />
                  </button>
                </div>
                </div>
              );
            })
          )}
        </div>

        <div className="cart-footer">
          <div className="cart-subtotal-row">
            <span>{t('subtotal')}</span>
            <strong className="cart-subtotal-amount">
              {formatPrice(subtotal)}
            </strong>
          </div>
          <button
            type="button"
            className="btn-checkout"
            disabled={items.length === 0}
            onClick={() => {
              onClose();
              setTimeout(onCheckout, 100);
            }}
          >
            {t('proceedCheckout')}
          </button>
        </div>
      </div>
    </>
  );
}
