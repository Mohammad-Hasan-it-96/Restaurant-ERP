@php
    $activeLanguages = \App\Models\Language::where('status', 1)->orderByDesc('is_default')->get();
    $currentLocale = session('locale', app()->getLocale());
    $isRtl = ($currentLocale === 'ar') || (session('site_direction') === 'rtl');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.menu') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @if($isRtl)
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('css/customer-home.css') }}">
</head>
<body>

<nav class="navbar bg-white border-bottom sticky-top customer-header">
    <div class="container page-shell py-2">
        <div class="d-flex align-items-center gap-2">
            <img id="appLogoMini" class="hero-logo d-none" alt="logo" style="width:44px;height:44px;border-radius:12px;">
            <div id="navLogoFallback" class="logo-fallback logo-fallback-sm"><i class="bi bi-shop"></i></div>
            <div>
                <div id="restaurantNameNav" class="fw-semibold">{{ __('app.restaurant') }}</div>
                <small id="statusTextNav" class="text-muted">...</small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if($activeLanguages->count() > 0)
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-translate me-1"></i>{{ strtoupper($currentLocale) }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach($activeLanguages as $language)
                    <li>
                        <a class="dropdown-item {{ $currentLocale === $language->code ? 'active' : '' }}" href="{{ route('language.change', $language->code) }}">
                            {{ $language->name }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <button id="desktopCartBtn" class="btn top-cart-btn position-relative d-none d-md-inline-flex" data-bs-toggle="offcanvas" data-bs-target="#cartCanvas">
                <i class="bi bi-cart3 me-1"></i>
                <span>{{ __('app.cart') }}</span>
                <span id="cartCountTop" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
            </button>
        </div>
    </div>
</nav>

<main class="container page-shell py-3 pb-5">

    <div id="globalError" class="alert alert-danger d-none d-flex align-items-center justify-content-between gap-2">
        <span id="globalErrorText">{{ __('app.failed_loading_data') }}</span>
        <button id="retryLoadBtn" class="btn btn-sm btn-light">{{ __('app.retry') }}</button>
    </div>

    <section class="hero-card p-3 p-md-3 mb-3">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <img id="heroLogo" class="hero-logo d-none" alt="logo">
                <div id="heroLogoFallback" class="logo-fallback"><i class="bi bi-shop"></i></div>
            </div>
            <div class="col">
                <h1 id="heroName" class="h4 mb-1">{{ __('app.restaurant') }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span id="openBadge" class="badge text-bg-secondary">...</span>
                    <span id="todayHours" class="small text-muted"></span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a id="phoneLink" class="btn btn-sm btn-outline-secondary d-none" href="#">
                        <i class="bi bi-telephone me-1"></i><span id="phoneText"></span>
                    </a>
                    <a id="whatsappLink" class="btn btn-sm btn-outline-success d-none" href="#" target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp me-1"></i><span id="whatsappText"></span>
                    </a>
                </div>
                <small id="deliveryNote" class="text-muted d-block mt-2"></small>
            </div>
        </div>
    </section>

    <section id="rootSection" class="section-card p-2 p-md-3 mb-2">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h6 mb-0">{{ __('app.main_categories') }}</h2>
        </div>
        <div id="rootCategoriesState"></div>
        <div id="rootCategories" class="chip-scroll d-flex gap-2"></div>
    </section>

    <section id="subSection" class="section-card p-2 p-md-3 mb-2 d-none">
        <h2 class="h6 mb-2">{{ __('app.sub_categories') }}</h2>
        <div id="subCategoriesState"></div>
        <div id="subCategories" class="chip-scroll d-flex gap-2"></div>
    </section>

    <section id="featuredSection" class="section-card p-2 p-md-3 mb-2 d-none">
        <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
            <h2 class="h6 mb-0">{{ __('app.featured') }}</h2>
        </div>
        <div id="featuredState"></div>
        <div id="featuredProducts" class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4"></div>
    </section>

    <section id="productsSection" class="section-card p-2 p-md-3 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2 gap-2 flex-wrap">
            <h2 class="h6 mb-0">{{ __('app.products_by_category') }}</h2>
            <input id="productSearch" data-action="search-products" class="form-control form-control-sm product-search-input" placeholder="{{ __('app.search_product') }}">
        </div>
        <div id="productsState"></div>
        <div id="categoryProducts" class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4"></div>
    </section>
</main>

<button id="mobileCartBtn" class="btn btn-dark floating-cart d-md-none" data-bs-toggle="offcanvas" data-bs-target="#cartCanvas">
    <i class="bi bi-cart3 me-1"></i>
    <span id="cartCountMobile">0</span>
</button>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="productModalTitle" class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="product-img-wrap rounded mb-3 d-none" id="productModalImageWrap">
                    <img id="productModalImage" alt="product">
                </div>
                <p id="productModalDesc" class="text-muted small mb-3"></p>
                <div class="d-flex justify-content-between align-items-center">
                    <span>{{ __('app.price') }}</span>
                    <div>
                        <span id="productModalOldPrice" class="price-old me-2 d-none"></span>
                        <span id="productModalPrice" class="price-now"></span>
                    </div>
                </div>
                <div id="productModalUnavailable" class="alert alert-secondary py-2 mt-3 d-none">{{ __('app.unavailable') }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.close') }}</button>
                <button id="productModalAddBtn" type="button" class="btn btn-primary">{{ __('app.add_to_cart') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="cartCanvas" aria-labelledby="cartCanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="cartCanvasLabel"><i class="bi bi-cart3 me-2"></i>{{ __('app.cart') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="cartItems" class="flex-grow-1"></div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">{{ __('app.subtotal') }}</span>
            <strong id="cartSubtotal">0.00</strong>
        </div>
        <button id="checkoutOpenBtn" class="btn btn-success w-100">{{ __('app.checkout') }}</button>
    </div>
</div>

<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <form id="checkoutForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('app.checkout') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="checkoutAlert" class="alert d-none"></div>
                <div id="checkoutApiErrors" class="small text-danger mb-2"></div>

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">{{ __('app.full_name') }}</label>
                        <input name="customer_name" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('app.phone') }}</label>
                        <input name="customer_phone" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('app.order_type') }}</label>
                        <select name="order_type" id="orderType" class="form-select" required>
                            <option value="table">{{ __('app.table') }}</option>
                            <option value="delivery">{{ __('app.delivery') }}</option>
                            <option value="takeaway">{{ __('app.takeaway') }}</option>
                        </select>
                    </div>
                </div>

                <div id="tableFields" class="checkout-fields checkout-hidden mt-2">
                    <label class="form-label">{{ __('app.table_number') }}</label>
                    <input name="table_number" class="form-control" placeholder="{{ __('app.table_number_placeholder') }}">
                </div>

                <div id="deliveryFields" class="checkout-fields checkout-hidden mt-2">
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.address') }}</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.delivery_type') }}</label>
                        <select name="delivery_type" id="deliveryType" class="form-select">
                            <option value="immediate">{{ __('app.immediate') }}</option>
                            <option value="scheduled">{{ __('app.scheduled') }}</option>
                        </select>
                    </div>
                    <div id="scheduledField" class="checkout-fields checkout-hidden mb-2">
                        <label class="form-label">{{ __('app.scheduled_at') }}</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.estimated_delivery_fee') }}</label>
                        <select id="deliveryZone" class="form-select">
                            <option value="">{{ __('app.select') }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-2">
                    <label class="form-label">{{ __('app.customer_note') }}</label>
                    <textarea name="customer_note" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.close') }}</button>
                <button id="submitOrderBtn" type="submit" class="btn btn-primary">{{ __('app.submit_order') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success"><i class="bi bi-check-circle me-1"></i>{{ __('app.order_success') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-2">{{ __('app.order_success_message') }}</p>
                <div class="success-order-number" id="orderSuccessNumber">---</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('app.ok') }}</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.i18n = {
    price:                      @json(__('app.price')),
    checkout:                   @json(__('app.checkout')),
    all:                        @json(__('app.all')),
    delete:                     @json(__('app.delete')),
    sending:                    @json(__('app.sending')),
    submit_order:               @json(__('app.submit_order')),
    details:                    @json(__('app.details')),
    add_to_cart:                @json(__('app.add_to_cart')),
    not_available_now:          @json(__('app.not_available_now')),
    unavailable:                @json(__('app.unavailable')),
    accepting_orders_now:       @json(__('app.accepting_orders_now')),
    browsing_available:         @json(__('app.browsing_available')),
    open_for_orders:            @json(__('app.open_for_orders')),
    closed_now:                 @json(__('app.closed_now')),
    today_hours:                @json(__('app.today_hours')),
    hours_not_set:              @json(__('app.hours_not_set')),
    closed_today:               @json(__('app.closed_today')),
    open_today:                 @json(__('app.open_today')),
    no_description:             @json(__('app.no_description')),
    no_description_detail:      @json(__('app.no_description_detail')),
    failed_loading_partial:     @json(__('app.failed_loading_partial')),
    failed_categories:          @json(__('app.failed_categories')),
    failed_featured:            @json(__('app.failed_featured')),
    failed_products:            @json(__('app.failed_products')),
    no_categories_added:        @json(__('app.no_categories_added')),
    no_subcategories_section:   @json(__('app.no_subcategories_section')),
    no_products_now:            @json(__('app.no_products_now')),
    product_unavailable_alert:  @json(__('app.product_unavailable_alert')),
    cart_is_empty:              @json(__('app.cart_is_empty')),
    no_delivery_zones_available:@json(__('app.no_delivery_zones_available')),
    select_zone:                @json(__('app.select_zone')),
    some_products_unavailable:  @json(__('app.some_products_unavailable')),
    order_sent_success:         @json(__('app.order_sent_success')),
    failed_submit_order:        @json(__('app.failed_submit_order')),
};
</script>
<script>
// ── Remote Log Helper ────────────────────────────────────────────────────────
function sendLog(type, message, data) {
    try {
        navigator.sendBeacon
            ? navigator.sendBeacon('/api/v1/logs/frontend', new Blob(
                [JSON.stringify({ type, message, data })],
                { type: 'application/json' }
              ))
            : fetch('/api/v1/logs/frontend', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body:    JSON.stringify({ type, message, data }),
                keepalive: true,
              }).catch(() => {});
    } catch {}
}

// ── Global Error Handler ─────────────────────────────────────────────────────
window.onerror = function(msg, src, line, col, error) {
    console.error('[JS ERROR]', { msg, src, line, col, error });
    sendLog('js.error', String(msg), { src, line, col, error: error ? error.toString() : null });
};

window.addEventListener('unhandledrejection', function(e) {
    const message = e.reason instanceof Error ? e.reason.message : String(e.reason);
    console.error('[JS UNHANDLED REJECTION]', message);
    sendLog('js.unhandledrejection', message, {});
});

(() => {
    'use strict';

    // ── Logging Helper ───────────────────────────────────────────────────────
    function logEvent(type, data) {
        console.debug(`[APP][${type}]`, data);
    }

    function logError(type, message, data) {
        console.error(`[APP][${type}]`, message, data);
        sendLog(type, message, data);
    }

    const apiBase    = '/api/v1';
    const storageKey = 'restaurant_cart_v1';
    const isAr       = document.documentElement.lang.startsWith('ar');

    // ── State ───────────────────────────────────────────────────────────────────
    const state = {
        settings: {},
        categories: [],
        products: [],
        deliveryZones: [],
        selectedRootCategoryId: null,
        selectedSubCategoryId: null,
        searchQuery: '',
        cart: [],
    };
    const loadErrors = { settings: null, categories: null, products: null, deliveryZones: null };


    // â”€â”€ DOM refs â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    const el = {
        globalError:             document.getElementById('globalError'),
        globalErrorText:         document.getElementById('globalErrorText'),
        retryLoadBtn:            document.getElementById('retryLoadBtn'),
        restaurantNameNav:       document.getElementById('restaurantNameNav'),
        statusTextNav:           document.getElementById('statusTextNav'),
        appLogoMini:             document.getElementById('appLogoMini'),
        navLogoFallback:         document.getElementById('navLogoFallback'),
        heroLogo:                document.getElementById('heroLogo'),
        heroLogoFallback:        document.getElementById('heroLogoFallback'),
        heroName:                document.getElementById('heroName'),
        openBadge:               document.getElementById('openBadge'),
        todayHours:              document.getElementById('todayHours'),
        deliveryNote:            document.getElementById('deliveryNote'),
        phoneLink:               document.getElementById('phoneLink'),
        phoneText:               document.getElementById('phoneText'),
        whatsappLink:            document.getElementById('whatsappLink'),
        whatsappText:            document.getElementById('whatsappText'),
        subSection:              document.getElementById('subSection'),
        featuredSection:         document.getElementById('featuredSection'),
        rootCategoriesState:     document.getElementById('rootCategoriesState'),
        rootCategories:          document.getElementById('rootCategories'),
        subCategoriesState:      document.getElementById('subCategoriesState'),
        subCategories:           document.getElementById('subCategories'),
        featuredState:           document.getElementById('featuredState'),
        featuredProducts:        document.getElementById('featuredProducts'),
        productsState:           document.getElementById('productsState'),
        categoryProducts:        document.getElementById('categoryProducts'),
        productSearch:           document.getElementById('productSearch'),
        cartCountTop:            document.getElementById('cartCountTop'),
        cartCountMobile:         document.getElementById('cartCountMobile'),
        cartItems:               document.getElementById('cartItems'),
        cartSubtotal:            document.getElementById('cartSubtotal'),
        checkoutOpenBtn:         document.getElementById('checkoutOpenBtn'),
        productModalTitle:       document.getElementById('productModalTitle'),
        productModalImageWrap:   document.getElementById('productModalImageWrap'),
        productModalImage:       document.getElementById('productModalImage'),
        productModalDesc:        document.getElementById('productModalDesc'),
        productModalPrice:       document.getElementById('productModalPrice'),
        productModalOldPrice:    document.getElementById('productModalOldPrice'),
        productModalUnavailable: document.getElementById('productModalUnavailable'),
        productModalAddBtn:      document.getElementById('productModalAddBtn'),
        checkoutForm:            document.getElementById('checkoutForm'),
        checkoutAlert:           document.getElementById('checkoutAlert'),
        checkoutApiErrors:       document.getElementById('checkoutApiErrors'),
        submitOrderBtn:          document.getElementById('submitOrderBtn'),
        orderType:               document.getElementById('orderType'),
        deliveryType:            document.getElementById('deliveryType'),
        tableFields:             document.getElementById('tableFields'),
        deliveryFields:          document.getElementById('deliveryFields'),
        scheduledField:          document.getElementById('scheduledField'),
        deliveryZone:            document.getElementById('deliveryZone'),
        orderSuccessNumber:      document.getElementById('orderSuccessNumber'),
    };

    let activeProduct = null;

    init();

    // â”€â”€ Bootstrap â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async function init() {
        state.cart = loadCart();
        wireEvents();
        renderLoadingSkeletons();
        await loadAll();
        renderAll();
    }

    // â”€â”€ Event Wiring â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function wireEvents() {
        // â”€â”€ Retry
        el.retryLoadBtn.addEventListener('click', async () => {
            await loadAll();
            renderAll();
        });

        // â”€â”€ Checkout form dynamics
        el.orderType.addEventListener('change', handleOrderTypeUI);
        el.deliveryType.addEventListener('change', handleDeliveryTypeUI);
        el.checkoutForm.addEventListener('submit', submitCheckout);

        // â”€â”€ Checkout open â€“ guard empty cart
        el.checkoutOpenBtn.addEventListener('click', () => {
            if (!state.cart.length) {
                const orig = el.checkoutOpenBtn.innerHTML;
                el.checkoutOpenBtn.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${window.i18n.cart_is_empty}`;
                el.checkoutOpenBtn.disabled = true;
                setTimeout(() => {
                    el.checkoutOpenBtn.innerHTML = orig;
                    el.checkoutOpenBtn.disabled = false;
                }, 2000);
                return;
            }
            // Close cart offcanvas first then open checkout
            const cartCanvas = bootstrap.Offcanvas.getInstance(document.getElementById('cartCanvas'));
            if (cartCanvas) {
                cartCanvas.hide();
                // Wait for offcanvas close animation then open modal
                document.getElementById('cartCanvas').addEventListener('hidden.bs.offcanvas', () => {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutModal')).show();
                }, { once: true });
            } else {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutModal')).show();
            }
        });

        // â”€â”€ Product modal â€“ add to cart
        el.productModalAddBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            if (!activeProduct) return;
            console.debug('[addToCart] modal â†’ product:', activeProduct.id, activeProduct.name);
            addToCart(activeProduct.id);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal')).hide();
        });

        // â”€â”€ Event delegation for filters and search
        document.addEventListener('click', handleFilterClick);
        document.addEventListener('input', handleFilterInput);

        // â”€â”€ GLOBAL click delegation (product/cart actions)
        document.addEventListener('click', handleGlobalClick);
    }

    /**
     * Handles delegated click actions for category filters only.
     */
    function handleFilterClick(e) {
        const actionEl = e.target.closest('[data-action]');
        if (!actionEl) return;

        const action = actionEl.dataset.action;
        const categoryId = actionEl.dataset.categoryId;

        if (action === 'select-root-category') {
            const id = Number(categoryId);
            if (!Number.isFinite(id) || state.selectedRootCategoryId === id) return;
            state.selectedRootCategoryId = id;
            state.selectedSubCategoryId = null;
            renderCategories();
            renderSubCategories();
            renderProducts();
            return;
        }

        if (action === 'select-sub-category') {
            state.selectedSubCategoryId = (categoryId === 'all') ? null : Number(categoryId);
            renderSubCategories();
            renderProducts();
        }
    }

    /**
     * Handles delegated input actions for product search only.
     */
    function handleFilterInput(e) {
        const actionEl = e.target.closest('[data-action="search-products"]');
        if (!actionEl) return;
        state.searchQuery = actionEl.value.trim();
        renderProducts();
    }

    /**
     * Single global click handler – replaces all querySelectorAll + forEach bindings.
     * Works correctly even after innerHTML re-renders.
     *
     * Product/Cart: data-action="open-product|add-to-cart|cart-plus|cart-minus|cart-remove"
     *                 data-product-id="<numeric id>"
     */
    function handleGlobalClick(e) {
        const t = e.target;

        // ── Unified action dispatcher (data-action + data-product-id/category-id) ─────────
        const actionEl = t.closest('[data-action]');
        if (!actionEl) return;

        const action    = actionEl.dataset.action;
        const productId = actionEl.dataset.productId ? Number(actionEl.dataset.productId) : null;
        const categoryId = actionEl.dataset.categoryId;

        console.debug('[click] action:', action, '| productId:', productId, '| categoryId:', categoryId);

        switch (action) {
            case 'open-product': {
                openProductModal(productId);
                break;
            }
            case 'add-to-cart': {
                if (actionEl.disabled) break;
                addToCart(productId);
                break;
            }
            case 'cart-plus':   { updateCartQuantity(productId,  1); break; }
            case 'cart-minus':  { updateCartQuantity(productId, -1); break; }
            case 'cart-remove': { removeFromCart(productId); break; }
        }
    }

    // â”€â”€ Data Fetching â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async function loadAll() {
        resetErrors();
        await Promise.allSettled([
            fetchSettings(),
            fetchCategories(),
            fetchProducts(),
            fetchDeliveryZones(),
        ]);
        const failures = Object.values(loadErrors).filter(Boolean);
        el.globalError.classList.toggle('d-none', failures.length === 0);
        if (failures.length) el.globalErrorText.textContent = window.i18n.failed_loading_partial;
    }

    async function fetchJSON(url, options = {}) {
         console.debug('[fetchJSON] GET/POST:', url, 'options:', options);
         const response = await fetch(url, {
             headers: { 'Accept': 'application/json', ...(options.headers || {}) },
             ...options,
         });
         console.debug('[fetchJSON] status:', response.status, 'ok:', response.ok);
         const body = await response.json().catch(() => ({}));
         if (!response.ok) {
             console.error('[fetchJSON] error response:', body);
             const normalizedMessage = normalizeMaybeEscapedText(body.message || '', `HTTP ${response.status}`);
             const err      = new Error(normalizedMessage);
             err.errors     = normalizeApiErrors(body.errors);
             err.httpStatus = response.status;
             sendLog('api.error', normalizedMessage, { url, status: response.status });
             throw err;
         }
         console.debug('[fetchJSON] success response:', body);
         return body;
     }

    /**
     * Normalizes API validation errors and decodes escaped unicode strings.
     */
    function normalizeApiErrors(errors) {
        if (!errors || typeof errors !== 'object') return null;

        const normalized = {};
        Object.entries(errors).forEach(([field, messages]) => {
            const list = Array.isArray(messages) ? messages : [messages];
            normalized[field] = list
                .map(message => normalizeMaybeEscapedText(message, ''))
                .filter(message => message !== '');
        });

        return normalized;
    }

    /**
     * Extracts arrays from known API payload shapes.
     */
    function extractArray(response) {
        if (Array.isArray(response?.data)) return response.data;
        if (Array.isArray(response?.data?.data)) return response.data.data;
        if (Array.isArray(response?.data?.data?.data)) return response.data.data.data;
        if (Array.isArray(response?.data?.items)) return response.data.items;
        return [];
    }

     /**
      * Unwraps the unified API envelope for non-array payloads (e.g. settings object).
      */
     function normalizeData(payload) {
         if (!payload) return null;
         const data = (payload.success !== undefined) ? payload.data : payload;
         if (!data) return null;
         if (!Array.isArray(data) && Array.isArray(data.data)) return data.data;
         if (!Array.isArray(data) && Array.isArray(data.items)) return data.items;
         return data;
     }

    function normalizeMaybeEscapedText(value, fallback = '') {
        if (value == null) return fallback;
        const raw = String(value).trim();
        if (!raw) return fallback;
        try { const d = JSON.parse(raw); if (typeof d === 'string') return d; } catch {}
        if (raw.includes('\\u')) {
            try {
                const w = JSON.parse('"' + raw.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"');
                if (typeof w === 'string') return w;
            } catch {}
        }
        return raw;
    }

     async function fetchSettings() {
         try {
             console.debug('[API] fetching settings...');
             const payload  = await fetchJSON(`${apiBase}/settings/public`);
             const settings = normalizeData(payload) || {};
             settings.restaurant_name = normalizeMaybeEscapedText(settings.restaurant_name, 'Restaurant');
             console.debug('[API] settings loaded:', settings);
             state.settings = settings;
         } catch (err) {
             console.warn('[API] settings error:', err.message);
             loadErrors.settings = err.message;
             state.settings = { restaurant_name: 'Restaurant', restaurant_logo: null, is_open_now: false, opening_hours: {} };
         }
     }

     async function fetchCategories() {
         try {
             console.debug('[API] fetching categories → GET', `${apiBase}/categories`);
             const payload = await fetchJSON(`${apiBase}/categories`);
             state.categories = extractArray(payload);
             console.debug('[categories] ✓ loaded', state.categories.length, 'items');
             if (state.categories.length) {
                 console.debug('[categories] first item sample:', JSON.stringify(state.categories[0]));
                 const rootCount = state.categories.filter(c => c.parent_id === null || c.parent_id === undefined).length;
                 console.debug('[categories] root categories:', rootCount, '| sub categories:', state.categories.length - rootCount);
             }

             if (!state.selectedRootCategoryId && state.categories.length) {
                 const roots = state.categories.filter(c => c.parent_id === null || c.parent_id === undefined);
                 state.selectedRootCategoryId = roots.length ? roots[0].id : state.categories[0].id;
                 console.debug('[categories] auto-selected root id:', state.selectedRootCategoryId);
             }
         } catch (err) {
             console.warn('[API] categories error:', err.message, err);
             loadErrors.categories = err.message;
             state.categories = [];
         }
     }

     async function fetchProducts() {
         try {
             console.debug('[API] fetching products → GET', `${apiBase}/products`);
             const payload = await fetchJSON(`${apiBase}/products`);
             const raw = extractArray(payload);
             console.debug('[products] ✓ raw extracted:', raw.length, 'items');

             if (raw.length) {
                 console.debug('[products] first item sample:', JSON.stringify(raw[0]));
                 const withCat = raw.filter(p => p.category_id != null).length;
                 console.debug('[products] items WITH category_id:', withCat, '| WITHOUT:', raw.length - withCat);
             }

             // Map to ensure effective_price is always a valid number
             // (API returns it, but recalculate as fallback in case it's missing)
             state.products = raw.map(p => ({
                 ...p,
                 category_id:    p.category_id != null ? Number(p.category_id) : null,
                 effective_price: (p.effective_price != null)
                     ? Number(p.effective_price)
                     : Number(p.discount_price || p.price || 0),
             }));

             console.debug('[products] ✓ state.products stored:', state.products.length, 'items');
             logEvent('products.loaded', { count: state.products.length });
         } catch (err) {
             console.warn('[API] products error:', err.message, err);
             loadErrors.products = err.message;
             state.products = [];
         }
     }

     async function fetchDeliveryZones() {
         try {
             console.debug('[API] fetching delivery zones → GET', `${apiBase}/delivery-zones`);
             const payload        = await fetchJSON(`${apiBase}/delivery-zones`);
             state.deliveryZones  = extractArray(payload);
             console.debug('[API] delivery zones ✓ loaded:', state.deliveryZones.length, 'items');
         } catch (err) {
             console.warn('[API] delivery zones error:', err.message, err);
             loadErrors.deliveryZones = err.message;
             state.deliveryZones = [];
         }
     }

    /**
     * Resets loading errors for all API resources.
     */
    function resetErrors() {
        Object.keys(loadErrors).forEach(key => {
            loadErrors[key] = null;
        });
    }

    // â”€â”€ Rendering â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function renderAll() {
        renderAppHeader();
        renderCategories();
        renderSubCategories();
        renderFeatured();
        renderProducts();
        renderDeliveryZones();
        renderCart();
        handleOrderTypeUI();
        handleDeliveryTypeUI();
    }

    function renderLoadingSkeletons() {
        el.featuredState.innerHTML  = skeletonRow(4);
        el.productsState.innerHTML  = skeletonRow(8);
    }

    function renderAppHeader() {
        const s    = state.settings || {};
        const name = normalizeMaybeEscapedText(s.restaurant_name, 'Restaurant');

        el.restaurantNameNav.textContent = name;
        el.heroName.textContent          = name;

        setLogoWithFallback(el.appLogoMini, el.navLogoFallback, s.restaurant_logo);
        setLogoWithFallback(el.heroLogo, el.heroLogoFallback, s.restaurant_logo);

        const effectivelyOpen = (s.is_accepting_orders !== false) && !!s.is_open_now;
        el.openBadge.className      = `badge ${effectivelyOpen ? 'text-bg-success' : 'text-bg-danger'}`;
        el.openBadge.textContent    = effectivelyOpen ? window.i18n.open_for_orders : window.i18n.closed_now;
        el.statusTextNav.textContent = effectivelyOpen ? window.i18n.accepting_orders_now : window.i18n.browsing_available;
        el.todayHours.textContent   = getTodayHoursSummary(s.opening_hours || {});
        el.deliveryNote.textContent = s.delivery_note || '';

        if (s.restaurant_phone) {
            el.phoneText.textContent = s.restaurant_phone;
            el.phoneLink.href        = `tel:${s.restaurant_phone}`;
            el.phoneLink.classList.remove('d-none');
        } else { el.phoneLink.classList.add('d-none'); }

        if (s.restaurant_whatsapp) {
            const phone = s.restaurant_whatsapp.replace(/\D/g, '');
            el.whatsappText.textContent = s.restaurant_whatsapp;
            el.whatsappLink.href        = `https://wa.me/${phone}`;
            el.whatsappLink.classList.remove('d-none');
        } else { el.whatsappLink.classList.add('d-none'); }
    }

    function getTodayHoursSummary(openingHours) {
        const days    = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
        const today   = openingHours[days[new Date().getDay()]];
        if (!today)              return window.i18n.hours_not_set;
        if (!today.is_open)      return window.i18n.closed_today;
        if (!today.from || !today.to) return window.i18n.open_today;
        return `${window.i18n.today_hours}: ${today.from} - ${today.to}`;
    }

    function setLogoWithFallback(imgNode, fallbackNode, src) {
        if (!src) { imgNode.classList.add('d-none'); fallbackNode.classList.remove('d-none'); return; }
        imgNode.onload  = () => { imgNode.classList.remove('d-none'); fallbackNode.classList.add('d-none'); };
        imgNode.onerror = () => { imgNode.classList.add('d-none');    fallbackNode.classList.remove('d-none'); };
        imgNode.src = src;
    }

    function renderCategories() {
        if (loadErrors.categories) {
            el.rootCategoriesState.innerHTML = errorState(window.i18n.failed_categories);
            el.rootCategories.innerHTML = '';
            return;
        }
        // Use state.categories variable
        const roots = state.categories.filter(c => c.parent_id === null || c.parent_id === undefined);
        if (!roots.length) {
            el.rootCategoriesState.innerHTML = emptyState(window.i18n.no_categories_added);
            el.rootCategories.innerHTML = '';
            return;
        }
        el.rootCategoriesState.innerHTML = '';
        el.rootCategories.innerHTML = roots.map(c => `
            <button class="btn btn-sm main-chip cat-chip ${state.selectedRootCategoryId === c.id ? 'active' : ''}"
                    data-action="select-root-category"
                    data-category-id="${c.id}">
                ${c.image ? `<img src="${escapeHtml(c.image)}" class="chip-thumb me-1 js-chip-thumb" alt="">` : ''}
                ${escapeHtml(c.name)}
            </button>
        `).join('');
        // Image fallback for chips (onerror doesn't bubble → bind explicitly)
        el.rootCategories.querySelectorAll('.js-chip-thumb').forEach(img => {
            img.addEventListener('error', () => img.remove(), { once: true });
        });
    }

    function renderSubCategories() {
        if (!state.selectedRootCategoryId || !state.categories.length) {
            el.subSection.classList.add('d-none');
            return;
        }
        const rootId = Number(state.selectedRootCategoryId);
        const subs = state.categories.filter(c => c.parent_id !== null && c.parent_id !== undefined && Number(c.parent_id) === rootId);
        console.debug('[renderSubCategories] rootId:', rootId, '→', subs.length, 'sub-categories found');
        if (!subs.length) {
            el.subSection.classList.add('d-none');
            el.subCategories.innerHTML = '';
            return;
        }
        el.subSection.classList.remove('d-none');
        el.subCategoriesState.innerHTML = '';
        el.subCategories.innerHTML = `
            <button class="btn btn-sm btn-outline-secondary cat-chip sub-chip ${state.selectedSubCategoryId === null ? 'active' : ''}"
                    data-action="select-sub-category"
                    data-category-id="all">${window.i18n.all}</button>
            ${subs.map(c => `
                <button class="btn btn-sm btn-outline-secondary cat-chip sub-chip ${state.selectedSubCategoryId === c.id ? 'active' : ''}"
                        data-action="select-sub-category"
                        data-category-id="${c.id}">${escapeHtml(c.name)}</button>
            `).join('')}
        `;
    }

    function renderFeatured() {
        if (loadErrors.products) {
            el.featuredSection.classList.remove('d-none');
            el.featuredState.innerHTML  = errorState(window.i18n.failed_featured);
            el.featuredProducts.innerHTML = '';
            return;
        }
        const featured = state.products.filter(p => !!p.is_featured).slice(0, 8);
        if (!featured.length) { el.featuredSection.classList.add('d-none'); return; }

        el.featuredSection.classList.remove('d-none');
        el.featuredState.innerHTML      = '';
        el.featuredProducts.innerHTML   = featured.map(productCard).join('');
        bindProductImageFallbacks(el.featuredProducts); // onerror doesn't bubble
    }

    function getFilteredProducts() {
        let products = [...state.products];

        if (state.selectedSubCategoryId !== null && state.selectedSubCategoryId !== undefined) {
            products = products.filter(p => p.category_id != null && Number(p.category_id) === state.selectedSubCategoryId);
        } else if (state.selectedRootCategoryId !== null && state.selectedRootCategoryId !== undefined) {
            const rootId = Number(state.selectedRootCategoryId);
            const childIds = state.categories.filter(c => c.parent_id !== null && c.parent_id !== undefined && Number(c.parent_id) === rootId).map(c => Number(c.id));
            const allowed = new Set([rootId, ...childIds]);
            products = products.filter(p => p.category_id != null && allowed.has(Number(p.category_id)));
        }

        if (state.searchQuery) {
            products = products.filter(p => {
                const haystack = [p.name || '', p.name_ar || '', p.name_en || '', p.description_ar || '', p.description_en || ''].join(' ').toLowerCase();
                return haystack.includes(state.searchQuery.toLowerCase());
            });
        }

        return products;
    }

    function renderProducts() {
        if (loadErrors.products) {
            el.productsState.innerHTML    = errorState(window.i18n.failed_products);
            el.categoryProducts.innerHTML = '';
            return;
        }

        const products = getFilteredProducts();
        logEvent('filter.applied', {
            root:  state.selectedRootCategoryId,
            sub:   state.selectedSubCategoryId,
            count: products.length,
        });
        console.debug('[filter] renderProducts() start → state.products count:', state.products.length);
        console.debug('[filter] selected → root:', state.selectedRootCategoryId, '| sub:', state.selectedSubCategoryId);

        console.debug('[filter] ✓ final filtered count:', products.length,
                      '| root:', state.selectedRootCategoryId, '| sub:', state.selectedSubCategoryId,
                      '| search:', state.searchQuery || '—');

        if (!products.length) {
            el.productsState.innerHTML = `
                <div class="empty-state d-flex align-items-center gap-2">
                    <i class="bi bi-emoji-neutral"></i>
                    <span>${window.i18n.no_products_now}</span>
                </div>`;
            el.categoryProducts.innerHTML = '';
            return;
        }

        el.productsState.innerHTML      = '';
        el.categoryProducts.innerHTML   = products.map(productCard).join('');
        bindProductImageFallbacks(el.categoryProducts); // onerror doesn't bubble
    }

    function productCard(p) {
        const available  = !!p.is_available;
        const rawDesc    = isAr
            ? (p.description_ar || p.description_en || '')
            : (p.description_en || p.description_ar || '');
        const shortDesc  = rawDesc.length > 90 ? `${rawDesc.slice(0, 90)}...` : rawDesc;
        const oldPrice   = p.discount_price
            ? `<span class="price-old me-1">${num(p.price)}</span>`
            : '';
        const imageBlock = p.image
            ? `<img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" class="js-product-img">`
            : `<div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="bi bi-image fs-2"></i></div>`;

        return `
            <div class="col">
                <article class="product-card h-100 ${available ? '' : 'product-unavailable'}"
                         data-action="open-product"
                         data-product-id="${p.id}">
                    <div class="product-img-wrap">${imageBlock}</div>
                    <div class="p-3 d-flex flex-column h-100" style="min-height:0">
                        <h3 class="h6 mb-1">${escapeHtml(p.name)}</h3>
                        <p class="text-muted small mb-2 flex-grow-1">${escapeHtml(shortDesc || window.i18n.no_description)}</p>
                        <div class="mb-2">${oldPrice}<span class="price-now">${num(p.effective_price)}</span></div>
                        ${available ? '' : `<span class="badge text-bg-secondary mb-2">${window.i18n.unavailable}</span>`}
                        <div class="d-grid gap-2 mt-auto">
                            <button class="btn btn-outline-secondary btn-sm"
                                    data-product-id="${p.id}"
                                    data-action="open-product">${window.i18n.details}</button>
                            <button class="btn btn-primary btn-sm"
                                    data-product-id="${p.id}"
                                    data-action="add-to-cart"
                                    ${available ? '' : 'disabled'}>
                                <i class="bi bi-plus-circle me-1"></i>${window.i18n.add_to_cart}
                            </button>
                        </div>
                    </div>
                </article>
            </div>`;
    }

    // onerror events don't bubble â†’ must bind explicitly
    function bindProductImageFallbacks(container) {
        container.querySelectorAll('.js-product-img').forEach(img => {
            img.addEventListener('error', () => {
                const wrap = img.closest('.product-img-wrap');
                if (wrap) wrap.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="bi bi-image fs-2"></i></div>';
            }, { once: true });
        });
    }

    // â”€â”€ Product Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
     function openProductModal(productId) {
         console.debug('[openProductModal] opening modal for productId:', productId);
         const product = state.products.find(item => Number(item.id) === Number(productId));
         if (!product) {
             console.warn('[openProductModal] product not found:', productId);
             return;
         }

         activeProduct = product;
         logEvent('product.open', product);
         console.debug('[openProductModal] activeProduct set:', product.name);

        el.productModalTitle.textContent = product.name;

        const desc = (isAr
            ? (product.description_ar || product.description_en || '')
            : (product.description_en || product.description_ar || '')).trim();
        el.productModalDesc.textContent = desc || window.i18n.no_description_detail;

        if (product.image) {
            el.productModalImage.onerror = () => el.productModalImageWrap.classList.add('d-none');
            el.productModalImage.src     = product.image;
            el.productModalImageWrap.classList.remove('d-none');
        } else {
            el.productModalImageWrap.classList.add('d-none');
        }

        if (product.discount_price) {
            el.productModalOldPrice.textContent = num(product.price);
            el.productModalOldPrice.classList.remove('d-none');
        } else {
            el.productModalOldPrice.classList.add('d-none');
        }

        el.productModalPrice.textContent = num(product.effective_price);

         const unavailable = !product.is_available;
         el.productModalUnavailable.classList.toggle('d-none', !unavailable);
         el.productModalAddBtn.disabled = unavailable;
         el.productModalAddBtn.dataset.action = 'add-to-cart';
         el.productModalAddBtn.dataset.productId = String(product.id);

         console.debug('[openProductModal] showing modal, available:', !!product.is_available);
         new bootstrap.Modal(document.getElementById('productModal')).show();
     }

    /**
     * Adds a product to cart by product id.
     */
    function addToCart(productId) {
        const product = findProduct(productId);
        if (!product) return;

        if (!product.is_available) {
            console.warn('[addToCart] not available, blocking:', product.id);
            alert(window.i18n.product_unavailable_alert);
            return;
        }

        const pid = Number(product.id);
        const price = Number(product.effective_price ?? product.discount_price ?? product.price ?? 0);
        const existing = state.cart.find(item => Number(item.id) === pid);

        if (existing) {
            existing.quantity += 1;
        } else {
            state.cart.push({
                id: pid,
                name: product.name || '',
                price,
                image: product.image || null,
                quantity: 1,
            });
        }

        saveCart();
        logEvent('cart.add', { productId });
        renderCart();
    }

    /**
     * Renders cart items, subtotal, and cart badges.
     */
    function renderCart() {
        const count = getCartCount();
        const subtotal = getCartSubtotal();

        el.cartCountTop.textContent = count;
        el.cartCountMobile.textContent = count;
        el.checkoutOpenBtn.disabled = state.cart.length === 0;

        if (!state.cart.length) {
            el.cartItems.innerHTML = `<div class="cart-empty text-center text-muted py-4"><i class="bi bi-cart3 fs-3 d-block mb-2"></i>${window.i18n.cart_is_empty}</div>`;
            el.cartSubtotal.textContent = num(0);
            return;
        }

        el.cartItems.innerHTML = state.cart.map(item => {
            const pid = Number(item.id);
            const product = findProduct(pid);
            const isAvailable = product ? !!product.is_available : false;
            const lineTotal = Number(item.price) * item.quantity;
            return `
                <div class="border rounded p-2 mb-2 bg-white">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">${escapeHtml(item.name)}</div>
                            <div class="small text-muted">${num(item.price)} &times; ${item.quantity}</div>
                            ${isAvailable ? '' : `<span class="badge text-bg-secondary mt-1">${window.i18n.not_available_now}</span>`}
                        </div>
                        <button class="btn btn-sm btn-link text-danger p-0"
                                data-product-id="${pid}"
                                data-action="cart-remove">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary qty-btn"
                                    data-product-id="${pid}"
                                    data-action="cart-minus">&minus;</button>
                            <button class="btn btn-outline-secondary" disabled style="min-width:36px">${item.quantity}</button>
                            <button class="btn btn-outline-secondary qty-btn"
                                    data-product-id="${pid}"
                                    data-action="cart-plus">+</button>
                        </div>
                        <strong>${num(lineTotal)}</strong>
                    </div>
                </div>`;
        }).join('');

        el.cartSubtotal.textContent = num(subtotal);
    }

    /**
     * Updates an item's quantity by delta.
     */
    function updateCartQuantity(productId, delta) {
        const pid = Number(productId);
        const index = state.cart.findIndex(item => Number(item.id) === pid);
        if (index < 0) return;

        state.cart[index].quantity += delta;
        if (state.cart[index].quantity <= 0) {
            state.cart.splice(index, 1);
        }

        saveCart();
        logEvent('cart.update', state.cart);
        renderCart();
    }

    /**
     * Removes an item from cart by product id.
     */
    function removeFromCart(productId) {
        const pid = Number(productId);
        state.cart = state.cart.filter(item => Number(item.id) !== pid);
        saveCart();
        logEvent('cart.update', state.cart);
        renderCart();
    }

    /**
     * Returns cart subtotal amount.
     */
    function getCartSubtotal() {
        return state.cart.reduce((sum, item) => sum + (Number(item.price) * Number(item.quantity)), 0);
    }

    /**
     * Returns total cart items count.
     */
    function getCartCount() {
        return state.cart.reduce((sum, item) => sum + Number(item.quantity), 0);
    }

    // â”€â”€ Checkout / Delivery Zones â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function renderDeliveryZones() {
        if (!state.deliveryZones.length) {
            el.deliveryZone.innerHTML = `<option value="">${window.i18n.no_delivery_zones_available}</option>`;
            return;
        }
        el.deliveryZone.innerHTML =
            `<option value="">${window.i18n.select_zone}</option>` +
            state.deliveryZones.map(z =>
                `<option value="${z.estimated_fee}">${escapeHtml(z.area_name)} (${num(z.estimated_fee)})</option>`
            ).join('');
    }

     function handleOrderTypeUI() {
         const type = el.orderType.value;
         console.debug('[handleOrderTypeUI] selected type:', type);
         toggleField(el.tableFields,    type === 'table');
         toggleField(el.deliveryFields, type === 'delivery');
     }

     function handleDeliveryTypeUI() {
         const deliveryType = el.deliveryType.value;
         console.debug('[handleDeliveryTypeUI] delivery type:', deliveryType);
         toggleField(el.scheduledField, deliveryType === 'scheduled');
     }

    function toggleField(node, visible) {
        node.classList.toggle('checkout-hidden', !visible);
        node.classList.toggle('checkout-visible',  visible);
    }

    function validateCartBeforeCheckout() {
        const unavailable = state.cart.filter(item => {
            const p = findProduct(item.id);
            return !p || !p.is_available;
        });
        if (!unavailable.length) return true;
         showCheckoutAlert('danger', `${window.i18n.some_products_unavailable} ${unavailable.map(i => i.name).join(', ')}`);
        return false;
    }

    /**
     * Submits checkout form to orders API.
     */
    async function submitCheckout(event) {
        event.preventDefault();
        el.checkoutApiErrors.innerHTML = '';
        hideCheckoutAlert();

        if (!state.cart.length) {
            showCheckoutAlert('danger', window.i18n.cart_is_empty);
            return;
        }
        if (!validateCartBeforeCheckout()) return;

        const formData = new FormData(el.checkoutForm);
        const payload  = {
            customer_name:  String(formData.get('customer_name')  || '').trim(),
            customer_phone: String(formData.get('customer_phone') || '').trim(),
            order_type:     formData.get('order_type'),
            table_number:   null,
            address:        null,
            delivery_type:  null,
            scheduled_at:   null,
            customer_note:  String(formData.get('customer_note')  || '').trim() || null,
            estimated_delivery_fee: null,
            items: state.cart.map(item => ({
                product_id: item.id,
                quantity:   item.quantity,
            })),
        };

        if (payload.order_type === 'table') {
            payload.table_number = String(formData.get('table_number') || '').trim();
        }

        if (payload.order_type === 'delivery') {
            payload.address       = String(formData.get('address') || '').trim();
            payload.delivery_type = formData.get('delivery_type') || 'immediate';
            if (payload.delivery_type === 'scheduled') {
                payload.scheduled_at = formData.get('scheduled_at') || null;
            }
            if (el.deliveryZone.value) {
                payload.estimated_delivery_fee = Number(el.deliveryZone.value);
            }
        }

        console.debug('[order] payload â†’', JSON.stringify(payload, null, 2));
        logEvent('order.submit', payload);

        el.submitOrderBtn.disabled    = true;
        el.submitOrderBtn.textContent = window.i18n.sending;

        try {
            const response    = await fetchJSON(`${apiBase}/orders`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });

             const data        = normalizeData(response) || {};
             const orderNumber = data.order_number || data.id || '---';
             console.debug('[submitCheckout] extracted order_number:', orderNumber);

            state.cart = [];
            clearCartStorage();
            renderCart();
            el.checkoutForm.reset();
            handleOrderTypeUI();
            handleDeliveryTypeUI();

            bootstrap.Modal.getInstance(document.getElementById('checkoutModal'))?.hide();
            bootstrap.Offcanvas.getInstance(document.getElementById('cartCanvas'))?.hide();

            el.orderSuccessNumber.textContent = `#${orderNumber}`;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('orderSuccessModal')).show();

         } catch (error) {
             console.error('[submitCheckout] error:', error.message, error);
             logError('order.submit.failed', error.message || 'submit failed', {
                 httpStatus: error.httpStatus ?? null,
             });
             showCheckoutAlert('danger', error.message || window.i18n.failed_submit_order);
             if (error.errors && typeof error.errors === 'object') {
                 console.error('[submitCheckout] validation errors:', error.errors);
                 el.checkoutApiErrors.innerHTML = Object.values(error.errors)
                     .flat()
                     .map(msg => `<div>• ${escapeHtml(msg)}</div>`)
                     .join('');
             }
         } finally {
             el.submitOrderBtn.disabled    = false;
             el.submitOrderBtn.textContent = window.i18n.submit_order;
         }
     }

    function showCheckoutAlert(type, message) {
        el.checkoutAlert.className = `alert alert-${type}`;
        el.checkoutAlert.textContent = message;
        el.checkoutAlert.classList.remove('d-none');
    }

    function hideCheckoutAlert() {
        el.checkoutAlert.classList.add('d-none');
    }

      function findProduct(productId) {
          // Search in state.products (the single source of truth)
          const found = state.products.find(p => Number(p.id) === Number(productId));
          if (found) {
              console.debug('[findProduct] found:', found.name, '(id:', productId, ')');
              return found;
          }
          console.warn('[findProduct] NOT FOUND for id:', productId, '| total products:', state.products.length);
          return null;
       }

    /**
     * Saves cart to localStorage.
     */
    function saveCart() {
        localStorage.setItem(storageKey, JSON.stringify(state.cart));
    }

    /**
     * Clears cart key from localStorage.
     */
    function clearCartStorage() {
        localStorage.removeItem(storageKey);
    }

    /**
     * Loads cart from localStorage.
     */
    function loadCart() {
        try {
            const raw = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return raw.map(item => ({
                id:       item.id       ?? item.product_id   ?? null,
                name:     item.name     ?? item.product_name ?? '',
                price:    item.price    ?? item.product_price ?? 0,
                image:    item.image    ?? null,
                quantity: item.quantity ?? 1,
            })).filter(item => item.id != null);
        } catch {
            return [];
        }
    }

    function num(value) {
        const n = Number(value || 0);
        return n.toLocaleString(isAr ? 'ar-SY' : 'en-US');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    }

    function skeletonRow(count) {
        return `<div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">${
            Array.from({ length: count }).map(() =>
                '<div class="col"><div class="skeleton skeleton-card"></div></div>'
            ).join('')
        }</div>`;
    }

    function emptyState(text) { return `<div class="empty-state">${escapeHtml(text)}</div>`; }
    function errorState(text) { return `<div class="error-state text-danger">${escapeHtml(text)}</div>`; }

})();
</script>
</body>
</html>
