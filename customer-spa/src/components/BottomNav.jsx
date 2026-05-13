import { CartIcon, MenuIcon, PersonIcon } from './Icons';
import { formatPrice } from '../api/client';
import { createT } from '../i18n';

export default function BottomNav({ cartCount, cartTotal, onCartClick, isRtl }) {
  const t = createT(isRtl);
  return (
    <nav className="bottom-nav" aria-label="Main navigation">
      <button className="bottom-nav-item active">
        <MenuIcon size={22} />
        <span>{t('menu')}</span>
      </button>

      <button className="bottom-nav-cart" onClick={onCartClick} aria-label={t('cart')}>
        <CartIcon size={18} />
        <span>
          {cartCount > 0
            ? `${cartCount} · ${formatPrice(cartTotal, isRtl)}`
            : t('cart')}
        </span>
      </button>

      <button className="bottom-nav-item">
        <PersonIcon size={22} />
        <span>{t('profile')}</span>
      </button>
    </nav>
  );
}
