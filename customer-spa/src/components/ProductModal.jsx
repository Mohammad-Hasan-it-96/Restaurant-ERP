import { useState, useEffect } from 'react';
import { CloseIcon, PlusIcon, MinusIcon, ImageIcon, StarIcon } from './Icons';
import { formatPrice } from '../api/client';
import { createT } from '../i18n';

export default function ProductModal({ product, onAdd, onClose, isRtl }) {
  const t = createT(isRtl);
  const [qty, setQty] = useState(1);
  const [visible, setVisible] = useState(false);

  // Trigger enter animation
  useEffect(() => { const id = setTimeout(() => setVisible(true), 10); return () => clearTimeout(id); }, []);

  const close = () => {
    setVisible(false);
    setTimeout(onClose, 280);
  };

  const handleAdd = () => {
    onAdd(product, qty);
    close();
  };

  const p = product;
  const available = !!p.is_available;
  const desc = isRtl
    ? (p.description_ar || p.description_en || '')
    : (p.description_en || p.description_ar || '');

  return (
    <div
      className="modal-overlay"
      style={{ opacity: visible ? 1 : 0, transition: 'opacity 0.25s' }}
      onClick={e => { if (e.target === e.currentTarget) close(); }}
    >
      <div
        className="product-sheet"
        style={{
          transform: visible ? 'translateY(0)' : 'translateY(100%)',
          transition: 'transform 0.3s cubic-bezier(0.4,0,0.2,1)',
        }}
      >
        {/* Image */}
        <div className="product-sheet-img-wrap">
          {p.image ? (
            <img src={p.image} alt={p.name}
                 onError={e => { e.target.style.display='none'; e.target.nextSibling.style.display='flex'; }} />
          ) : null}
          <div className="product-sheet-img-placeholder"
               style={p.image ? { display: 'none' } : {}}>
            <ImageIcon size={64} />
          </div>
          <button className="product-sheet-close" onClick={close} aria-label={t('close')}>
            <CloseIcon size={16} />
          </button>
        </div>

        {/* Body */}
        <div className="product-sheet-body">
          <div className="product-sheet-title-row">
            <h2 className="product-sheet-title">{p.name}</h2>
            <div className="product-sheet-price-col">
              {p.discount_price && (
                <span className="product-sheet-old-price">{formatPrice(p.price, isRtl)}</span>
              )}
              <span className="product-sheet-price">{formatPrice(p.effective_price, isRtl)}</span>
            </div>
          </div>

          <div className="product-sheet-meta">
            <StarIcon size={13} /> 4.9
            <span style={{ marginInlineStart: 6 }}>{t('reviews')}</span>
          </div>

          {desc && <p className="product-sheet-desc">{desc}</p>}

          {!available && (
            <div className="product-sheet-unavail">{t('unavailable')}</div>
          )}
        </div>

        {/* Footer */}
        <div className="product-sheet-footer">
          <div className="qty-control">
            <button className="qty-btn" disabled={qty <= 1}
                    onClick={() => setQty(q => Math.max(1, q - 1))}>
              <MinusIcon size={16} />
            </button>
            <span className="qty-value">{qty}</span>
            <button className="qty-btn" onClick={() => setQty(q => q + 1)}>
              <PlusIcon size={16} />
            </button>
          </div>
          <button
            className="btn-add-sheet"
            disabled={!available}
            onClick={handleAdd}
          >
            {t('addToCart')} · {formatPrice(p.effective_price * qty, isRtl)}
          </button>
        </div>
      </div>
    </div>
  );
}
