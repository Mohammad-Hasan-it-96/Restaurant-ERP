import { useState, useMemo, useCallback, useEffect } from 'react';
import useRestaurantData from './hooks/useRestaurantData';
import useCart from './hooks/useCart';
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
import BottomNav from './components/BottomNav';

export default function App() {
  const [isRtl, setIsRtl] = useState(
    () => document.documentElement.dir === 'rtl'
  );
  const [selectedRoot, setSelectedRoot] = useState(null);
  const [selectedSub,  setSelectedSub]  = useState(null);
  const [searchQuery,  setSearchQuery]  = useState('');
  const [activeProduct, setActiveProduct] = useState(null);
  const [showCart,     setShowCart]     = useState(false);
  const [showCheckout, setShowCheckout] = useState(false);
  const [lastOrder,    setLastOrder]    = useState(null);

  const { settings, categories, allProducts, zones, loading, error, retry } =
    useRestaurantData();
  const cart = useCart();

  // Auto-select first root category
  useEffect(() => {
    if (selectedRoot) return;
    const roots = categories.filter(c => c.parent_id === null);
    if (roots.length) setSelectedRoot(roots[0].id);
  }, [categories, selectedRoot]);

  // RTL toggle
  const toggleLang = useCallback(() => {
    const next = !isRtl;
    setIsRtl(next);
    document.documentElement.dir  = next ? 'rtl' : 'ltr';
    document.documentElement.lang = next ? 'ar'  : 'en';
    localStorage.setItem('spa_dir', next ? 'rtl' : 'ltr');
  }, [isRtl]);

  // Filtered products
  const rootCategories = useMemo(
    () => categories.filter(c => c.parent_id === null),
    [categories]
  );
  const subCategories = useMemo(
    () => (selectedRoot ? categories.filter(c => c.parent_id === selectedRoot) : []),
    [categories, selectedRoot]
  );

  const products = useMemo(() => {
    let list = allProducts;
    if (selectedSub !== null && selectedSub !== undefined) {
      list = list.filter(p => p.category_id === selectedSub);
    } else if (selectedRoot) {
      const childIds = new Set([
        selectedRoot,
        ...subCategories.map(c => c.id),
      ]);
      list = list.filter(p => childIds.has(p.category_id));
    }
    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      list = list.filter(p =>
        (p.name || '').toLowerCase().includes(q) ||
        (p.description_en || '').toLowerCase().includes(q) ||
        (p.description_ar || '').toLowerCase().includes(q)
      );
    }
    return list;
  }, [allProducts, selectedRoot, selectedSub, subCategories, searchQuery]);

  const handleRootSelect = useCallback(id => {
    setSelectedRoot(id);
    setSelectedSub(null);
  }, []);

  const handleAddToCart = useCallback((product, qty = 1) => {
    cart.addToCart(product, qty);
  }, [cart]);

  const handleCheckoutSuccess = useCallback(orderNumber => {
    cart.clearCart();
    setShowCheckout(false);
    setShowCart(false);
    setLastOrder(orderNumber);
  }, [cart]);

  return (
    <div className="app">
      <Header
        settings={settings}
        cartCount={cart.cartCount}
        onCartClick={() => setShowCart(true)}
        onLangToggle={toggleLang}
        isRtl={isRtl}
      />

      <main className="main-content">
        <HeroStrip settings={settings} isRtl={isRtl} />
        <SearchBar value={searchQuery} onChange={setSearchQuery} isRtl={isRtl} />

        <CategoryChips
          rootCategories={rootCategories}
          subCategories={subCategories}
          selectedRoot={selectedRoot}
          selectedSub={selectedSub}
          onRootSelect={handleRootSelect}
          onSubSelect={setSelectedSub}
          isRtl={isRtl}
        />

        {error && (
          <div className="error-banner">
            <span>{error}</span>
            <button className="retry-btn" onClick={retry}>Retry</button>
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
              products={allProducts.filter(p => !!p.is_featured)}
              onProductClick={setActiveProduct}
              isRtl={isRtl}
            />
            <ProductList
              products={products}
              categories={categories}
              onOpenModal={setActiveProduct}
              isRtl={isRtl}
            />
          </>
        )}
      </main>

      <BottomNav
        cartCount={cart.cartCount}
        cartTotal={cart.cartTotal}
        onCartClick={() => setShowCart(true)}
        isRtl={isRtl}
      />

      {activeProduct && (
        <ProductModal
          product={activeProduct}
          onAdd={handleAddToCart}
          onClose={() => setActiveProduct(null)}
          isRtl={isRtl}
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
          isRtl={isRtl}
        />
      )}

      {showCheckout && (
        <CheckoutModal
          zones={zones}
          cartItems={cart.cartItems}
          cartTotal={cart.cartTotal}
          onSuccess={handleCheckoutSuccess}
          onClose={() => setShowCheckout(false)}
          isRtl={isRtl}
        />
      )}

      {lastOrder && (
        <OrderSuccess
          orderNumber={lastOrder}
          onClose={() => setLastOrder(null)}
          isRtl={isRtl}
        />
      )}
    </div>
  );
}
