import { useState, useMemo, useCallback, useEffect } from 'react';
import useRestaurantData from './hooks/useRestaurantData';
import useCart from './hooks/useCart';
import { useI18n } from './i18n';
import { localDesc, localName } from './utils/format';
import Header from './components/Header';
import HeroStrip from './components/HeroStrip';
import SearchBar from './components/SearchBar';
import CategoryChips from './components/CategoryChips';
import FeaturedSection from './components/FeaturedSection';
import ProductList from './components/ProductList';
import ProductModal from './components/ProductModal';
import CartDrawer from './components/CartDrawer';
import CheckoutModal from './components/CheckoutModal';
import OrderSuccess from './components/OrderSuccess';
import MyProfile from './components/MyProfile';
import BottomNav from './components/BottomNav';

export default function App() {
  const { t, lang } = useI18n();
  const [selectedRoot, setSelectedRoot] = useState(null);
  const [selectedSub, setSelectedSub] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeProduct, setActiveProduct] = useState(null);
  const [showCart, setShowCart] = useState(false);
  const [showCheckout, setShowCheckout] = useState(false);
  const [lastOrder, setLastOrder] = useState(null);
  const [activePage, setActivePage] = useState('menu');

  const { settings, categories, allProducts, zones, loading, error, retry } =
    useRestaurantData();
  const cart = useCart();

  useEffect(() => {
    document.title = t('documentTitle');
  }, [t, lang]);

  // Auto-select first root category
  useEffect(() => {
    if (selectedRoot) return;
    const roots = categories.filter((c) => c.parent_id === null);
    if (roots.length) setSelectedRoot(roots[0].id);
  }, [categories, selectedRoot]);

  const rootCategories = useMemo(
    () => categories.filter((c) => c.parent_id === null),
    [categories]
  );
  const subCategories = useMemo(
    () =>
      selectedRoot
        ? categories.filter((c) => c.parent_id === selectedRoot)
        : [],
    [categories, selectedRoot]
  );

  const products = useMemo(() => {
    let list = allProducts;
    if (selectedSub !== null && selectedSub !== undefined) {
      list = list.filter((p) => p.category_id === selectedSub);
    } else if (selectedRoot) {
      const childIds = new Set([
        selectedRoot,
        ...subCategories.map((c) => c.id),
      ]);
      list = list.filter((p) => childIds.has(p.category_id));
    }
    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      list = list.filter((p) => {
        const blob = [
          localName(p, lang),
          localDesc(p, lang),
          p.name,
          p.name_ar,
          p.name_en,
          p.description_ar,
          p.description_en,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();
        return blob.includes(q);
      });
    }
    return list;
  }, [allProducts, selectedRoot, selectedSub, subCategories, searchQuery, lang]);

  const handleRootSelect = useCallback((id) => {
    setSelectedRoot(id);
    setSelectedSub(null);
  }, []);

  const handleAddToCart = useCallback(
    (product, qty = 1) => {
      const ok = cart.addToCart(product, qty);
      if (!ok) window.alert(t('productUnavailableAlert'));
      return ok;
    },
    [cart, t]
  );

  const handleQuickAdd = useCallback(
    (product) => {
      const ok = cart.addToCart(product, 1);
      if (!ok) window.alert(t('productUnavailableAlert'));
    },
    [cart, t]
  );

  const handleCheckoutSuccess = useCallback(
    (orderNumber) => {
      cart.clearCart();
      setShowCheckout(false);
      setShowCart(false);
      setLastOrder(orderNumber);
    },
    [cart]
  );

  return (
    <div className="app">
      <Header
        settings={settings}
        cartCount={cart.cartCount}
        onCartClick={() => setShowCart(true)}
      />

      <main className="main-content">
        <HeroStrip settings={settings} />

        {activePage === 'menu' ? (
          <>
            <SearchBar value={searchQuery} onChange={setSearchQuery} />

            <CategoryChips
              rootCategories={rootCategories}
              subCategories={subCategories}
              selectedRoot={selectedRoot}
              selectedSub={selectedSub}
              onRootSelect={handleRootSelect}
              onSubSelect={setSelectedSub}
            />

            {error && (
              <div className="error-banner">
                <span>{t(error)}</span>
                <button type="button" className="retry-btn" onClick={retry}>
                  {t('retry')}
                </button>
              </div>
            )}

            {loading ? (
              <div className="products-section">
                <div className="products-container">
                  {Array.from({ length: 6 }).map((_, i) => (
                    <div key={i} className="skeleton skeleton-card" />
                  ))}
                </div>
              </div>
            ) : (
              <>
                <FeaturedSection
                  products={allProducts
                    .filter((p) => !!p.is_featured)
                    .slice(0, 8)}
                  onProductClick={setActiveProduct}
                />
                <ProductList
                  products={products}
                  categories={categories}
                  onOpenModal={setActiveProduct}
                  onQuickAdd={handleQuickAdd}
                />
              </>
            )}
          </>
        ) : activePage === 'profile' ? (
          <MyProfile />
        ) : null}
      </main>

      <BottomNav
        activePage={activePage}
        onMenuClick={() => setActivePage('menu')}
        onProfileClick={() => setActivePage('profile')}
        cartCount={cart.cartCount}
        cartTotal={cart.cartTotal}
        onCartClick={() => setShowCart(true)}
      />

      {activeProduct && (
        <ProductModal
          product={activeProduct}
          onAdd={handleAddToCart}
          onClose={() => setActiveProduct(null)}
        />
      )}

      {showCart && (
        <CartDrawer
          items={cart.cartItems}
          onClose={() => setShowCart(false)}
          onChangeQty={cart.changeQty}
          onRemove={cart.removeFromCart}
          onClear={cart.clearCart}
          onCheckout={() => setShowCheckout(true)}
        />
      )}

      {showCheckout && (
        <CheckoutModal
          zones={zones}
          cartItems={cart.cartItems}
          cartTotal={cart.cartTotal}
          onSuccess={handleCheckoutSuccess}
          onClose={() => setShowCheckout(false)}
        />
      )}

      {lastOrder && (
        <OrderSuccess
          orderNumber={lastOrder}
          onClose={() => setLastOrder(null)}
        />
      )}
    </div>
  );
}
