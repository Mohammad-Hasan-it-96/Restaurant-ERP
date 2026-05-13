import { useRef, useCallback } from 'react';
import { SearchIcon } from './Icons';
import { createT } from '../i18n';

export default function SearchBar({ value, onChange, isRtl }) {
  const t = createT(isRtl);
  const timerRef = useRef(null);

  const handleChange = useCallback(
    (e) => {
      const val = e.target.value;
      clearTimeout(timerRef.current);
      timerRef.current = setTimeout(() => onChange(val), 300);
    },
    [onChange]
  );

  return (
    <div className="search-bar">
      <div className="search-input-wrap">
        <span className="search-icon-wrap">
          <SearchIcon />
        </span>
        <input
          className="search-input"
          type="search"
          defaultValue={value}
          placeholder={t('search')}
          onChange={handleChange}
          autoComplete="off"
        />
      </div>
    </div>
  );
}
