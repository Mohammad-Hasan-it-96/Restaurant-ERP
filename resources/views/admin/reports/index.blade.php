@extends('layouts.app')

@section('title', __('app.reports_analytics'))

@push('styles')
<style>
.stat-icon-wrap {
    width: 46px; height: 46px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <h2 class="fw-bold mb-0 me-auto">
            <i class="bi bi-bar-chart-line me-2 text-primary"></i>
            {{ __('app.reports_analytics') }}
        </h2>
        <a href="{{ route('admin.reports.export', request()->query()) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ __('app.export_csv') }}
        </a>
    </div>

    {{-- ── A. Filter Bar ─────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form action="{{ route('admin.reports') }}" method="GET"
                  class="row g-2 align-items-end">

                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('app.date_from') }}</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ $filters['dateFrom']->toDateString() }}"
                           max="{{ $filters['dateTo']->toDateString() }}">
                </div>

                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('app.date_to') }}</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ $filters['dateTo']->toDateString() }}"
                           min="{{ $filters['dateFrom']->toDateString() }}">
                </div>

                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('app.status') }}</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all"       {{ $filters['status'] === 'all'       ? 'selected' : '' }}>{{ __('app.all_statuses') }}</option>
                        <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>{{ __('app.completed') }}</option>
                        <option value="pending"   {{ $filters['status'] === 'pending'   ? 'selected' : '' }}>{{ __('app.pending') }}</option>
                        <option value="accepted"  {{ $filters['status'] === 'accepted'  ? 'selected' : '' }}>{{ __('app.accepted') }}</option>
                        <option value="preparing" {{ $filters['status'] === 'preparing' ? 'selected' : '' }}>{{ __('app.preparing') }}</option>
                        <option value="ready"     {{ $filters['status'] === 'ready'     ? 'selected' : '' }}>{{ __('app.ready') }}</option>
                        <option value="delivered" {{ $filters['status'] === 'delivered' ? 'selected' : '' }}>{{ __('app.delivered') }}</option>
                        <option value="rejected"  {{ $filters['status'] === 'rejected'  ? 'selected' : '' }}>{{ __('app.rejected') }}</option>
                        <option value="cancelled_by_admin"    {{ $filters['status'] === 'cancelled_by_admin'    ? 'selected' : '' }}>{{ __('app.cancelled_by_admin') }}</option>
                        <option value="cancelled_by_customer" {{ $filters['status'] === 'cancelled_by_customer' ? 'selected' : '' }}>{{ __('app.cancelled_by_customer') }}</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-funnel me-1"></i>{{ __('app.apply_filter') }}
                    </button>
                    <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- ── B. Summary Cards ──────────────────────────────────────────────── --}}
    <div class="row g-4 mb-4">

        {{-- Total Revenue --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.total_revenue') }}</p>
                            <h3 class="fw-bold mb-0">{{ number_format($totalRevenue, 2) }}</h3>
                        </div>
                        <div class="stat-icon-wrap" style="background:rgba(16,185,129,.12)">
                            <i class="bi bi-currency-dollar fs-4" style="color:var(--success, #10b981)"></i>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">
                        {{ $filters['dateFrom']->toDateString() }} — {{ $filters['dateTo']->toDateString() }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Total Orders --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.total_orders') }}</p>
                            <h3 class="fw-bold mb-0">{{ number_format($totalOrders) }}</h3>
                        </div>
                        <div class="stat-icon-wrap" style="background:rgba(79,70,229,.12)">
                            <i class="bi bi-receipt fs-4" style="color:var(--primary, #4f46e5)"></i>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">{{ __('app.status') }}: {{ __('app.' . $filters['status']) }}</p>
                </div>
            </div>
        </div>

        {{-- Average Order Value --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.avg_order_value') }}</p>
                            <h3 class="fw-bold mb-0">{{ number_format($averageOrderValue, 2) }}</h3>
                        </div>
                        <div class="stat-icon-wrap" style="background:rgba(59,130,246,.12)">
                            <i class="bi bi-calculator fs-4" style="color:var(--info, #3b82f6)"></i>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">{{ __('app.total_revenue') }} / {{ __('app.total_orders') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Delivery Fees --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted small mb-1">{{ __('app.total_delivery_fees') }}</p>
                            <h3 class="fw-bold mb-0">{{ number_format($totalDeliveryFees, 2) }}</h3>
                        </div>
                        <div class="stat-icon-wrap" style="background:rgba(245,158,11,.12)">
                            <i class="bi bi-truck fs-4" style="color:var(--warning, #f59e0b)"></i>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">{{ __('app.delivery') }} {{ __('app.orders') }}</p>
                </div>
            </div>
        </div>

    </div>

    {{-- ── C. Charts Row ─────────────────────────────────────────────────── --}}
    <div class="row g-4 mb-4">

        {{-- Revenue by Day (Bar) --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-bar-chart-line me-2 text-primary"></i>{{ __('app.revenue_by_day') }}
                    </h6>
                    <span class="badge bg-primary">{{ __('app.' . $filters['status']) }}</span>
                </div>
                <div class="card-body">
                    @if($revenueByDay->isEmpty())
                        <p class="text-muted text-center py-5">{{ __('app.no_data_for_period') }}</p>
                    @else
                        <canvas id="revenueChart" height="260"></canvas>
                    @endif
                </div>
            </div>
        </div>

        {{-- Orders by Type (Doughnut) --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart me-2 text-primary"></i>{{ __('app.orders_by_type') }}
                    </h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    @php $hasTypeData = ($ordersByType->sum() > 0); @endphp
                    @if(!$hasTypeData)
                        <p class="text-muted text-center py-4">{{ __('app.no_data_for_period') }}</p>
                    @else
                        <canvas id="typeChart" height="220"></canvas>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ── D. Top Products ────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-trophy me-2 text-primary"></i>{{ __('app.top_products') }}
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:3rem">#</th>
                            <th>{{ __('app.product_name') }}</th>
                            <th class="text-center">{{ __('app.qty_sold') }}</th>
                            <th class="text-end">{{ __('app.total_revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $i => $product)
                        <tr>
                            <td>
                                @if($i === 0)
                                    <i class="bi bi-trophy-fill text-warning"></i>
                                @elseif($i === 1)
                                    <i class="bi bi-trophy-fill text-secondary"></i>
                                @elseif($i === 2)
                                    <i class="bi bi-trophy-fill" style="color:#cd7f32"></i>
                                @else
                                    <span class="text-muted">{{ $i + 1 }}</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $product->product_name }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ number_format($product->total_quantity) }}</span>
                            </td>
                            <td class="text-end fw-semibold text-success">
                                {{ number_format($product->total_revenue, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                {{ __('app.no_data_for_period') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── E. Orders Table (paginated) ────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-list-ul me-2 text-primary"></i>{{ __('app.orders') }}
                <span class="badge bg-secondary ms-1">{{ $orders->total() }}</span>
            </h6>
            <a href="{{ route('admin.reports.export', request()->query()) }}"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-download me-1"></i>{{ __('app.export_csv') }}
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('app.order_number') }}</th>
                            <th>{{ __('app.customer_name') }}</th>
                            <th>{{ __('app.phone') }}</th>
                            <th>{{ __('app.order_type') }}</th>
                            <th>{{ __('app.status') }}</th>
                            <th class="text-end">{{ __('app.total') }}</th>
                            <th>{{ __('app.created_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="fw-semibold">{{ $order->order_number }}</td>
                            <td>{{ $order->customer->full_name ?? '—' }}</td>
                            <td dir="ltr">{{ $order->customer->phone ?? '—' }}</td>
                            <td>
                                @php $typeIcons = ['delivery'=>'bi-bicycle','table'=>'bi-grid-1x2','takeaway'=>'bi-bag']; @endphp
                                <i class="bi {{ $typeIcons[$order->order_type] ?? 'bi-question' }} me-1"></i>
                                {{ __('app.' . $order->order_type) }}
                            </td>
                            <td>@include('admin.orders.partials.status-badge', ['status' => $order->status])</td>
                            <td class="text-end fw-semibold text-primary">
                                {{ number_format($order->total, 2) }}
                            </td>
                            <td class="text-muted small">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="{{ __('app.view') }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                                {{ __('app.no_orders_found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
        <div class="card-footer bg-transparent">
            {{ $orders->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Theme-aware colors (matches dashboard.blade.php) ──────────────────
    const isDark     = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const gridColor  = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
    const labelColor = isDark ? '#94a3b8' : '#64748b';

    // ── Revenue by Day (Bar chart) ─────────────────────────────────────────
    const revenueRaw  = @json($revenueByDay);
    const revCanvas   = document.getElementById('revenueChart');

    if (revCanvas && revenueRaw.length) {
        new Chart(revCanvas, {
            type: 'bar',
            data: {
                labels:   revenueRaw.map(d => d.date),
                datasets: [{
                    label:           '{{ __('app.total_revenue') }}',
                    data:            revenueRaw.map(d => d.revenue),
                    backgroundColor: 'rgba(79,70,229,.75)',
                    borderColor:     '#4f46e5',
                    borderWidth:     1,
                    borderRadius:    6,
                    borderSkipped:   false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: labelColor },
                        grid:  { color: gridColor }
                    },
                    x: {
                        ticks: { color: labelColor, maxRotation: 45 },
                        grid:  { display: false }
                    }
                }
            }
        });
    }

    // ── Orders by Type (Doughnut) ──────────────────────────────────────────
    const typeCanvas = document.getElementById('typeChart');

    if (typeCanvas) {
        new Chart(typeCanvas, {
            type: 'doughnut',
            data: {
                labels: [
                    '{{ __('app.table') }}',
                    '{{ __('app.delivery') }}',
                    '{{ __('app.takeaway') }}'
                ],
                datasets: [{
                    data: [
                        {{ $ordersByType['table']    ?? 0 }},
                        {{ $ordersByType['delivery'] ?? 0 }},
                        {{ $ordersByType['takeaway'] ?? 0 }}
                    ],
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
    }

});
</script>
@endpush

