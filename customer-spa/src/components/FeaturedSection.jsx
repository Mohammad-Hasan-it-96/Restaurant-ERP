import { ImageIcon } from './Icons';
import { formatPrice, localName } from '../utils/format';
import { useI18n } from '../i18n';

export default function FeaturedSection({ products, onProductClick }) {
  const { t, lang } = useI18n();
  if (!products.length) return null;

  return (
    <section className="featured-section">
      <div className="section-header">
        <h2>{t('featured')}</h2>
      </div>
      <div className="featured-scroll">
        {products.map((p) => {
          const title = localName(p, lang);
          return (
            <button
            key={p.id}
            type="button"
            className="featured-card"
            onClick={() => onProductClick(p)}
          >
            {p.image ? (
              <img
                className="featured-card-img"
                src={p.image}
                alt={title}
                onError={(e) => {
                  e.target.style.display = 'none';
                  e.target.nextSibling.style.display = 'flex';
                }}
              />
            ) : null}
            <div
              className="featured-card-img-placeholder"
              style={p.image ? { display: 'none' } : {}}
            >
              <ImageIcon size={28} />
            </div>
            <div className="featured-card-body">
              <div className="featured-card-name">{title}</div>
              <div className="featured-card-price">
                {formatPrice(p.effective_price)}
              </div>
            </div>
          </button>
          );
        })}
      </div>
    </section>
  );
}
