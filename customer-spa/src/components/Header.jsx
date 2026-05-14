import { BellIcon, CartIcon, ForkKnifeIcon } from './Icons';
import { formatPrice } from '../utils/format';
import { switchLanguage, useI18n } from '../i18n';

export default function Header({ settings, cartCount, onCartClick }) {
  const { lang, t } = useI18n();

  // Pick locale-aware restaurant name:
  // English → prefer restaurant_name_en, fall back to Arabic name
  // Arabic  → prefer restaurant_name (Arabic), fall back to English name
  const nameAr = settings?.restaurant_name    || '';
  const nameEn = settings?.restaurant_name_en || '';
  const name = lang === 'en'
    ? (nameEn || nameAr || t('menu'))
    : (nameAr || nameEn || t('menu'));
  const isOpen =
    !!settings?.is_open_now && settings?.is_accepting_orders !== false;

  const handleLangClick = () => {
    switchLanguage(lang === 'ar' ? 'en' : 'ar');
  };

  return (
    <header className="header">
      <div className="header-inner">
        <div className="header-left">
          <div className="header-logo">
            {settings?.restaurant_logo ? (
              <img
                src={settings.restaurant_logo}
                alt={name}
                onError={(e) => {
                  e.target.style.display = 'none';
                  e.target.nextSibling.style.display = 'flex';
                }}
              />
            ) : null}
            <div
              className="logo-fallback"
              style={settings?.restaurant_logo ? { display: 'none' } : {}}
            >
              <ForkKnifeIcon />
            </div>
          </div>
          <div className="header-title-wrap">
            <span className="header-name">{name}</span>
            <span className={`header-status ${isOpen ? 'open' : 'closed'}`}>
              <span className="status-dot" />
              {isOpen ? t('openNow') : t('closedNow')}
            </span>
          </div>
        </div>

        <div className="header-right">
          <button
            type="button"
            className="lang-btn"
            onClick={handleLangClick}
            aria-label={t('toggleLanguageAria')}
          >
            {t('lang')}
          </button>
          <button
            type="button"
            className="icon-btn"
            aria-label={t('bellAria')}
          >
            <BellIcon />
          </button>
          <button
            type="button"
            className="cart-btn-header"
            onClick={onCartClick}
            aria-label={t('cart')}
          >
            <CartIcon size={18} />
            <span>{t('cart')}</span>
            {cartCount > 0 && (
              <span className="cart-badge-header">
                {formatPrice(cartCount)}
              </span>
            )}
          </button>
        </div>
      </div>
    </header>
  );
}
