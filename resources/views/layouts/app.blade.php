<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App::isLocale('ar') || (session('site_direction') == 'rtl') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - @yield('title')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- RTL Bootstrap CSS (conditionally loaded) -->
    @if(App::isLocale('ar') || (session('site_direction') == 'rtl'))
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    @endif

    <style>
        .sidebar {
            width: 250px;
            height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            background: #f8f9fa;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,.1);
        }

        .main-content {
            margin-left: 280px;
            margin-top: 56px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        /* RTL adjustments for sidebar and content */
        html[dir="rtl"] .sidebar {
            left: auto;
            right: 0;
            box-shadow: -2px 0 5px rgba(0,0,0,.1);
        }

        html[dir="rtl"] .main-content {
            margin-left: 0;
            margin-right: 280px;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -280px;
            }

            html[dir="rtl"] .sidebar {
                left: auto;
                right: -280px;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            html[dir="rtl"] .main-content {
                margin-right: 0;
            }

            .sidebar.active {
                left: 0;
            }

            html[dir="rtl"] .sidebar.active {
                right: 0;
                left: auto;
            }
        }
    </style>

    <!-- Rest of your styles remain unchanged -->
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

    @stack('styles')
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
                @php
                    $languages = \App\Models\Language::where('status', 1)->get();
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
                <button class="navbar-toggler border-0" type="button" onclick="toggleSidebar()">
                    <i class="bi bi-list" style="color: var(--text); font-size: 1.5rem;"></i>
                </button>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    @auth
    <div class="sidebar">
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
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
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

        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Initialize animations for page elements
        function initializeAnimations() {
            // Apply animations to cards, alerts, and other elements
            const animatedElements = document.querySelectorAll('.card, .alert');
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
    @stack('scripts')
</body>
</html>

<style>
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

    /* RTL sidebar adjustments */
    html[dir="rtl"] .sidebar {
        left: auto;
        right: 0;
        box-shadow: -2px 0 15px rgba(0,0,0,.1);
    }

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

    .sidebar .nav-link {
        color: var(--text);
        transition: all 0.2s;
    }

    .sidebar .nav-link:hover:not(.active) {
        background-color: var(--sidebar-hover);
    }

    .sidebar-heading {
        letter-spacing: 1px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .sidebar .text-muted {
        color: var(--text-muted) !important;
    }

    /* Sidebar dropdown styles */
    .sidebar-item button {
        text-align: left;
        font-size: 1rem;
        color: var(--text);
    }

    .sidebar-item button:hover:not(.active) {
        background-color: var(--sidebar-hover);
    }

    .sidebar-item button:focus {
        outline: none;
        box-shadow: none;
    }

    /* Fix for active items - with increased specificity */
    .sidebar .nav-link.active,
    .sidebar-item button.active {
        background-color: var(--primary) !important;
    }

    .sidebar .nav-link.active span,
    .sidebar .nav-link.active i,
    .sidebar-item button.active span,
    .sidebar-item button.active i {
        color: #ffffff !important; /* Use explicit #ffffff instead of 'white' */
    }

    /* Additional specificity for dropdown items */
    .sidebar-item .collapse .nav-link.active span,
    .sidebar-item .collapse .nav-link.active i {
        color: #ffffff !important;
    }

    /* Ensure text is visible in all states */
    .sidebar-item button span,
    .sidebar-item .nav-link span {
        color: var(--text);
    }

    .sidebar-item button.active span,
    .sidebar-item button.active i,
    .sidebar-item .nav-link.active span,
    .sidebar-item .nav-link.active i {
        color: #ffffff !important;
    }

    /* Dark mode adjustments for dropdown menus */
    [data-bs-theme="dark"] .dropdown-menu {
        background-color: var(--card-bg);
        border-color: var(--border-color);
    }

    [data-bs-theme="dark"] .dropdown-item {
        color: var(--text);
    }

    [data-bs-theme="dark"] .dropdown-item:hover {
        background-color: var(--sidebar-hover);
    }

    [data-bs-theme="dark"] .dropdown-divider {
        border-color: var(--border-color);
    }
</style>
