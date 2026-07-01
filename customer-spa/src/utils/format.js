/**
 * Active currency, set once from the public settings payload
 * (GET /api/v1/settings/public → "currency"). Falls back to a bare number
 * until settings load. See setCurrency() below.
 */
let _currency = { symbol: '', position: 'suffix', decimals: 0 };

/**
 * Configure the currency used by formatPrice. Call once when settings arrive.
 */
export const setCurrency = (currency) => {
  if (currency && typeof currency === 'object') {
    const decimals = parseInt(currency.decimals, 10);
    _currency = {
      symbol: currency.symbol || '',
      position: currency.position === 'prefix' ? 'prefix' : 'suffix',
      decimals: Number.isFinite(decimals) && decimals >= 0 ? decimals : 0,
    };
  }
};

/**
 * Formats a plain number (counts, quantities) using Western digits — no currency.
 */
export const formatNumber = (num) => {
  const n = parseFloat(num);
  return isNaN(n) ? '0' : n.toLocaleString('en-US');
};

/**
 * Formats a monetary amount with the configured currency (symbol, position,
 * decimals). Western digits; non-numeric input falls back to 0.
 */
export const formatPrice = (num) => {
  const n = parseFloat(num);
  const safe = isNaN(n) ? 0 : n;
  const number = safe.toLocaleString('en-US', {
    minimumFractionDigits: _currency.decimals,
    maximumFractionDigits: _currency.decimals,
  });

  if (!_currency.symbol) return number;
  return _currency.position === 'prefix'
    ? `${_currency.symbol}${number}`
    : `${number} ${_currency.symbol}`;
};

/**
 * Picks localized display name from API bilingual fields.
 */
export const localName = (item, lang) =>
  lang === 'ar'
    ? item.name_ar || item.name_en
    : item.name_en || item.name_ar;

/**
 * Picks localized description from API bilingual fields.
 */
export const localDesc = (item, lang) =>
  lang === 'ar'
    ? item.description_ar || item.description_en
    : item.description_en || item.description_ar;
