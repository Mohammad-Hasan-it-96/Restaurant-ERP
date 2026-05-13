import { ImageIcon } from './Icons';
import { formatPrice } from '../api/client';
import { createT } from '../i18n';

export default function FeaturedSection({ products, onProductClick, isRtl }) {
  const t = createT(isRtl);
  if (!products.length) return null;

  return (
    <section className="featured-section">
      <div className="section-header">
        <h2>{t('featured')}</h2>
      </div>
      <div className="featured-scroll">
        {products.map(p => (
          <button
            key={p.id}
            className="featured-card"
            onClick={() => onProductClick(p)}
          >
            {p.image
              ? <img className="featured-card-img" src={p.image} alt={p.name}
                     onError={e => { e.target.style.display='none'; e.target.nextSibling.style.display='flex'; }} />
              : null}
            <div className="featured-card-img-placeholder"
                 style={p.image ? { display: 'none' } : {}}>
              <ImageIcon size={28} />
            </div>
            <div className="featured-card-body">
              <div className="featured-card-name">{p.name}</div>
              <div className="featured-card-price">
                {formatPrice(p.effective_price, isRtl)}
              </div>
            </div>
          </button>
        ))}
      </div>
    </section>
  );
}
