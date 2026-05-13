import ProductCard from './ProductCard';
import { createT } from '../i18n';

export default function ProductList({ products, categories, onOpenModal, isRtl }) {
  const t = createT(isRtl);

  if (!products.length) {
    return <div className="empty-msg">{t('noProducts')}</div>;
  }

  // Group products by their category
  const categoryMap = new Map(categories.map(c => [c.id, c.name]));

  // Build ordered groups: maintain first-appearance order of categories
  const groups = [];
  const seen = new Map();
  for (const p of products) {
    const cid = p.category_id;
    if (!seen.has(cid)) {
      seen.set(cid, groups.length);
      groups.push({ cid, name: categoryMap.get(cid) || '', items: [] });
    }
    groups[seen.get(cid)].items.push(p);
  }

  // If only one group (or no category info), render flat
  if (groups.length === 1 && !groups[0].name) {
    return (
      <section className="products-section">
        <div className="products-container">
          {products.map(p => (
            <ProductCard key={p.id} product={p} onOpenModal={onOpenModal} isRtl={isRtl} />
          ))}
        </div>
      </section>
    );
  }

  return (
    <section className="products-section">
      {groups.map(({ cid, name, items }) => (
        <div key={cid ?? 'uncategorized'} className="category-group">
          {name && (
            <div className="section-header">
              <h2>{name}</h2>
            </div>
          )}
          <div className="products-container">
            {items.map(p => (
              <ProductCard key={p.id} product={p} onOpenModal={onOpenModal} isRtl={isRtl} />
            ))}
          </div>
        </div>
      ))}
    </section>
  );
}
