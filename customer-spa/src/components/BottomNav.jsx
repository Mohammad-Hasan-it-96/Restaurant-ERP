import { CartIcon, PersonIcon, ClipboardIcon, MenuIcon } from './Icons';
import { formatPrice } from '../utils/format';
import { useI18n } from '../i18n';

export default function BottomNav({
  activePage,
  onMenuClick,
  onOrdersClick,
  onProfileClick,
  cartCount,
  cartTotal,
  onCartClick,
}) {
  const { t } = useI18n();
  return (
    <nav className="bottom-nav" aria-label={t('mainNavAria')}>
      {/* طلباتي */}
      <button
        type="button"
        className={`bottom-nav-item${activePage === 'orders' ? ' active' : ''}`}
        onClick={onOrdersClick}
      >
        <ClipboardIcon size={22} />
        <span>{t('ordersPage')}</span>
      </button>

      {/* السلة — وسط */}
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

      {/* حسابي */}
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
