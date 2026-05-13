import { ImageIcon, PlusIcon } from './Icons';
import { formatPrice } from '../api/client';
import { createT } from '../i18n';

export default function ProductCard({ product, onOpenModal, isRtl }) {
  const t = createT(isRtl);
  const p = product;
  const available = !!p.is_available;

  const desc = isRtl
    ? (p.description_ar || p.description_en || '')
    : (p.description_en || p.description_ar || '');

  return (
    <article
      className={`product-card${available ? '' : ' unavailable'}`}
      onClick={() => onOpenModal(p)}
      role="button"
      tabIndex={0}
      onKeyDown={e => e.key === 'Enter' && onOpenModal(p)}
    >
      {/* Thumbnail */}
      {p.image ? (
        <img
          className="product-thumb"
          src={p.image}
          alt={p.name}
          loading="lazy"
          onError={e => { e.target.style.display = 'none'; e.target.nextSibling.style.display = 'flex'; }}
        />
      ) : null}
      <div
        className="product-thumb-placeholder"
        style={p.image ? { display: 'none' } : {}}
      >
        <ImageIcon size={28} />
      </div>

      {/* Info */}
      <div className="product-info">
        <div className="product-name">{p.name}</div>
        {desc ? <div className="product-desc">{desc}</div> : null}
        <div className="product-price-row">
          <span className="product-price">{formatPrice(p.effective_price, isRtl)}</span>
          {p.discount_price ? (
            <span className="product-price-old">{formatPrice(p.price, isRtl)}</span>
          ) : null}
          {!available && (
            <span className="unavailable-badge">{t('unavailable')}</span>
          )}
        </div>
      </div>

      {/* Circle add button */}
      <button
        className="product-add-btn"
        disabled={!available}
        onClick={e => { e.stopPropagation(); onOpenModal(p); }}
        aria-label={t('addToCart')}
      >
        <PlusIcon size={16} />
      </button>
    </article>
  );
}
