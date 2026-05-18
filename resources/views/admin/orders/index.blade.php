@extends('layouts.app')

@section('title', __('app.orders_management'))

@push('styles')
<style>
#newOrderBanner {
    border-left: 5px solid #f59e0b;
    animation: pulse-border 1s infinite alternate;
}
@keyframes pulse-border {
    from { border-left-color: #f59e0b; }
    to   { border-left-color: #ef4444; }
}
.quick-filter-btn.active-filter { box-shadow: 0 0 0 3px rgba(79,70,229,.35); }
</style>
@endpush

@section('content')
<div class="container-fluid py-4"
     id="ordersPage"
     data-latest-id="{{ $latestId }}"
     data-pending="{{ $counts['pending'] ?? 0 }}"
     data-poll-url="{{ route('admin.orders.index', ['_poll' => 1]) }}">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-receipt me-2 text-primary"></i>
            {{ __('app.orders_management') }}
        </h2>
    </div>

    {{-- New Order Banner --}}
    <div id="newOrderBanner" class="alert alert-warning alert-dismissible d-none mb-3">
        <i class="bi bi-bell-fill me-2 text-warning"></i>
        <strong>طلب جديد!</strong> وصل طلب جديد.
        <button id="refreshNowBtn" class="btn btn-sm btn-warning ms-2">
            <i class="bi bi-arrow-clockwise me-1"></i>تحديث الآن
        </button>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ── Quick Filter Pills ──────────────────────────────────────────────── --}}
    @php
        $currentStatus = request('status', 'all');
        $quickFilters  = [
            'all'       => ['label' => 'الكل',          'icon' => 'bi-list-ul',        'color' => 'secondary'],
            'pending'   => ['label' => 'قيد الانتظار',  'icon' => 'bi-hourglass-split','color' => 'warning'],
            'accepted'  => ['label' => 'مقبول',         'icon' => 'bi-check-circle',   'color' => 'primary'],
            'preparing' => ['label' => 'قيد التحضير',   'icon' => 'bi-fire',           'color' => 'warning'],
            'ready'     => ['label' => 'جاهز',          'icon' => 'bi-check2-circle',  'color' => 'info'],
            'delivered' => ['label' => 'تم التوصيل',    'icon' => 'bi-truck',          'color' => 'primary'],
            'completed' => ['label' => 'مكتمل',         'icon' => 'bi-check2-all',     'color' => 'success'],
            'cancelled_by_admin'    => ['label' => 'ملغي (إدارة)',  'icon' => 'bi-x-circle',  'color' => 'dark'],
            'cancelled_by_customer' => ['label' => 'ملغي (عميل)',  'icon' => 'bi-x-octagon', 'color' => 'secondary'],
            'rejected'  => ['label' => 'مرفوض',         'icon' => 'bi-ban',            'color' => 'danger'],
        ];
    @endphp
    <div class="d-flex gap-2 flex-wrap mb-3">
        @foreach($quickFilters as $key => $meta)
        @php
            $isActive = ($currentStatus === $key) || ($key === 'all' && $currentStatus === 'all');
            $cnt      = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0);
            $href     = route('admin.orders.index', array_merge(
                            request()->except('status','page'),
                            $key !== 'all' ? ['status' => $key] : []
                        ));
        @endphp
        <a href="{{ $href }}"
           class="btn btn-sm quick-filter-btn {{ $isActive ? 'btn-'.$meta['color'].' active-filter' : 'btn-outline-'.$meta['color'] }}">
            <i class="bi {{ $meta['icon'] }} me-1"></i>
            {{ $meta['label'] }}
            @if($cnt)
            <span class="badge {{ $isActive ? 'bg-white text-dark' : 'bg-'.$meta['color'] }} ms-1">{{ $cnt }}</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form action="{{ route('admin.orders.index') }}" method="GET" class="row g-2 align-items-end">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="{{ __('app.search_orders') }}"
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('app.search') }}</button>
                    @if(request('search'))
                    <a href="{{ route('admin.orders.index', request()->only('status')) }}" class="btn btn-outline-secondary">{{ __('app.clear') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @php
                $sort = request('sort', 'id');
                $dir  = request('direction', 'desc');
                $sortUrl  = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'direction' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
                $sortIcon = fn($col) => $sort === $col
                    ? ($dir === 'asc' ? '<i class="bi bi-sort-up-alt text-primary ms-1"></i>' : '<i class="bi bi-sort-down-alt text-primary ms-1"></i>')
                    : '<i class="bi bi-arrow-down-up text-muted ms-1" style="opacity:.5"></i>';
            @endphp
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ $sortUrl('id') }}" class="text-dark text-decoration-none">{{ __('app.order_number') }} {!! $sortIcon('id') !!}</a>
                            </th>
                            <th>{{ __('app.customer_name') }}</th>
                            <th>{{ __('app.phone') }}</th>
                            <th>{{ __('app.order_type') }}</th>
                            <th>
                                <a href="{{ $sortUrl('total') }}" class="text-dark text-decoration-none">{{ __('app.subtotal') }} {!! $sortIcon('total') !!}</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('status') }}" class="text-dark text-decoration-none">{{ __('app.status') }} {!! $sortIcon('status') !!}</a>
                            </th>
                            <th>{{ __('app.payment_status') }}</th>
                            <th>
                                <a href="{{ $sortUrl('created_at') }}" class="text-dark text-decoration-none">{{ __('app.created_at') }} {!! $sortIcon('created_at') !!}</a>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="fw-semibold">{{ $order->order_number }}</td>
                            <td>{{ $order->customer_name ?? '—' }}</td>
                            <td dir="ltr">{{ $order->phone ?? '—' }}</td>
                            <td>
                                @php
                                    $typeIcons = ['delivery' => 'bi-bicycle', 'table' => 'bi-grid-1x2', 'takeaway' => 'bi-bag'];
                                    $icon = $typeIcons[$order->order_type] ?? 'bi-question';
                                @endphp
                                <i class="bi {{ $icon }} me-1"></i>{{ __('app.' . $order->order_type) }}
                            </td>
                            <td>{{ number_format($order->subtotal, 2) }}</td>
                            <td>@include('admin.orders.partials.status-badge', ['status' => $order->status])</td>
                            <td>
                                @php $ps = $order->payment_status ?? 'unpaid'; @endphp
                                <span class="badge bg-{{ $ps === 'paid' ? 'success' : ($ps === 'refunded' ? 'warning' : 'secondary') }}">
                                    {{ __('app.' . $ps) }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="{{ __('app.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(in_array($order->status, ['accepted', 'completed']))
                                    <a href="{{ route('admin.orders.invoice', $order) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="طباعة سريعة">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    @endif
                                    {{-- Quick workflow transition buttons --}}
                                    @can('update', $order)
                                    @if($order->status === 'accepted')
                                        <form method="POST" action="{{ route('admin.orders.preparing', $order) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-warning" title="{{ __('app.mark_preparing') }}">
                                                <i class="bi bi-fire"></i>
                                            </button>
                                        </form>
                                    @elseif($order->status === 'preparing')
                                        <form method="POST" action="{{ route('admin.orders.ready', $order) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-info text-white" title="{{ __('app.mark_ready') }}">
                                                <i class="bi bi-check2-all"></i>
                                            </button>
                                        </form>
                                    @elseif($order->status === 'ready')
                                        @if($order->order_type === 'delivery')
                                            <form method="POST" action="{{ route('admin.orders.delivered', $order) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-primary" title="{{ __('app.mark_delivered') }}">
                                                    <i class="bi bi-truck"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.orders.completed', $order) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success" title="{{ __('app.mark_completed') }}">
                                                    <i class="bi bi-bag-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @elseif($order->status === 'delivered')
                                        <form method="POST" action="{{ route('admin.orders.completed', $order) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success" title="{{ __('app.mark_completed') }}">
                                                <i class="bi bi-bag-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
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
        <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const page        = document.getElementById('ordersPage');
    const pollUrl     = page.dataset.pollUrl;
    let   latestId    = parseInt(page.dataset.latestId) || 0;

    const banner      = document.getElementById('newOrderBanner');
    const refreshBtn  = document.getElementById('refreshNowBtn');

    /* ── Notification sound (public/sounds/notification.wav) ───────────── */
    const SOUND_URL = '{{ asset('sounds/notification.wav') }}';
    const notifAudio = new Audio(SOUND_URL);
    notifAudio.volume = 1.0;
    notifAudio.preload = 'auto';

    function beep() {
        try {
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

    /* ── Poll ────────────────────────────────────────────────────────────── */
    async function poll() {
        try {
            const res  = await fetch(pollUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();

            if (data.latest_id > latestId) {
                latestId = data.latest_id;
                beep();
                banner.classList.remove('d-none');

                let original = document.title;
                let blink = 0;
                const titleInterval = setInterval(() => {
                    document.title = (blink++ % 2 === 0) ? '🔔 طلب جديد!' : original;
                    if (blink > 10) { clearInterval(titleInterval); document.title = original; }
                }, 600);
            }
        } catch (e) { /* network error — silent */ }
    }

    /* ── "تحديث الآن" button ─────────────────────────────────────────────── */
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => window.location.reload());
    }

    /* ── Boot ────────────────────────────────────────────────────────────── */
    setInterval(poll, 10000);
    setTimeout(poll, 2000);
})();
</script>
@endpush
