@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('dashboard'))

@section('content')
<div class="container-fluid py-4">

    {{-- ── Restaurant Branding Banner ─────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,var(--primary) 0%,#7c3aed 100%);color:#fff;">
        <div class="card-body d-flex align-items-center gap-4 py-3">
            {{-- Logo --}}
            @if($restaurantLogo)
                <img src="{{ asset('storage/' . ltrim($restaurantLogo, '/')) }}"
                     alt="{{ $restaurantName }}"
                     style="width:72px;height:72px;object-fit:contain;border-radius:12px;
                            background:rgba(255,255,255,.15);padding:6px;flex-shrink:0;"
                     onerror="this.style.display='none'">
            @else
                <div style="width:72px;height:72px;border-radius:12px;background:rgba(255,255,255,.2);
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-shop" style="font-size:2rem;"></i>
                </div>
            @endif
            {{-- Names --}}
            <div>
                <h4 class="fw-bold mb-0" style="letter-spacing:-.3px;">{{ $restaurantName }}</h4>
                @php
                    $subtitle = app()->getLocale() === 'en' ? $restaurantNameAr : $restaurantNameEn;
                @endphp
                @if($subtitle && $subtitle !== $restaurantName)
                    <p class="mb-0 opacity-75 small">{{ $subtitle }}</p>
                @endif
                <p class="mb-0 mt-1 opacity-75 small">
                    <i class="bi bi-speedometer2 me-1"></i>{{ \App\Helpers\Helpers::translate('dashboard') }}
                </p>
            </div>
            {{-- Action button --}}
            <div class="ms-auto">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-light btn-sm opacity-90">
                    <i class="bi bi-receipt me-1"></i>{{ \App\Helpers\Helpers::translate('view_all') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ── Stats Cards (admin only) ────────────────────────────────────── --}}
    @if(auth()->user()->isAdmin())
    <div class="row g-4 mb-4">

        {{-- Orders Today --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.orders_today') }}</p>
                            <h3 class="fw-bold mb-0">{{ $ordersToday }}</h3>
                        </div>
                        <div class="rounded-circle p-2" style="background:rgba(79,70,229,.12)">
                            <i class="bi bi-bag-check fs-4" style="color:var(--primary)"></i>
                        </div>
                    </div>
                    <a href="{{ route('admin.orders.index') }}" class="small text-muted text-decoration-none">
                        <i class="bi bi-arrow-right me-1"></i>{{ __('app.view_orders') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Sales Today --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.sales_today') }}</p>
                            <h3 class="fw-bold mb-0">{{ number_format($salesToday, 2) }}</h3>
                        </div>
                        <div class="rounded-circle p-2" style="background:rgba(16,185,129,.12)">
                            <i class="bi bi-currency-dollar fs-4" style="color:var(--success)"></i>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">{{ __('app.excluding_cancelled') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Customers --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.total_customers_count') }}</p>
                            <h3 class="fw-bold mb-0">{{ $totalCustomers }}</h3>
                        </div>
                        <div class="rounded-circle p-2" style="background:rgba(59,130,246,.12)">
                            <i class="bi bi-people fs-4" style="color:var(--info)"></i>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.index') }}" class="small text-muted text-decoration-none">
                        <i class="bi bi-arrow-right me-1"></i>{{ __('app.customers') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Pending Orders --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 {{ $pendingOrders > 0 ? 'border-warning border-start border-4' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.pending_orders') }}</p>
                            <h3 class="fw-bold mb-0 {{ $pendingOrders > 0 ? 'text-warning' : '' }}">
                                {{ $pendingOrders }}
                            </h3>
                        </div>
                        <div class="rounded-circle p-2" style="background:rgba(245,158,11,.12)">
                            <i class="bi bi-hourglass-split fs-4" style="color:var(--warning)"></i>
                        </div>
                    </div>
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}"
                       class="small text-decoration-none {{ $pendingOrders > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                        <i class="bi bi-arrow-right me-1"></i>{{ __('app.view_pending') }}
                    </a>
                </div>
            </div>
        </div>

    </div>{{-- end stats row --}}
    @endif

    {{-- ── Charts Row (admin only) ─────────────────────────────────────── --}}
    @if(auth()->user()->isAdmin())
    <div class="row g-4 mb-4">

        {{-- Weekly Orders Chart --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-bar-chart-line me-2 text-primary"></i>{{ __('app.orders_last_7_days') }}
                    </h6>
                    <span class="badge bg-primary">{{ __('app.this_week') }}</span>
                </div>
                <div class="card-body">
                    <canvas id="weeklyOrdersChart" height="250"></canvas>
                </div>
            </div>
        </div>

        {{-- Order Type Breakdown (Doughnut) --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart me-2 text-primary"></i>{{ __('app.order_types') }}
                    </h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    @php
                        $typeTable    = \App\Models\Order::where('order_type', \App\Models\Order::TYPE_TABLE)->count();
                        $typeDelivery = \App\Models\Order::where('order_type', \App\Models\Order::TYPE_DELIVERY)->count();
                        $typeTakeaway = \App\Models\Order::where('order_type', \App\Models\Order::TYPE_TAKEAWAY)->count();
                    @endphp
                    <canvas id="orderTypeChart" height="220"></canvas>
                </div>
            </div>
        </div>

    </div>{{-- end charts row --}}
    @endif

    {{-- ── Top Customers & Top Products (admin only) ─────────────────────── --}}
    @if(auth()->user()->isAdmin())
    <div class="row g-4 mb-4">

        {{-- Top 5 Customers --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-trophy me-2 text-warning"></i>{{ __('app.top_customers') }}
                    </h6>
                    <a href="{{ route('admin.customers.index', ['sort' => 'orders_count', 'direction' => 'desc']) }}"
                       class="btn btn-sm btn-link text-decoration-none p-0">
                        {{ __('app.view_all') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topCustomers as $i => $customer)
                        <li class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
                            <span class="fw-bold text-muted" style="min-width:1.4rem;">{{ $i + 1 }}</span>
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:34px;height:34px;background:rgba(79,70,229,.1);">
                                <i class="bi bi-person-fill" style="color:var(--primary)"></i>
                            </div>
                            <div class="flex-grow-1 text-truncate">
                                <a href="{{ route('admin.customers.show', $customer) }}"
                                   class="text-decoration-none fw-semibold text-body text-truncate d-block">
                                    {{ $customer->full_name }}
                                </a>
                            </div>
                            <span class="badge bg-primary rounded-pill">
                                {{ $customer->orders_count }} {{ __('app.orders') }}
                            </span>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-people fs-3 d-block mb-2"></i>
                            {{ __('app.no_data') }}
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Top 5 Products --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-fire me-2 text-danger"></i>{{ __('app.top_products') }}
                    </h6>
                    <a href="{{ route('admin.products.index') }}"
                       class="btn btn-sm btn-link text-decoration-none p-0">
                        {{ __('app.view_all') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    @php
                        $maxSold = $topProducts->first()?->total_sold ?: 1;
                    @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($topProducts as $i => $product)
                        @php $pct = round(($product->total_sold / $maxSold) * 100); @endphp
                        <li class="list-group-item py-2 px-3">
                            <div class="d-flex align-items-center gap-3 mb-1">
                                <span class="fw-bold text-muted" style="min-width:1.4rem;">{{ $i + 1 }}</span>
                                <span class="fw-semibold text-truncate flex-grow-1">{{ $product->product_name }}</span>
                                <span class="badge bg-success rounded-pill text-nowrap">
                                    {{ $product->total_sold }} {{ __('app.sold') }}
                                </span>
                            </div>
                            <div class="ps-4">
                                <div class="progress" style="height:5px;">
                                    <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                            {{ __('app.no_data') }}
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

    </div>{{-- end top row --}}
    @endif

    {{-- ── Recent Orders ────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i>{{ __('app.recent_orders') }}
            </h6>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-link text-decoration-none">
                {{ __('app.view_all') }}
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('app.order_number') }}</th>
                            <th>{{ __('app.customer') }}</th>
                            <th>{{ __('app.phone') }}</th>
                            <th>{{ __('app.order_type') }}</th>
                            <th>{{ __('app.address') }}</th>
                            @if(auth()->user()->isAdmin())
                            <th>{{ __('app.total') }}</th>
                            @endif
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                        @php
                            $statusColors = [
                                'pending'               => 'warning',
                                'accepted'              => 'primary',
                                'completed'             => 'success',
                                'rejected'              => 'danger',
                                'cancelled_by_admin'    => 'dark',
                                'cancelled_by_customer' => 'secondary',
                            ];
                            $color = $statusColors[$order->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="fw-semibold">#{{ $order->order_number }}</td>
                            <td>{{ $order->customer_name ?? '—' }}</td>
                            <td class="small text-muted">{{ $order->phone ?? '—' }}</td>
                            <td>
                                @php $typeIcons = ['delivery'=>'bi-bicycle','table'=>'bi-grid-1x2','takeaway'=>'bi-bag']; @endphp
                                <i class="bi {{ $typeIcons[$order->order_type] ?? 'bi-question' }} me-1"></i>
                                {{ __('app.' . $order->order_type) }}
                            </td>
                            <td class="small text-muted" style="max-width:160px;">
                                {{ $order->address ? \Illuminate\Support\Str::limit($order->address, 40) : '—' }}
                            </td>
                            @if(auth()->user()->isAdmin())
                            <td>{{ number_format($order->total, 2) }}</td>
                            @endif
                            <td><span class="badge bg-{{ $color }}">{{ __('app.' . $order->status) }}</span></td>
                            <td class="text-muted small">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.orders.invoice', $order) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="طباعة سريعة">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() ? 9 : 8 }}" class="text-center text-muted py-4">
                                <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                                {{ __('app.no_orders_found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@if(auth()->user()->isAdmin())
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Detect dark mode ────────────────────────────────────────────────────
    const isDark     = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const gridColor  = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
    const labelColor = isDark ? '#94a3b8' : '#64748b';

    // ── Weekly Orders Bar Chart ─────────────────────────────────────────────
    const weekLabels = @json($weekLabels);
    const weekCounts = @json($weekCounts);

    new Chart(document.getElementById('weeklyOrdersChart'), {
        type: 'bar',
        data: {
            labels: weekLabels,
            datasets: [{
                label: '{{ __('app.orders') }}',
                data: weekCounts,
                backgroundColor: 'rgba(79,70,229,.75)',
                borderColor: '#4f46e5',
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y + ' {{ __('app.orders') }}'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: labelColor, stepSize: 1, precision: 0 },
                    grid:  { color: gridColor }
                },
                x: {
                    ticks: { color: labelColor },
                    grid:  { display: false }
                }
            }
        }
    });

    // ── Order Type Doughnut ─────────────────────────────────────────────────
    new Chart(document.getElementById('orderTypeChart'), {
        type: 'doughnut',
        data: {
            labels: ['{{ __('app.table') }}', '{{ __('app.delivery') }}', '{{ __('app.takeaway') }}'],
            datasets: [{
                data: [{{ $typeTable }}, {{ $typeDelivery }}, {{ $typeTakeaway }}],
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: labelColor, padding: 16, boxWidth: 12 }
                }
            }
        }
    });

});
</script>
@endpush
@endif
