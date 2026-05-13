import { ImageIcon, PlusIcon } from './Icons';
import { formatPrice, localDesc, localName } from '../utils/format';
import { useI18n } from '../i18n';

export default function ProductCard({ product, onOpenModal, onQuickAdd }) {
  const { lang, t } = useI18n();
  const p = product;
  const available = !!p.is_available;
  const displayName = localName(p, lang);
  const desc = localDesc(p, lang);

  return (
    <article
      className={`product-card${available ? '' : ' unavailable'}`}
      onClick={() => onOpenModal(p)}
      role="button"
      tabIndex={0}
      onKeyDown={(e) => e.key === 'Enter' && onOpenModal(p)}
    >
      {p.image ? (
        <img
          className="product-thumb"
          src={p.image}
          alt={displayName}
          loading="lazy"
          onError={(e) => {
            e.target.style.display = 'none';
            e.target.nextSibling.style.display = 'flex';
          }}
        />
      ) : null}
      <div
        className="product-thumb-placeholder"
        style={p.image ? { display: 'none' } : {}}
      >
        <ImageIcon size={28} />
      </div>

      <div className="product-info">
        <div className="product-name">{displayName}</div>
        {desc ? <div className="product-desc">{desc}</div> : null}
        <div className="product-price-row">
          <span className="product-price">{formatPrice(p.effective_price)}</span>
          {p.discount_price ? (
            <span className="product-price-old">{formatPrice(p.price)}</span>
          ) : null}
          {!available && (
            <span className="unavailable-badge">{t('unavailable')}</span>
          )}
        </div>
      </div>

      <button
        type="button"
        className="product-add-btn"
        disabled={!available}
        onClick={(e) => {
          e.stopPropagation();
          if (!available) return;
          if (onQuickAdd) onQuickAdd(p);
          else onOpenModal(p);
        }}
        aria-label={t('addToCart')}
      >
        <PlusIcon size={16} />
      </button>
    </article>
  );
}
