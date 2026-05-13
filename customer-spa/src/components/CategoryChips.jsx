import { createT } from '../i18n';

export default function CategoryChips({

  rootCategories, subCategories, selectedRoot, selectedSub,

  onRootSelect, onSubSelect, isRtl,

}) {

  const t = createT(isRtl);

  return (

    <div className="chips-section">

      {/* Root category row */}
      <div className="chips-scroll">
        {rootCategories.map(cat => (
          <button
            key={cat.id}
            className={`chip${selectedRoot === cat.id ? ' active' : ''}`}
            onClick={() => onRootSelect(cat.id)}
          >
            {cat.image && (
              <img className="chip-thumb" src={cat.image} alt=""
                   onError={e => e.target.remove()} />
            )}
            {cat.name}
          </button>
        ))}
      </div>

      {/* Sub-category row (shown when root has subs) */}
      {subCategories.length > 0 && (
        <div className="chips-scroll">
          <button
            className={`chip sub-chip${selectedSub === null ? ' active' : ''}`}
            onClick={() => onSubSelect(null)}
          >
            {t('all')}
          </button>
          {subCategories.map(cat => (
            <button
              key={cat.id}
              className={`chip sub-chip${selectedSub === cat.id ? ' active' : ''}`}
              onClick={() => onSubSelect(cat.id)}
            >
              {cat.name}
            </button>
          ))}
        </div>
      )}
    </div>

  );

}
