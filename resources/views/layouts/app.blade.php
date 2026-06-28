<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App::isLocale('ar') || (session('site_direction') == 'rtl') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $config = new (\App\Services\SystemConfigService::class);
        $lang = str_replace('_', '-', app()->getLocale());
    @endphp
    <title>{{ $config->getFirstText(
            ['restaurant_name_'.$lang, 'restaurant_name', 'site_name'],
            config('app.name', '')
        ) }} - @yield('title')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- RTL Bootstrap CSS (conditionally loaded) -->
    @if(App::isLocale('ar') || (session('site_direction') == 'rtl'))
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    @endif

    <!-- Main layout: sidebar + content offsets -->
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --background: #ffffff;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --sidebar-bg: #f8fafc;
            --sidebar-hover: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        [data-bs-theme="dark"] {
            --background: #0f172a;
            --card-bg: #1e293b;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.2);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
        }

        /* Rest of your CSS remains unchanged */
    </style>

    <!-- Additional styles for language selector -->
    <style>
        .language-selector .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .language-selector .dropdown-item img {
            width: 20px;
            height: 14px;
            object-fit: cover;
        }

        .language-selector .dropdown-toggle::after {
            margin-left: 0.5em;
        }

        html[dir="rtl"] .language-selector .dropdown-toggle::after {
            margin-left: 0;
            margin-right: 0.5em;
        }

        .current-language-flag {
            width: 20px;
            height: 14px;
            object-fit: cover;
            margin-right: 5px;
        }

        html[dir="rtl"] .current-language-flag {
            margin-right: 0;
            margin-left: 5px;
        }

        /* RTL specific adjustments */
        html[dir="rtl"] .me-2, html[dir="rtl"] .me-3 {
            margin-right: 0 !important;
        }

        html[dir="rtl"] .me-2 {
            margin-left: 0.5rem !important;
        }

        html[dir="rtl"] .me-3 {
            margin-left: 1rem !important;
        }

        html[dir="rtl"] .ms-3 {
            margin-left: 0 !important;
            margin-right: 1rem !important;
        }
    </style>

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <style>
        /* ── Sidebar ──────────────────────────────────────────────────────────── */
        .sidebar {
            width: 280px;
            height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            background-color: var(--sidebar-bg);
            padding: 0;
            box-shadow: 2px 0 15px rgba(0,0,0,.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        html[dir="rtl"] .sidebar {
            left: auto;
            right: 0;
            box-shadow: -2px 0 15px rgba(0,0,0,.1);
        }

        /* ── Main content ─────────────────────────────────────────────────────── */
        .main-content {
            margin-left: 280px;
            margin-top: 56px;
            padding: 30px;
            transition: all 0.3s ease;
        }
        html[dir="rtl"] .main-content {
            margin-left: 0;
            margin-right: 280px;
        }
        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding: 16px;
                /* Clear the bottom navigation bar */
                padding-bottom: 80px;
            }
        }

        /* ── Avatar ───────────────────────────────────────────────────────────── */
        .avatar-circle {
            width: 80px;
            height: 80px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Sidebar nav links ────────────────────────────────────────────────── */
        .sidebar .nav-link {
            color: var(--text);
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover:not(.active) { background-color: var(--sidebar-hover); }
        .sidebar-heading {
            letter-spacing: 1px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .sidebar .text-muted { color: var(--text-muted) !important; }

        .sidebar-item button {
            text-align: left;
            font-size: 1rem;
            color: var(--text);
        }
        .sidebar-item button:hover:not(.active) { background-color: var(--sidebar-hover); }
        .sidebar-item button:focus { outline: none; box-shadow: none; }

        .sidebar .nav-link.active,
        .sidebar-item button.active { background-color: var(--primary) !important; }

        .sidebar .nav-link.active span,
        .sidebar .nav-link.active i,
        .sidebar-item button.active span,
        .sidebar-item button.active i,
        .sidebar-item .collapse .nav-link.active span,
        .sidebar-item .collapse .nav-link.active i { color: #ffffff !important; }

        .sidebar-item button span,
        .sidebar-item .nav-link span { color: var(--text); }

        /* ── Dark mode dropdown ───────────────────────────────────────────────── */
        [data-bs-theme="dark"] .dropdown-menu {
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }
        [data-bs-theme="dark"] .dropdown-item { color: var(--text); }
        [data-bs-theme="dark"] .dropdown-item:hover { background-color: var(--sidebar-hover); }
        [data-bs-theme="dark"] .dropdown-divider { border-color: var(--border-color); }

        /* ── Bottom navigation bar ────────────────────────────────────────────── */
        .bottom-nav-item {
            color: var(--text-muted);
            font-size: 10px;
            cursor: pointer;
            transition: color 0.15s;
        }
        .bottom-nav-item:hover,
        .bottom-nav-active { color: var(--primary) !important; }

        /* ── Bootstrap offcanvas overrides ───────────────────────────────────── */
        @media (max-width: 767.98px) {
            #adminSidebar {
                top: 56px !important;
                bottom: auto !important;
                height: calc(100vh - 56px) !important;
                left: 0 !important;
                right: auto !important;
                width: 280px !important;
                z-index: 1045 !important;
                background-color: var(--sidebar-bg) !important;
            }
            html[dir="rtl"] #adminSidebar {
                right: 0 !important;
                left: auto !important;
            }
        }
        @media (min-width: 768px) {
            #adminSidebar {
                position: fixed !important;
                top: 56px !important;
                bottom: auto !important;
                height: calc(100vh - 56px) !important;
                width: 280px !important;
                background-color: var(--sidebar-bg) !important;
                transform: none !important;
                visibility: visible !important;
                left: 0 !important;
                right: auto !important;
            }
            html[dir="rtl"] #adminSidebar {
                right: 0 !important;
                left: auto !important;
            }
        }
    </style>

    @stack('styles')

    {{-- Feature flags for admin-side JS (resolved booleans, admin context). --}}
    <script>window.features = @json(\App\Support\Feature::all());</script>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg shadow-sm fixed-top" style="background-color: var(--card-bg); border-bottom: 1px solid var(--border-color);">
        <div class="container-fluid">
            @php
                $navLocale  = strtolower(session('locale', config('app.locale', 'ar')));
                $navLogo    = \App\Models\SystemConfig::get('restaurant_logo');
                $navNameAr  = \App\Models\SystemConfig::get('restaurant_name_ar') ?: \App\Models\SystemConfig::get('site_name') ?: config('app.name');
                $navNameEn  = \App\Models\SystemConfig::get('restaurant_name_en', '');
                $navName    = $navLocale === 'en'
                    ? ($navNameEn ?: $navNameAr)
                    : $navNameAr;
            @endphp
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                @if($navLogo)
                    <img src="{{ asset('storage/' . ltrim($navLogo, '/')) }}"
                         alt="{{ $navName }}"
                         style="width:36px;height:36px;object-fit:contain;border-radius:8px;"
                         onerror="this.style.display='none';document.getElementById('navBrandIcon').style.display='flex'">
                    <div id="navBrandIcon" style="display:none;width:36px;height:36px;border-radius:50%;background-color:var(--primary);color:#fff;align-items:center;justify-content:center;">
                        <i class="bi bi-shop"></i>
                    </div>
                @else
                    <div style="width:36px;height:36px;border-radius:50%;background-color:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-shop"></i>
                    </div>
                @endif
                <span class="fw-bold d-none d-sm-inline" style="color: var(--text);">{{ $navName }}</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                <!-- Language Selector -->
                @feature('localization.languages')
                @php
                    $languages = \App\Models\Language::query()->where('status', 1)->get();
                    $currentLocale = session('locale', config('app.locale'));
                    $currentLanguage = $languages->where('code', $currentLocale)->first();
                @endphp

                @if($languages->count() > 0)
                <div class="dropdown language-selector">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if($currentLanguage && $currentLanguage->flag_path)
                            <img src="{{ asset('storage/' . $currentLanguage->flag_path) }}" alt="{{ $currentLanguage->name }}" class="current-language-flag">
                        @endif
                        {{ $currentLanguage ? $currentLanguage->name : \App\Helpers\Helpers::translate('english') }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach($languages as $language)
                            <li>
                                <a class="dropdown-item {{ $currentLocale == $language->code ? 'active' : '' }}"
                                   href="{{ route('language.change', $language->code) }}">
                                    @if($language->flag_path)
                                        <img src="{{ asset('storage/' . $language->flag_path) }}" alt="{{ $language->name }}">
                                    @endif
                                    {{ $language->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @endfeature

                @auth
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--text);">
                        @if(Auth::user()->profile_picture)
                            <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center rounded-circle"
                                 style="width: 32px; height: 32px; background-color: {{ '#' . substr(md5(Auth::user()->email), 0, 6) }}; color: white;">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 200px; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                        <li><h6 class="dropdown-header">{{ \App\Helpers\Helpers::translate('signed_in_as') }}</h6></li>
                        <li><span class="dropdown-item-text fw-medium">{{ Auth::user()->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}"><i class="bi bi-person-gear me-2"></i>{{ \App\Helpers\Helpers::translate('profile') }}</a></li>
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form-nav').submit();"><i class="bi bi-box-arrow-right me-2"></i>{{ \App\Helpers\Helpers::translate('logout') }}</a></li>
                        <form id="logout-form-nav" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </ul>
                </div>
                @else
                <a href="{{ route('auth.login') }}" class="btn btn-outline-primary">{{ \App\Helpers\Helpers::translate('Login') }}</a>
                @endauth

                <button class="btn btn-icon" id="themeToggle" style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: var(--sidebar-hover);">
                    <i class="bi bi-moon-stars"></i>
                </button>

                @auth
                <button class="navbar-toggler border-0 d-md-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
                    <i class="bi bi-list" style="color: var(--text); font-size: 1.5rem;"></i>
                </button>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Global New Order Notification Banner -->
    @auth
    <div id="globalNewOrderBanner" class="alert alert-warning alert-dismissible fade d-none position-fixed"
         style="top: 56px; left: 50%; transform: translateX(-50%); z-index: 1031; width: min(92vw, 480px); box-shadow: 0 4px 12px rgba(0,0,0,.15); border-left: 5px solid #f59e0b;">
        <i class="bi bi-bell-fill me-2 text-warning"></i>
        <strong>{{ __('app.new_order') ?? 'طلب جديد!' }}</strong> {{ __('app.new_order_arrived') ?? 'وصل طلب جديد.' }}
        <button id="globalRefreshNowBtn" class="btn btn-sm btn-warning ms-2">
            <i class="bi bi-arrow-clockwise me-1"></i>{{ __('app.refresh_now') ?? 'تحديث الآن' }}
        </button>
        <button id="globalMuteBtn" class="btn btn-sm btn-outline-secondary ms-1" title="Mute">
            <i class="bi bi-bell"></i>
        </button>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endauth

    <!-- Sidebar -->
    @auth
    {{-- offcanvas-start = left in LTR, right in RTL (Bootstrap RTL flips it automatically) --}}
    <div id="adminSidebar" class="sidebar offcanvas offcanvas-start" tabindex="-1" aria-label="Admin Sidebar">
        <!-- Mobile close button -->
        <div class="d-md-none text-end px-3 pt-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="d-flex flex-column gap-4">
            <!-- User Profile Section -->
            <div class="text-center py-4">
                @if(Auth::user()->profile_picture)
                    <div class="mx-auto mb-3">
                        <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="rounded-circle" style="width: 72px; height: 72px; object-fit: cover;">
                    </div>
                @else
                    <div class="avatar-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="background-color: {{ '#' . substr(md5(Auth::user()->email), 0, 6) }};">
                        <span style="font-size: 2rem; color: white;">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    </div>
                @endif
                <h6 class="mb-1" style="color: var(--text);">{{ Auth::user()->name }}</h6>
                <span class="text-muted small">{{ Auth::user()->email }}</span>
                @if(Auth::user()->role)
                    <div class="mt-1">
                        @if(Auth::user()->role === 'admin')
                            <span class="badge bg-danger">{{ \App\Helpers\Helpers::translate('Admin') }}</span>
                        @elseif(Auth::user()->role === 'moderator')
                            <span class="badge bg-warning text-dark">{{ \App\Helpers\Helpers::translate('Moderator') }}</span>
                        @else
                            <span class="badge bg-info">{{ \App\Helpers\Helpers::translate('User') }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Navigation -->
            <div class="nav-section">
                <p class="sidebar-heading text-uppercase text-muted small fw-bold ms-3 mb-2">{{ \App\Helpers\Helpers::translate('main') }}</p>
                <div class="nav flex-column">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-3"></i>
                        <span>{{ \App\Helpers\Helpers::translate('dashboard') }}</span>
                    </a>

                    <!-- Orders Link -->
                    <a href="{{ route('admin.orders.index') }}"
                       class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt me-3"></i>
                        <span>{{ \App\Helpers\Helpers::translate('orders') }}</span>
                        {{-- Pending badge --}}
                        @php $pendingCount = \App\Models\Order::where('status','pending')->count(); @endphp
                        @if($pendingCount)
                        <span class="badge bg-warning text-dark ms-auto">{{ $pendingCount }}</span>
                        @endif
                    </a>

                    <!-- Reports Link (admin only) -->
                    @if(Auth::user()->role === 'admin')
                    <a href="{{ route('admin.reports') }}"
                       class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line me-3"></i>
                        <span>{{ \App\Helpers\Helpers::translate('reports') }}</span>
                    </a>
                    @endif

                    <!-- Customers Link -->
                    <a href="{{ route('admin.customers.index') }}"
                       class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-3"></i>
                        <span>{{ \App\Helpers\Helpers::translate('customers') }}</span>
                    </a>

                    <!-- Categories Dropdown -->
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#categoriesCollapse" aria-expanded="{{ request()->routeIs('admin.categories.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-tags me-3" style="{{ request()->routeIs('admin.categories.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.categories.*') ? 'color: #ffffff !important;' : '' }}">{{ \App\Helpers\Helpers::translate('categories') }}</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.categories.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.categories.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.categories.*') ? 'show' : '' }}" id="categoriesCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.categories.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.categories.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('list') }}</span>
                                </a>
                                @if(Auth::user()->role === 'admin')
                                <a href="{{ route('admin.categories.create') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.categories.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('add_new') }}</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Zones Dropdown -->
                    @feature('core.delivery')
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.delivery-zones.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#deliveryZonesCollapse" aria-expanded="{{ request()->routeIs('admin.delivery-zones.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt me-3" style="{{ request()->routeIs('admin.delivery-zones.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.delivery-zones.*') ? 'color: #ffffff !important;' : '' }}">{{ \App\Helpers\Helpers::translate('delivery_zones') }}</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.delivery-zones.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.delivery-zones.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.delivery-zones.*') ? 'show' : '' }}" id="deliveryZonesCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.delivery-zones.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.delivery-zones.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('list') }}</span>
                                </a>
                                @if(Auth::user()->role === 'admin')
                                <a href="{{ route('admin.delivery-zones.create') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.delivery-zones.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('add_new') }}</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endfeature

                    <!-- Weights Dropdown -->
                    @feature('products.weight_products')
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.weights.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#weightsCollapse" aria-expanded="{{ request()->routeIs('admin.weights.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-speedometer2 me-3" style="{{ request()->routeIs('admin.weights.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.weights.*') ? 'color: #ffffff !important;' : '' }}">{{ \App\Helpers\Helpers::translate('weights') }}</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.weights.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.weights.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.weights.*') ? 'show' : '' }}" id="weightsCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.weights.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.weights.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('list') }}</span>
                                </a>
                                @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                                <a href="{{ route('admin.weights.create') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.weights.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('add_new') }}</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endfeature

                    <!-- Options Dropdown -->
                    @feature('products.options')
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.options.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#optionsCollapse" aria-expanded="{{ request()->routeIs('admin.options.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-sliders me-3" style="{{ request()->routeIs('admin.options.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.options.*') ? 'color: #ffffff !important;' : '' }}">خيارات المنتجات</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.options.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.options.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.options.*') ? 'show' : '' }}" id="optionsCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.options.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.options.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>القائمة</span>
                                </a>
                                @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                                <a href="{{ route('admin.options.create') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.options.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>إضافة خيار</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endfeature

                    <!-- Products Dropdown -->
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#productsCollapse" aria-expanded="{{ request()->routeIs('admin.products.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-box-seam me-3" style="{{ request()->routeIs('admin.products.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.products.*') ? 'color: #ffffff !important;' : '' }}">{{ \App\Helpers\Helpers::translate('products') }}</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.products.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.products.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.products.*') ? 'show' : '' }}" id="productsCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.products.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.products.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('list') }}</span>
                                </a>
                                @if(Auth::user()->role === 'admin')
                                <a href="{{ route('admin.products.create') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.products.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('add_new') }}</span>
                                </a>
                                <a href="{{ route('admin.products.import') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.products.import') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-excel me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('import_products') }}</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Users Dropdown - Only for Admins -->
                    @if(Auth::user()->role === 'admin')
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#usersCollapse" aria-expanded="{{ request()->routeIs('admin.users.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people me-3" style="{{ request()->routeIs('admin.users.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.users.*') ? 'color: #ffffff !important;' : '' }}">{{ \App\Helpers\Helpers::translate('users') }}</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.users.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.users.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.users.*') ? 'show' : '' }}" id="usersCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.users.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('list') }}</span>
                                </a>
                                <a href="{{ route('admin.users.create') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('add_new') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Languages Dropdown -->
                    @feature('localization.languages')
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.languages.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#languagesCollapse" aria-expanded="{{ request()->routeIs('admin.languages.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-translate me-3" style="{{ request()->routeIs('admin.languages.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.languages.*') ? 'color: #ffffff !important;' : '' }}">{{ \App\Helpers\Helpers::translate('languages') }}</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.languages.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.languages.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.languages.*') ? 'show' : '' }}" id="languagesCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.languages.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.languages.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('list') }}</span>
                                </a>
                                @if(Auth::user()->role === 'admin')
                                <a href="{{ route('admin.languages.create') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.languages.create') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('add_new') }}</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endfeature

                    <!-- System Configs Dropdown - Only for Admins -->
                    @if(Auth::user()->role === 'admin')
                    <div class="sidebar-item mb-1">
                        <button class="nav-link d-flex align-items-center justify-content-between w-100 py-3 px-3 rounded-3 border-0 bg-transparent {{ request()->routeIs('admin.configs.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#configsCollapse" aria-expanded="{{ request()->routeIs('admin.configs.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-gear me-3" style="{{ request()->routeIs('admin.configs.*') ? 'color: #ffffff !important;' : '' }}"></i>
                                <span style="{{ request()->routeIs('admin.configs.*') ? 'color: #ffffff !important;' : '' }}">{{ \App\Helpers\Helpers::translate('system_configs') }}</span>
                            </div>
                            <i class="bi {{ request()->routeIs('admin.configs.*') ? 'bi-chevron-down' : 'bi-chevron-right' }}" style="{{ request()->routeIs('admin.configs.*') ? 'color: #ffffff !important;' : '' }}"></i>
                        </button>
                        <div class="collapse {{ request()->routeIs('admin.configs.*') ? 'show' : '' }}" id="configsCollapse">
                            <div class="nav flex-column ms-4 mt-1">
                                <a href="{{ route('admin.configs.index') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->routeIs('admin.configs.index') ? 'active' : '' }}">
                                    <i class="bi bi-list me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('all_configs') }}</span>
                                </a>
                                <a href="{{ route('admin.configs.group', 'restaurant') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->is('admin/configs/group/restaurant') ? 'active' : '' }}">
                                    <i class="bi bi-shop me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('restaurant') }}</span>
                                </a>
                                <a href="{{ route('admin.configs.group', 'general') }}" class="nav-link py-2 px-3 rounded-3 {{ request()->is('admin/configs/group/general') ? 'active' : '' }}">
                                    <i class="bi bi-sliders me-2"></i>
                                    <span>{{ \App\Helpers\Helpers::translate('general') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Log Viewer - Admin only -->
                    @if(Auth::user()->role === 'admin')
                    <a href="{{ url('admin/system-secure-metrics-health-logs') }}"
                       class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 {{ request()->is('admin/system-secure-metrics-health-logs*') ? 'active' : '' }}">
                        <i class="bi bi-file-text me-3"></i>
                        <span>سجلات النظام</span>
                    </a>
                    <a href="{{ route('admin.activity-logs.index') }}"
                       class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-history me-3"></i>
                        <span>{{ __('app.activity_log') }}</span>
                    </a>
                    <a href="{{ url('api/health') }}" target="_blank"
                       class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1">
                        <i class="bi bi-heart-pulse me-3"></i>
                        <span>{{ __('app.health_check') }}</span>
                    </a>
                    @endif
                </div>
            </div>

            <!-- Account Section -->
            <div class="nav-section mt-auto">
                <p class="sidebar-heading text-uppercase text-muted small fw-bold ms-3 mb-2">{{ \App\Helpers\Helpers::translate('account') }}</p>
                <div class="nav flex-column">
                    <a href="{{ route('admin.profile.edit') }}" class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                        <i class="bi bi-person-gear me-3"></i>
                        <span>{{ \App\Helpers\Helpers::translate('settings') }}</span>
                    </a>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-1 text-danger">
                        <i class="bi bi-box-arrow-right me-3"></i>
                        <span>{{ \App\Helpers\Helpers::translate('logout') }}</span>
                    </a>
                    <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <!-- Main Content -->
    <div class="main-content @guest ms-0 @endguest">
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize theme
            const themeToggle = document.getElementById('themeToggle');
            const htmlElement = document.documentElement;

            // Check for saved theme preference or use preferred color scheme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                htmlElement.setAttribute('data-bs-theme', savedTheme);
                updateThemeIcon(savedTheme);
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const initialTheme = prefersDark ? 'dark' : 'light';
                htmlElement.setAttribute('data-bs-theme', initialTheme);
                localStorage.setItem('theme', initialTheme);
                updateThemeIcon(initialTheme);
            }

            // Toggle theme when button is clicked
            themeToggle.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                htmlElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);

                // Apply theme to all elements that need it
                applyThemeToElements(newTheme);
            });

            function updateThemeIcon(theme) {
                const icon = themeToggle.querySelector('i');
                if (theme === 'dark') {
                    icon.classList.remove('bi-moon-stars');
                    icon.classList.add('bi-sun');
                } else {
                    icon.classList.remove('bi-sun');
                    icon.classList.add('bi-moon-stars');
                }
            }

            function applyThemeToElements(theme) {
                // This ensures all elements using CSS variables get updated
                document.documentElement.style.setProperty('--card-bg',
                    theme === 'dark' ? '#1e293b' : '#ffffff');
                document.documentElement.style.setProperty('--text',
                    theme === 'dark' ? '#f1f5f9' : '#1e293b');
                document.documentElement.style.setProperty('--text-muted',
                    theme === 'dark' ? '#94a3b8' : '#64748b');
                document.documentElement.style.setProperty('--border-color',
                    theme === 'dark' ? '#334155' : '#e2e8f0');
                document.documentElement.style.setProperty('--sidebar-bg',
                    theme === 'dark' ? '#1e293b' : '#f8fafc');
                document.documentElement.style.setProperty('--sidebar-hover',
                    theme === 'dark' ? '#334155' : '#f1f5f9');
            }

            // Apply theme on initial load
            applyThemeToElements(savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

            // Initialize animations for all page elements
            initializeAnimations();

            // Add click event listeners to all sidebar links to handle animations
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Don't intercept logout links or links with onclick handlers
                    if (this.getAttribute('href') === '#' || this.hasAttribute('onclick')) {
                        return;
                    }

                    // Store the URL we're navigating to
                    const targetUrl = this.getAttribute('href');

                    // Only intercept if it's an internal link
                    if (targetUrl && targetUrl.startsWith(window.location.origin) || targetUrl.startsWith('/')) {
                        e.preventDefault();

                        // Fade out current content
                        const mainContent = document.querySelector('.main-content');
                        mainContent.style.transition = 'opacity 0.2s ease-out';
                        mainContent.style.opacity = '0';

                        // Navigate after a short delay
                        setTimeout(() => {
                            window.location.href = targetUrl;
                        }, 200);
                    }
                });
            });
        });

        // Initialize animations for page elements
        function initializeAnimations() {
            // Apply animations to cards, alerts, and other elements
            const animatedElements = document.querySelectorAll('.card:not([data-no-anim]), .alert');
            animatedElements.forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    element.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100);
            });

            // Ensure main content is visible
            const mainContent = document.querySelector('.main-content');
            mainContent.style.opacity = '1';
        }

        // Re-initialize animations when navigating between pages
        document.addEventListener('turbolinks:load', function() {
            initializeAnimations();
        });

        // If not using Turbolinks, add this event listener for regular page loads
        window.addEventListener('pageshow', function(event) {
            // Check if the page is being loaded from cache
            if (event.persisted) {
                initializeAnimations();
            }
        });
    </script>

    <!-- Global New Orders Polling Script -->
    <script>
    @auth
    (function () {
        'use strict';

        const banner      = document.getElementById('globalNewOrderBanner');
        const refreshBtn  = document.getElementById('globalRefreshNowBtn');
        const pollUrl     = '{{ route("admin.orders.index", ["_poll" => 1]) }}';

        /* ── Load latestId from localStorage (persist across page refreshes) ────── */
        let latestId = parseInt(localStorage.getItem('admin_latest_order_id') || 0);

        /* ── Notification sound (public/sounds/notification.wav) ───────────── */
        const SOUND_URL = '{{ asset('sounds/notification.wav') }}';
        const notifAudio = new Audio(SOUND_URL);
        notifAudio.volume = 1.0;
        notifAudio.preload = 'auto';

        function beep() {
            if (localStorage.getItem('admin_sound_muted') === '1') return;
            try {
                navigator.vibrate && navigator.vibrate([200, 100, 200]);
                const s = notifAudio.cloneNode();
                s.volume = 1.0;
                s.play().catch(() => {});
                setTimeout(() => {
                    const s2 = notifAudio.cloneNode();
                    s2.volume = 1.0;
                    s2.play().catch(() => {});
                }, 1100);
            } catch (e) { /* ignore */ }
        }

        /* ── Mute toggle ─────────────────────────────────────────────────── */
        const muteBtn = document.getElementById('globalMuteBtn');
        function syncMuteBtn() {
            if (!muteBtn) return;
            const muted = localStorage.getItem('admin_sound_muted') === '1';
            muteBtn.querySelector('i').className = muted ? 'bi bi-bell-slash-fill' : 'bi bi-bell';
            muteBtn.classList.toggle('btn-danger', muted);
            muteBtn.classList.toggle('btn-outline-secondary', !muted);
        }
        syncMuteBtn();
        muteBtn && muteBtn.addEventListener('click', () => {
            const muted = localStorage.getItem('admin_sound_muted') === '1';
            localStorage.setItem('admin_sound_muted', muted ? '0' : '1');
            syncMuteBtn();
        });

        /* ── Poll for new orders ────────────────────────────────────────────── */
        async function pollNewOrders() {
            try {
                const res  = await fetch(pollUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();

                /* ── Update bottom nav pending badge live ────────────────────── */
                if (data.pending_count !== undefined) {
                    const bnBadge = document.getElementById('bottomNavBadge');
                    if (bnBadge) {
                        if (data.pending_count > 0) {
                            bnBadge.textContent = data.pending_count;
                            bnBadge.classList.remove('d-none');
                        } else {
                            bnBadge.classList.add('d-none');
                        }
                    }
                }

                if (data.latest_id > latestId) {
                    latestId = data.latest_id;
                    /* ── Save to localStorage so it persists across page refreshes ──── */
                    localStorage.setItem('admin_latest_order_id', latestId.toString());

                    beep();
                    banner.classList.remove('d-none');

                    let original = document.title;
                    let blink = 0;
                    const titleInterval = setInterval(() => {
                        document.title = (blink++ % 2 === 0) ? '🔔 {{ __("app.new_order") ?? "طلب جديد!" }}' : original;
                        if (blink > 10) { clearInterval(titleInterval); document.title = original; }
                    }, 600);
                }
            } catch (e) { /* network error — silent */ }
        }

        /* ── "تحديث الآن" button ─────────────────────────────────────────────── */
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                window.location.href = '{{ route("admin.orders.index") }}';
            });
        }

        /* ── Boot ────────────────────────────────────────────────────────────── */
        setInterval(pollNewOrders, 10000);
        setTimeout(pollNewOrders, 2000);
    })();
    @endauth
    </script>

    {{-- ── Bottom Navigation Bar (mobile only) ──────────────────────────── --}}
    @auth
    @php $bnPending = \App\Models\Order::query()->where('status','pending')->count(); @endphp
    <nav class="d-md-none position-fixed bottom-0 start-0 end-0" id="bottomNav"
         style="background:var(--card-bg);border-top:1px solid var(--border-color);z-index:599;">
        <div class="d-flex">
            <a href="{{ route('admin.dashboard') }}"
               class="bottom-nav-item flex-fill text-decoration-none py-2 text-center
                      {{ request()->routeIs('admin.dashboard') ? 'bottom-nav-active' : '' }}">
                <i class="bi bi-speedometer2 d-block" style="font-size:22px;line-height:1;"></i>
                <span style="font-size:10px;">{{ app()->isLocale('ar') ? 'الرئيسية' : 'Home' }}</span>
            </a>
            <a href="{{ route('admin.orders.index') }}"
               class="bottom-nav-item flex-fill text-decoration-none py-2 text-center position-relative
                      {{ request()->routeIs('admin.orders.*') ? 'bottom-nav-active' : '' }}">
                <i class="bi bi-receipt d-block" style="font-size:22px;line-height:1;"></i>
                <span style="font-size:10px;">{{ app()->isLocale('ar') ? 'الطلبات' : 'Orders' }}</span>
                <span id="bottomNavBadge"
                      class="badge bg-danger position-absolute {{ $bnPending > 0 ? '' : 'd-none' }}"
                      style="top:4px;{{ app()->isLocale('ar') ? 'left:calc(50% - 20px)' : 'left:calc(50% + 4px)' }};font-size:9px;min-width:16px;padding:2px 4px;">{{ $bnPending ?: '' }}</span>
            </a>
            <a href="{{ route('admin.products.index') }}"
               class="bottom-nav-item flex-fill text-decoration-none py-2 text-center
                      {{ request()->routeIs('admin.products.*', 'admin.categories.*') ? 'bottom-nav-active' : '' }}">
                <i class="bi bi-grid d-block" style="font-size:22px;line-height:1;"></i>
                <span style="font-size:10px;">{{ app()->isLocale('ar') ? 'القائمة' : 'Menu' }}</span>
            </a>
            <button type="button" id="bottomNavMore"
                    class="bottom-nav-item flex-fill py-2 text-center border-0 bg-transparent"
                    data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
                <i class="bi bi-grid-3x3-gap d-block" style="font-size:22px;line-height:1;"></i>
                <span style="font-size:10px;">{{ app()->isLocale('ar') ? 'المزيد' : 'More' }}</span>
            </button>
        </div>
    </nav>
    @endauth

    {{-- ── Toast container (bottom-right / bottom-left for RTL) ─────────── --}}
    <div id="appToastContainer" class="position-fixed"
         style="bottom:80px;{{ app()->isLocale('ar') ? 'left:1rem;' : 'right:1rem;' }}z-index:1100;"></div>

    <script>
    /* ── Toast helper ────────────────────────────────────────────────────── */
    function showToast(message, type) {
        const container = document.getElementById('appToastContainer');
        if (!container) return;
        const bg = type === 'success' ? 'bg-success text-white'
                 : type === 'warning' ? 'bg-warning text-dark'
                 : 'bg-danger text-white';
        const closeCls = type === 'warning' ? '' : 'btn-close-white';
        const delay = type === 'error' ? 6000 : 3000;
        const el = document.createElement('div');
        el.className = `toast align-items-center ${bg} border-0 mb-2`;
        el.setAttribute('role', 'alert');
        el.setAttribute('data-bs-autohide', 'true');
        el.setAttribute('data-bs-delay', delay);
        el.innerHTML = `<div class="d-flex"><div class="toast-body fw-semibold">${message}</div>
            <button type="button" class="btn-close ${closeCls} me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        container.prepend(el);
        new bootstrap.Toast(el).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    /* ── Auto-show session flash as toasts ──────────────────────────────── */
    @if(session('success'))
    document.addEventListener('DOMContentLoaded', () => showToast({{ json_encode(session('success')) }}, 'success'));
    @endif
    @if(session('error'))
    document.addEventListener('DOMContentLoaded', () => showToast({{ json_encode(session('error')) }}, 'error'));
    @endif

    /* ── Global haptic feedback on primary button taps ──────────────────── */
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-success,.btn-danger,.btn-warning,.btn-primary')) {
            navigator.vibrate && navigator.vibrate(40);
        }
    });

    /* ── Prevent duplicate submits on order-action forms ─────────────────────
       Covers accept / reject / ready / delivered / completed / pay. On submit the
       button is disabled, gets a spinner + "جاري التنفيذ...", and repeat submits
       are ignored. Synchronous forms navigate on response, so the button stays
       disabled on success (no repeat); a bfcache restore or a safety timeout
       re-enables it. AJAX status-advance forms manage their own state (see the
       orders index page) and are excluded here. */
    (function () {
        const ACTION_PATHS = ['/accept', '/reject', '/ready', '/delivered', '/completed', '/mark-paid', '/delivery-fee'];
        const LOADING_TEXT = 'جاري التنفيذ...';

        function isActionForm(form) {
            if (form.classList.contains('status-advance-form')) return false; // self-managed (AJAX)
            let path;
            try { path = new URL(form.action, window.location.origin).pathname; }
            catch (e) { return false; }
            return ACTION_PATHS.some(p => path.endsWith(p));
        }

        function lock(btn) {
            if (!btn) return;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('loading');
            // Per-button override (e.g. "جاري الحفظ..." for the delivery-fee modal).
            const text = btn.dataset.loadingText || LOADING_TEXT;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + text;
        }

        function unlock(btn) {
            if (!btn || btn.dataset.originalHtml === undefined) return;
            btn.disabled = false;
            btn.classList.remove('loading');
            btn.innerHTML = btn.dataset.originalHtml;
        }

        // Attach on DOMContentLoaded so this delegated handler runs AFTER any
        // page-specific validators (e.g. the reject-reason check) — if they
        // cancelled the submit, e.defaultPrevented is true and we do nothing.
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!(form instanceof HTMLFormElement) || !isActionForm(form)) return;
                if (e.defaultPrevented) return;

                if (form.dataset.submitting === '1') { e.preventDefault(); return; }
                form.dataset.submitting = '1';

                const btn = form.querySelector('[type=submit]') || e.submitter;
                lock(btn);

                // Safety net: if no navigation happens (e.g. a network failure with
                // no server response), re-enable after a while so the user isn't stuck.
                setTimeout(function () {
                    form.dataset.submitting = '';
                    unlock(btn);
                }, 15000);
            });
        });

        // Back/forward bfcache restore can resurrect a page with its button still
        // disabled — reset any in-flight forms when that happens.
        window.addEventListener('pageshow', function (e) {
            if (!e.persisted) return;
            document.querySelectorAll('form[data-submitting="1"]').forEach(function (form) {
                form.dataset.submitting = '';
                unlock(form.querySelector('[type=submit]'));
            });
        });
    })();
    </script>

    @stack('scripts')
</body>
</html>
