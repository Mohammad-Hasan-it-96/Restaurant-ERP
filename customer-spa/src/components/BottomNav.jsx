import { CartIcon, MenuIcon, PersonIcon } from './Icons';
import { formatPrice } from '../utils/format';
import { useI18n } from '../i18n';

export default function BottomNav({
  activePage,
  onMenuClick,
  onProfileClick,
  cartCount,
  cartTotal,
  onCartClick,
}) {
  const { t } = useI18n();
  return (
    <nav className="bottom-nav" aria-label={t('mainNavAria')}>
      <button
        type="button"
        className={`bottom-nav-item${activePage === 'menu' ? ' active' : ''}`}
        onClick={onMenuClick}
      >
        <MenuIcon size={22} />
        <span>{t('menu')}</span>
      </button>

      <button
        type="button"
        className="bottom-nav-cart"
        onClick={onCartClick}
        aria-label={t('cart')}
      >
        <CartIcon size={18} />
        <span>
          {cartCount > 0
            ? `${cartCount} · ${formatPrice(cartTotal)}`
            : t('cart')}
        </span>
      </button>

      <button
        type="button"
        className={`bottom-nav-item${activePage === 'profile' ? ' active' : ''}`}
        onClick={onProfileClick}
      >
        <PersonIcon size={22} />
        <span>{t('profile')}</span>
      </button>
    </nav>
  );
}
