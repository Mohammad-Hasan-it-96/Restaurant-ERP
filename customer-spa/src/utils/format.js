/**
 * Formats a numeric amount for display using Western digits (en-US locale).
 * Safe for prices, quantities, and counts; non-numeric input falls back to "0".
 */
export const formatPrice = (num) => {
  const n = parseFloat(num);
  if (isNaN(n)) return '0';
  return n.toLocaleString('en-US');
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
