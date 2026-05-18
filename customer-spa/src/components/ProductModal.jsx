import { useState, useEffect } from 'react';
import { CloseIcon, PlusIcon, MinusIcon, ImageIcon } from './Icons';
import { formatPrice, localDesc, localName } from '../utils/format';
import { useI18n } from '../i18n';

export default function ProductModal({ product, onAdd, onClose }) {
  const { lang, t } = useI18n();
  const [qty, setQty] = useState(1);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const id = setTimeout(() => setVisible(true), 10);
    return () => clearTimeout(id);
  }, []);

  const close = () => {
    setVisible(false);
    setTimeout(onClose, 280);
  };

  const handleAdd = () => {
    const ok = onAdd(product, qty);
    if (ok !== false) close();
  };

  const p = product;
  const available = !!p.is_available;
  const displayName = localName(p, lang);
  const desc = localDesc(p, lang);

  return (
    <div
      className="modal-overlay"
      style={{ opacity: visible ? 1 : 0, transition: 'opacity 0.25s' }}
      onClick={(e) => {
        if (e.target === e.currentTarget) close();
      }}
    >
      <div
        className="product-sheet"
        style={{
          transform: visible ? 'translateY(0)' : 'translateY(100%)',
          transition: 'transform 0.3s cubic-bezier(0.4,0,0.2,1)',
        }}
      >
        <div className="product-sheet-img-wrap">
          {p.image ? (
            <img
              src={p.image}
              alt={displayName}
              onError={(e) => {
                e.target.style.display = 'none';
                e.target.nextSibling.style.display = 'flex';
              }}
            />
          ) : null}
          <div
            className="product-sheet-img-placeholder"
            style={p.image ? { display: 'none' } : {}}
          >
            <ImageIcon size={64} />
          </div>
          <button
            type="button"
            className="product-sheet-close"
            onClick={close}
            aria-label={t('close')}
          >
            <CloseIcon size={16} />
          </button>
        </div>

        <div className="product-sheet-body">
          <div className="product-sheet-title-row">
            <h2 className="product-sheet-title">{displayName}</h2>
            <div className="product-sheet-price-col">
              {p.discount_price ? (
                <>
                  <span className="product-sheet-old-price">
                    {formatPrice(p.price)}
                  </span>
                  <span className="product-sheet-price">
                    {formatPrice(p.effective_price)}
                  </span>
                  <span className="product-sheet-discount-badge">
                    -{Math.round(((p.price - p.discount_price) / p.price) * 100)}%
                  </span>
                </>
              ) : (
                <span className="product-sheet-price">
                  {formatPrice(p.effective_price)}
                </span>
              )}
            </div>
          </div>

          {desc ? <p className="product-sheet-desc">{desc}</p> : null}

          {!available && (
            <div className="product-sheet-unavail">{t('unavailable')}</div>
          )}
        </div>

        <div className="product-sheet-footer">
          <div className="qty-control">
            <button
              type="button"
              className="qty-btn"
              disabled={qty <= 1}
              onClick={() => setQty((q) => Math.max(1, q - 1))}
            >
              <MinusIcon size={16} />
            </button>
            <span className="qty-value">{qty}</span>
            <button
              type="button"
              className="qty-btn"
              onClick={() => setQty((q) => q + 1)}
            >
              <PlusIcon size={16} />
            </button>
          </div>
          <button
            type="button"
            className="btn-add-sheet"
            disabled={!available}
            onClick={handleAdd}
          >
            {t('addToCart')} · {formatPrice(p.effective_price * qty)}
          </button>
        </div>
      </div>
    </div>
  );
}
