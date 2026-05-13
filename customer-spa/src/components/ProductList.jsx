import ProductCard from './ProductCard';
import { useI18n } from '../i18n';
import { localName } from '../utils/format';

export default function ProductList({
  products,
  categories,
  onOpenModal,
  onQuickAdd,
}) {
  const { t, lang } = useI18n();

  if (!products.length) {
    return <div className="empty-msg">{t('noProducts')}</div>;
  }

  const categoryMap = new Map(
    categories.map((c) => [c.id, localName(c, lang)])
  );

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

  if (groups.length === 1 && !groups[0].name) {
    return (
      <section className="products-section">
        <div className="products-container">
          {products.map((p) => (
            <ProductCard
              key={p.id}
              product={p}
              onOpenModal={onOpenModal}
              onQuickAdd={onQuickAdd}
            />
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
            {items.map((p) => (
              <ProductCard
                key={p.id}
                product={p}
                onOpenModal={onOpenModal}
                onQuickAdd={onQuickAdd}
              />
            ))}
          </div>
        </div>
      ))}
    </section>
  );
}
