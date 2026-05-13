import { useI18n } from '../i18n';
import { localName } from '../utils/format';

export default function CategoryChips({
  rootCategories,
  subCategories,
  selectedRoot,
  selectedSub,
  onRootSelect,
  onSubSelect,
}) {
  const { t, lang } = useI18n();

  return (
    <div className="chips-section">
      <div className="chips-scroll">
        {rootCategories.map((cat) => (
          <button
            key={cat.id}
            type="button"
            className={`chip${selectedRoot === cat.id ? ' active' : ''}`}
            onClick={() => onRootSelect(cat.id)}
          >
            {cat.image && (
              <img
                className="chip-thumb"
                src={cat.image}
                alt=""
                aria-hidden
                onError={(e) => e.target.remove()}
              />
            )}
            {localName(cat, lang)}
          </button>
        ))}
      </div>

      {subCategories.length > 0 && (
        <div className="chips-scroll">
          <button
            type="button"
            className={`chip sub-chip${selectedSub === null ? ' active' : ''}`}
            onClick={() => onSubSelect(null)}
          >
            {t('all')}
          </button>
          {subCategories.map((cat) => (
            <button
              key={cat.id}
              type="button"
              className={`chip sub-chip${selectedSub === cat.id ? ' active' : ''}`}
              onClick={() => onSubSelect(cat.id)}
            >
              {localName(cat, lang)}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
