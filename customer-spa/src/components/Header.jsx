import { useState } from 'react';
import { BellIcon, CartIcon, ForkKnifeIcon } from './Icons';
import { createT } from '../i18n';

export default function Header({ settings, cartCount, onCartClick, isRtl, onLangToggle }) {
  const t = createT(isRtl);
  const name = settings?.restaurant_name || t('menu');
  const isOpen = !!settings?.is_open_now && settings?.is_accepting_orders !== false;

  return (
    <header className="header">
      <div className="header-inner">
        {/* Left: logo + name + status */}
        <div className="header-left">
          <div className="header-logo">
            {settings?.restaurant_logo
              ? <img src={settings.restaurant_logo} alt={name} onError={e => { e.target.style.display = 'none'; e.target.nextSibling.style.display = 'flex'; }} />
              : null}
            <div className="logo-fallback" style={settings?.restaurant_logo ? { display: 'none' } : {}}>
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

        {/* Right: actions */}
        <div className="header-right">
          <button className="lang-btn" onClick={onLangToggle} aria-label="Toggle language">
            {t('lang')}
          </button>
          <button className="icon-btn" aria-label={t('bell')}>
            <BellIcon />
          </button>
          {/* Desktop cart button */}
          <button
            className="cart-btn-header"
            onClick={onCartClick}
            aria-label={t('cart')}
          >
            <CartIcon size={18} />
            <span>{t('cart')}</span>
            {cartCount > 0 && (
              <span className="cart-badge-header">{cartCount}</span>
            )}
          </button>
        </div>
      </div>
    </header>
  );
}
