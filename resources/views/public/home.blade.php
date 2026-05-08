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
            <input id="productSearch" class="form-control form-control-sm product-search-input" placeholder="{{ __('app.search_product') }}">
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
        <button id="checkoutOpenBtn" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#checkoutModal">{{ __('app.checkout') }}</button>
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
(() => {
    const apiBase = '/api/v1';
    const storageKey = 'restaurant_cart_v1';

    const state = {
        settings: null,
        categories: [],
        products: [],
        deliveryZones: [],
        selectedRoot: null,
        selectedSub: null,
        cart: loadCart(),
        loading: {
            settings: true,
            categories: true,
            products: true,
            deliveryZones: true,
        },
        errors: {
            settings: null,
            categories: null,
            products: null,
            deliveryZones: null,
        },
    };

    const el = {
        globalError: document.getElementById('globalError'),
        globalErrorText: document.getElementById('globalErrorText'),
        retryLoadBtn: document.getElementById('retryLoadBtn'),

        restaurantNameNav: document.getElementById('restaurantNameNav'),
        statusTextNav: document.getElementById('statusTextNav'),
        appLogoMini: document.getElementById('appLogoMini'),
        navLogoFallback: document.getElementById('navLogoFallback'),

        heroLogo: document.getElementById('heroLogo'),
        heroLogoFallback: document.getElementById('heroLogoFallback'),
        heroName: document.getElementById('heroName'),
        openBadge: document.getElementById('openBadge'),
        todayHours: document.getElementById('todayHours'),
        deliveryNote: document.getElementById('deliveryNote'),
        phoneLink: document.getElementById('phoneLink'),
        phoneText: document.getElementById('phoneText'),
        whatsappLink: document.getElementById('whatsappLink'),
        whatsappText: document.getElementById('whatsappText'),

        rootSection: document.getElementById('rootSection'),
        subSection: document.getElementById('subSection'),
        featuredSection: document.getElementById('featuredSection'),
        productsSection: document.getElementById('productsSection'),

        rootCategoriesState: document.getElementById('rootCategoriesState'),
        rootCategories: document.getElementById('rootCategories'),
        subCategoriesState: document.getElementById('subCategoriesState'),
        subCategories: document.getElementById('subCategories'),

        featuredState: document.getElementById('featuredState'),
        featuredProducts: document.getElementById('featuredProducts'),

        productsState: document.getElementById('productsState'),
        categoryProducts: document.getElementById('categoryProducts'),
        productSearch: document.getElementById('productSearch'),

        desktopCartBtn: document.getElementById('desktopCartBtn'),
        mobileCartBtn: document.getElementById('mobileCartBtn'),
        cartCountTop: document.getElementById('cartCountTop'),
        cartCountMobile: document.getElementById('cartCountMobile'),

        cartItems: document.getElementById('cartItems'),
        cartSubtotal: document.getElementById('cartSubtotal'),

        productModalTitle: document.getElementById('productModalTitle'),
        productModalImageWrap: document.getElementById('productModalImageWrap'),
        productModalImage: document.getElementById('productModalImage'),
        productModalDesc: document.getElementById('productModalDesc'),
        productModalPrice: document.getElementById('productModalPrice'),
        productModalOldPrice: document.getElementById('productModalOldPrice'),
        productModalUnavailable: document.getElementById('productModalUnavailable'),
        productModalAddBtn: document.getElementById('productModalAddBtn'),

        checkoutForm: document.getElementById('checkoutForm'),
        checkoutAlert: document.getElementById('checkoutAlert'),
        checkoutApiErrors: document.getElementById('checkoutApiErrors'),
        submitOrderBtn: document.getElementById('submitOrderBtn'),

        orderType: document.getElementById('orderType'),
        deliveryType: document.getElementById('deliveryType'),
        tableFields: document.getElementById('tableFields'),
        deliveryFields: document.getElementById('deliveryFields'),
        scheduledField: document.getElementById('scheduledField'),
        deliveryZone: document.getElementById('deliveryZone'),

        orderSuccessNumber: document.getElementById('orderSuccessNumber'),
    };

    let activeProduct = null;

    init();

    async function init() {
        wireEvents();
        renderLoadingSkeletons();
        await loadAll();
        renderAll();
    }

    function wireEvents() {
        el.retryLoadBtn.addEventListener('click', async () => {
            await loadAll();
            renderAll();
        });

        el.productSearch.addEventListener('input', renderProducts);

        el.orderType.addEventListener('change', handleOrderTypeUI);
        el.deliveryType.addEventListener('change', handleDeliveryTypeUI);

        el.checkoutForm.addEventListener('submit', submitOrder);

        el.productModalAddBtn.addEventListener('click', () => {
            if (!activeProduct) return;
            addToCart(activeProduct);
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
        });
    }

    async function loadAll() {
        resetErrors();

        await Promise.allSettled([
            fetchSettings(),
            fetchCategories(),
            fetchProducts(),
            fetchDeliveryZones(),
        ]);

        const failures = Object.values(state.errors).filter(Boolean);
        el.globalError.classList.toggle('d-none', failures.length === 0);

        if (failures.length) {
            el.globalErrorText.textContent = window.i18n.failed_loading_partial;
        }

        // Keep page browsable even with partial failures.
        state.loading.settings = false;
        state.loading.categories = false;
        state.loading.products = false;
        state.loading.deliveryZones = false;
    }

    async function fetchJSON(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
            ...options,
        });

        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
            const err = new Error(body.message || 'Request failed');
            err.errors = body.errors || null;
            throw err;
        }

        return body;
    }

    function normalizeData(payload) {
        const data = payload && payload.success !== undefined ? payload.data : payload;
        if (data && Array.isArray(data.data)) return data.data;
        return data;
    }

    function normalizeMaybeEscapedText(value, fallback = '') {
        if (value === null || value === undefined) return fallback;
        if (typeof value !== 'string') return String(value);

        const raw = value.trim();
        if (raw === '') return fallback;

        try {
            const decodedJson = JSON.parse(raw);
            if (typeof decodedJson === 'string') {
                return decodedJson;
            }
        } catch (_) {}

        if (raw.includes('\\u')) {
            try {
                const wrapped = JSON.parse('"' + raw.replace(/"/g, '\\"') + '"');
                if (typeof wrapped === 'string') return wrapped;
            } catch (_) {}
        }

        return raw;
    }

    async function fetchSettings() {
        try {
            const payload = await fetchJSON(`${apiBase}/settings/public`);
            const settings = normalizeData(payload) || {};
            settings.restaurant_name = normalizeMaybeEscapedText(settings.restaurant_name, 'Restaurant');
            // Temporary debug helper for settings sync checks.
            console.info('settings/public payload', settings);
            state.settings = settings;
        } catch (err) {
            state.errors.settings = err.message;
            state.settings = {
                restaurant_name: 'Restaurant',
                restaurant_logo: null,
                is_open_now: false,
                opening_hours: {},
            };
        }
    }

    async function fetchCategories() {
        try {
            const payload = await fetchJSON(`${apiBase}/categories`);
            state.categories = normalizeData(payload) || [];
            if (!state.selectedRoot && state.categories.length) {
                const roots = state.categories.filter(c => c.parent_id === null);
                state.selectedRoot = roots.length ? roots[0].id : state.categories[0].id;
            }
        } catch (err) {
            state.errors.categories = err.message;
            state.categories = [];
        }
    }

    async function fetchProducts() {
        try {
            const payload = await fetchJSON(`${apiBase}/products`);
            state.products = normalizeData(payload) || [];
        } catch (err) {
            state.errors.products = err.message;
            state.products = [];
        }
    }

    async function fetchDeliveryZones() {
        try {
            const payload = await fetchJSON(`${apiBase}/delivery-zones`);
            state.deliveryZones = normalizeData(payload) || [];
        } catch (err) {
            state.errors.deliveryZones = err.message;
            state.deliveryZones = [];
        }
    }

    function resetErrors() {
        Object.keys(state.errors).forEach(key => {
            state.errors[key] = null;
        });
    }

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
        el.featuredState.innerHTML = skeletonRow(4);
        el.productsState.innerHTML = skeletonRow(8);
    }

    function renderAppHeader() {
        const s = state.settings || {};
        const name = normalizeMaybeEscapedText(s.restaurant_name, 'Restaurant');

        el.restaurantNameNav.textContent = name;
        el.heroName.textContent = name;

        setLogoWithFallback(el.appLogoMini, el.navLogoFallback, s.restaurant_logo);
        setLogoWithFallback(el.heroLogo, el.heroLogoFallback, s.restaurant_logo);

        const isAccepting = s.is_accepting_orders !== false;
        const isOpen = !!s.is_open_now;
        const effectivelyOpen = isAccepting && isOpen;

        el.openBadge.className = `badge ${effectivelyOpen ? 'text-bg-success' : 'text-bg-danger'}`;
        el.openBadge.textContent = effectivelyOpen ? window.i18n.open_for_orders : window.i18n.closed_now;
        el.statusTextNav.textContent = effectivelyOpen ? window.i18n.accepting_orders_now : window.i18n.browsing_available;

        el.todayHours.textContent = getTodayHoursSummary(s.opening_hours || {});
        el.deliveryNote.textContent = s.delivery_note || '';

        if (s.restaurant_phone) {
            el.phoneText.textContent = s.restaurant_phone;
            el.phoneLink.href = `tel:${s.restaurant_phone}`;
            el.phoneLink.classList.remove('d-none');
        } else {
            el.phoneLink.classList.add('d-none');
        }

        if (s.restaurant_whatsapp) {
            const phone = s.restaurant_whatsapp.replace(/\D/g, '');
            el.whatsappText.textContent = s.restaurant_whatsapp;
            el.whatsappLink.href = `https://wa.me/${phone}`;
            el.whatsappLink.classList.remove('d-none');
        } else {
            el.whatsappLink.classList.add('d-none');
        }
    }

    function getTodayHoursSummary(openingHours) {
        const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const todayKey = days[new Date().getDay()];
        const today = openingHours[todayKey];

        if (!today) return window.i18n.hours_not_set;
        if (!today.is_open) return window.i18n.closed_today;
        if (!today.from || !today.to) return window.i18n.open_today;

        return `${window.i18n.today_hours}: ${today.from} - ${today.to}`;
    }

    function setLogoWithFallback(imgNode, fallbackNode, src) {
        if (!src) {
            imgNode.classList.add('d-none');
            fallbackNode.classList.remove('d-none');
            return;
        }

        imgNode.onload = () => {
            imgNode.classList.remove('d-none');
            fallbackNode.classList.add('d-none');
        };

        imgNode.onerror = () => {
            imgNode.classList.add('d-none');
            fallbackNode.classList.remove('d-none');
        };

        imgNode.src = src;
    }

    function renderCategories() {
        if (state.errors.categories) {
            el.rootCategoriesState.innerHTML = errorState(window.i18n.failed_categories);
            el.rootCategories.innerHTML = '';
            return;
        }

        const roots = state.categories.filter(c => c.parent_id === null);

        if (!roots.length) {
            el.rootCategoriesState.innerHTML = emptyState(window.i18n.no_categories_added);
            el.rootCategories.innerHTML = '';
            return;
        }

        el.rootCategoriesState.innerHTML = '';

        el.rootCategories.innerHTML = roots.map(c => `
            <button class="btn btn-sm main-chip cat-chip ${state.selectedRoot === c.id ? 'active' : ''}" data-root-id="${c.id}">
                ${c.image ? `<img src="${c.image}" class="chip-thumb me-1 js-chip-thumb" alt="">` : ''}
                ${escapeHtml(c.name)}
            </button>
        `).join('');

        bindChipImageFallbacks(el.rootCategories);

        el.rootCategories.querySelectorAll('[data-root-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                state.selectedRoot = Number(btn.dataset.rootId);
                state.selectedSub = null;
                renderCategories();
                renderSubCategories();
                renderProducts();
            });
        });
    }

    function renderSubCategories() {
        if (!state.selectedRoot || !state.categories.length) {
            el.subSection.classList.add('d-none');
            el.subCategoriesState.innerHTML = '';
            el.subCategories.innerHTML = '';
            return;
        }

        const subs = state.categories.filter(c => c.parent_id === state.selectedRoot);

        if (!subs.length) {
            el.subSection.classList.add('d-none');
            el.subCategoriesState.innerHTML = `<small class="text-muted">${window.i18n.no_subcategories_section}</small>`;
            el.subCategories.innerHTML = '';
            return;
        }

        el.subSection.classList.remove('d-none');
        el.subCategoriesState.innerHTML = '';
        el.subCategories.innerHTML = `
            <button class="btn btn-sm btn-outline-secondary cat-chip sub-chip ${state.selectedSub === null ? 'active' : ''}" data-sub-id="all">${window.i18n.all}</button>
            ${subs.map(c => `
                <button class="btn btn-sm btn-outline-secondary cat-chip sub-chip ${state.selectedSub === c.id ? 'active' : ''}" data-sub-id="${c.id}">
                    ${escapeHtml(c.name)}
                </button>
            `).join('')}
        `;

        el.subCategories.querySelectorAll('[data-sub-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                state.selectedSub = btn.dataset.subId === 'all' ? null : Number(btn.dataset.subId);
                renderSubCategories();
                renderProducts();
            });
        });
    }

    function renderFeatured() {
        if (state.errors.products) {
            el.featuredSection.classList.remove('d-none');
            el.featuredState.innerHTML = errorState(window.i18n.failed_featured);
            el.featuredProducts.innerHTML = '';
            return;
        }

        const featured = state.products.filter(p => !!p.is_featured).slice(0, 8);

        if (!featured.length) {
            el.featuredSection.classList.add('d-none');
            el.featuredState.innerHTML = '';
            el.featuredProducts.innerHTML = '';
            return;
        }

        el.featuredSection.classList.remove('d-none');
        el.featuredState.innerHTML = '';
        el.featuredProducts.innerHTML = featured.map(productCard).join('');
        bindProductActions(el.featuredProducts);
    }

    function renderProducts() {
        if (state.errors.products) {
            el.productsState.innerHTML = errorState(window.i18n.failed_products);
            el.categoryProducts.innerHTML = '';
            return;
        }

        let products = [...state.products];

        if (state.selectedSub) {
            products = products.filter(p => Number(p.category_id) === Number(state.selectedSub));
        } else if (state.selectedRoot) {
            const childIds = state.categories.filter(c => c.parent_id === state.selectedRoot).map(c => c.id);
            const allowed = new Set([state.selectedRoot, ...childIds]);
            products = products.filter(p => allowed.has(Number(p.category_id)));
        }

        const search = el.productSearch.value.trim().toLowerCase();
        if (search) {
            products = products.filter(p => (p.name || '').toLowerCase().includes(search));
        }

        if (!products.length) {
            el.productsState.innerHTML = `<div class="empty-state d-flex align-items-center gap-2"><i class="bi bi-emoji-neutral"></i><span>${window.i18n.no_products_now}</span></div>`;
            el.categoryProducts.innerHTML = '';
            return;
        }

        el.productsState.innerHTML = '';
        el.categoryProducts.innerHTML = products.map(productCard).join('');
        bindProductImageFallbacks(el.categoryProducts);
        bindProductActions(el.categoryProducts);
    }

    function productCard(p) {
        const available = !!p.is_available;
        const desc = (p.description_ar || p.description_en || '').trim();
        const shortDesc = desc.length > 90 ? `${desc.slice(0, 90)}...` : desc;

        const oldPrice = p.discount_price
            ? `<span class="price-old me-1">${num(p.price)}</span>`
            : '';

        const imageBlock = p.image
            ? `<img src="${p.image}" alt="${escapeHtml(p.name)}" class="js-product-img">`
            : `<div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="bi bi-image"></i></div>`;

        return `
            <div class="col">
                <article class="product-card h-100 ${available ? '' : 'product-unavailable'}">
                    <div class="product-img-wrap">${imageBlock}</div>
                    <div class="p-3 d-flex flex-column h-100">
                        <h3 class="h6 mb-1">${escapeHtml(p.name)}</h3>
                        <p class="text-muted small mb-2">${escapeHtml(shortDesc || window.i18n.no_description)}</p>

                        <div class="mb-2">
                            ${oldPrice}
                            <span class="price-now">${num(p.effective_price)}</span>
                        </div>

                        ${available ? '' : `<span class="badge text-bg-secondary mb-2">${window.i18n.unavailable}</span>`}

                        <div class="mt-auto d-grid gap-2">
                            <button class="btn btn-outline-secondary btn-sm" data-show-product="${p.id}">${window.i18n.details}</button>
                            <button class="btn btn-primary btn-sm" data-add-product="${p.id}" ${available ? '' : 'disabled'}>
                                <i class="bi bi-plus-circle me-1"></i>${window.i18n.add_to_cart}
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        `;
    }

    function bindProductActions(container) {
        container.querySelectorAll('[data-show-product]').forEach(btn => {
            btn.addEventListener('click', () => openProductModal(Number(btn.dataset.showProduct)));
        });

        container.querySelectorAll('[data-add-product]').forEach(btn => {
            btn.addEventListener('click', () => {
                const product = findProduct(Number(btn.dataset.addProduct));
                if (!product) return;
                addToCart(product);
            });
        });
    }

    function bindChipImageFallbacks(container) {
        container.querySelectorAll('.js-chip-thumb').forEach(img => {
            img.addEventListener('error', () => {
                img.remove();
            }, { once: true });
        });
    }

    function bindProductImageFallbacks(container) {
        container.querySelectorAll('.js-product-img').forEach(img => {
            img.addEventListener('error', () => {
                const wrap = img.closest('.product-img-wrap');
                if (!wrap) return;
                wrap.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="bi bi-image"></i></div>';
            }, { once: true });
        });
    }

    function openProductModal(productId) {
        const product = findProduct(productId);
        if (!product) return;

        activeProduct = product;
        el.productModalTitle.textContent = product.name;

        const desc = (product.description_ar || product.description_en || '').trim();
        el.productModalDesc.textContent = desc || window.i18n.no_description_detail;

        if (product.image) {
            el.productModalImage.onerror = () => {
                el.productModalImageWrap.classList.add('d-none');
            };
            el.productModalImage.src = product.image;
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

        new bootstrap.Modal(document.getElementById('productModal')).show();
    }

    function addToCart(product) {
        if (!product.is_available) {
            showCheckoutAlert('warning', window.i18n.product_unavailable_alert);
            return;
        }

        const existing = state.cart.find(i => i.product_id === product.id);

        if (existing) {
            existing.quantity += 1;
        } else {
            state.cart.push({
                product_id: product.id,
                product_name: product.name,
                product_price: Number(product.effective_price),
                quantity: 1,
            });
        }

        persistCart();
        renderCart();
    }

    function renderCart() {
        const count = state.cart.reduce((sum, item) => sum + item.quantity, 0);
        el.cartCountTop.textContent = count;
        el.cartCountMobile.textContent = count;

        if (!state.cart.length) {
            el.cartItems.innerHTML = `<div class="cart-empty">${window.i18n.cart_is_empty}</div>`;
            el.cartSubtotal.textContent = '0.00';
            return;
        }

        el.cartItems.innerHTML = state.cart.map(item => {
            const product = findProduct(item.product_id);
            const isAvailableNow = product ? !!product.is_available : false;

            return `
                <div class="border rounded p-2 mb-2 bg-white">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold small">${escapeHtml(item.product_name)}</div>
                            <div class="small text-muted">${num(item.product_price)} × ${item.quantity}</div>
                            ${isAvailableNow ? '' : `<span class="badge text-bg-secondary mt-1">${window.i18n.not_available_now}</span>`}
                        </div>
                        <button class="btn btn-sm btn-link text-danger" data-cart-remove="${item.product_id}" title="${window.i18n.delete}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary qty-btn" data-cart-minus="${item.product_id}">-</button>
                            <button class="btn btn-outline-secondary" disabled>${item.quantity}</button>
                            <button class="btn btn-outline-secondary qty-btn" data-cart-plus="${item.product_id}">+</button>
                        </div>
                        <strong>${num(item.product_price * item.quantity)}</strong>
                    </div>
                </div>
            `;
        }).join('');

        el.cartItems.querySelectorAll('[data-cart-plus]').forEach(btn => {
            btn.addEventListener('click', () => changeQty(Number(btn.dataset.cartPlus), 1));
        });

        el.cartItems.querySelectorAll('[data-cart-minus]').forEach(btn => {
            btn.addEventListener('click', () => changeQty(Number(btn.dataset.cartMinus), -1));
        });

        el.cartItems.querySelectorAll('[data-cart-remove]').forEach(btn => {
            btn.addEventListener('click', () => removeFromCart(Number(btn.dataset.cartRemove)));
        });

        el.cartSubtotal.textContent = num(calcSubtotal());
    }

    function changeQty(productId, delta) {
        const index = state.cart.findIndex(i => i.product_id === productId);
        if (index < 0) return;

        state.cart[index].quantity += delta;

        if (state.cart[index].quantity <= 0) {
            state.cart.splice(index, 1);
        }

        persistCart();
        renderCart();
    }

    function removeFromCart(productId) {
        state.cart = state.cart.filter(i => i.product_id !== productId);
        persistCart();
        renderCart();
    }

    function renderDeliveryZones() {
        if (!state.deliveryZones.length) {
            el.deliveryZone.innerHTML = `<option value="">${window.i18n.no_delivery_zones_available}</option>`;
            return;
        }

        el.deliveryZone.innerHTML = `<option value="">${window.i18n.select_zone}</option>` + state.deliveryZones.map(zone =>
            `<option value="${zone.estimated_fee}">${escapeHtml(zone.area_name)} (${num(zone.estimated_fee)})</option>`
        ).join('');
    }

    function handleOrderTypeUI() {
        const type = el.orderType.value;

        toggleField(el.tableFields, type === 'table');
        toggleField(el.deliveryFields, type === 'delivery');
    }

    function handleDeliveryTypeUI() {
        const type = el.deliveryType.value;
        toggleField(el.scheduledField, type === 'scheduled');
    }

    function toggleField(node, visible) {
        node.classList.toggle('checkout-hidden', !visible);
        node.classList.toggle('checkout-visible', visible);
    }

    function validateCartBeforeCheckout() {
        const unavailable = state.cart.filter(item => {
            const p = findProduct(item.product_id);
            return !p || !p.is_available;
        });

        if (!unavailable.length) {
            return true;
        }

        const names = unavailable.map(i => i.product_name).join('، ');
        showCheckoutAlert('danger', `${window.i18n.some_products_unavailable} ${names}`);
        return false;
    }

    async function submitOrder(event) {
        event.preventDefault();
        el.checkoutApiErrors.innerHTML = '';

        if (!state.cart.length) {
            showCheckoutAlert('danger', window.i18n.cart_is_empty);
            return;
        }

        if (!validateCartBeforeCheckout()) {
            return;
        }

        const formData = new FormData(el.checkoutForm);
        const payload = {
            customer_name: String(formData.get('customer_name') || '').trim(),
            customer_phone: String(formData.get('customer_phone') || '').trim(),
            order_type: formData.get('order_type'),
            customer_note: String(formData.get('customer_note') || '').trim() || null,
            items: state.cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
            })),
        };

        if (payload.order_type === 'table') {
            payload.table_number = String(formData.get('table_number') || '').trim();
        }

        if (payload.order_type === 'delivery') {
            payload.address = String(formData.get('address') || '').trim();
            payload.delivery_type = formData.get('delivery_type') || 'immediate';

            if (payload.delivery_type === 'scheduled') {
                payload.scheduled_at = formData.get('scheduled_at');
            }

            if (el.deliveryZone.value) {
                payload.estimated_delivery_fee = Number(el.deliveryZone.value);
            }
        }

        el.submitOrderBtn.disabled = true;
        el.submitOrderBtn.textContent = window.i18n.sending;

        try {
            const response = await fetchJSON(`${apiBase}/orders`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            const data = normalizeData(response) || {};
            const orderNumber = data.order_number || '---';

            state.cart = [];
            persistCart();
            renderCart();

            el.checkoutForm.reset();
            handleOrderTypeUI();
            handleDeliveryTypeUI();
            showCheckoutAlert('success', `${window.i18n.order_sent_success} ${orderNumber}`);

            const checkoutModal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
            const cartCanvas = bootstrap.Offcanvas.getInstance(document.getElementById('cartCanvas'));

            if (checkoutModal) checkoutModal.hide();
            if (cartCanvas) cartCanvas.hide();

            el.orderSuccessNumber.textContent = `#${orderNumber}`;
            new bootstrap.Modal(document.getElementById('orderSuccessModal')).show();

        } catch (error) {
            showCheckoutAlert('danger', error.message || window.i18n.failed_submit_order);

            if (error.errors && typeof error.errors === 'object') {
                el.checkoutApiErrors.innerHTML = Object.values(error.errors)
                    .flat()
                    .map(msg => `<div>• ${escapeHtml(msg)}</div>`)
                    .join('');
            }
        } finally {
            el.submitOrderBtn.disabled = false;
            el.submitOrderBtn.textContent = window.i18n.submit_order;
        }
    }

    function showCheckoutAlert(type, message) {
        el.checkoutAlert.className = `alert alert-${type}`;
        el.checkoutAlert.textContent = message;
        el.checkoutAlert.classList.remove('d-none');
    }

    function findProduct(productId) {
        return state.products.find(product => Number(product.id) === Number(productId));
    }

    function calcSubtotal() {
        return state.cart.reduce((sum, item) => sum + (item.product_price * item.quantity), 0);
    }

    function persistCart() {
        localStorage.setItem(storageKey, JSON.stringify(state.cart));
    }

    function loadCart() {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || '[]');
        } catch (_) {
            return [];
        }
    }

    function num(value) {
        return Number(value || 0).toFixed(2);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function skeletonRow(count) {
        return `<div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">${
            Array.from({ length: count }).map(() => '<div class="col"><div class="skeleton skeleton-card"></div></div>').join('')
        }</div>`;
    }

    function emptyState(text) {
        return `<div class="empty-state">${escapeHtml(text)}</div>`;
    }

    function errorState(text) {
        return `<div class="error-state text-danger">${escapeHtml(text)}</div>`;
    }
})();
</script>
</body>
</html>

