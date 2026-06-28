@extends('layouts.app')

@section('title', __('app.customer') . ' — ' . $customer->full_name)

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $back ?? route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-right me-1"></i>{{ __('app.back') }}
                </a>
                <h2 class="fw-bold mb-0">
                    <i class="bi bi-person-circle me-2 text-primary"></i>
                    {{ $customer->full_name }}
                </h2>
            </div>

            @if($customer->is_blocked)
                <button type="button"
                        class="btn btn-success"
                        data-bs-toggle="modal"
                        data-bs-target="#unblockModal">
                    <i class="bi bi-unlock me-1"></i>{{ __('app.unblock') }}
                </button>
            @else
                <button type="button"
                        class="btn btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#blockModal"
                        data-action="{{ route('admin.customers.block', $customer) }}"
                        data-name="{{ $customer->full_name }}">
                    <i class="bi bi-ban me-1"></i>{{ __('app.block') }}
                </button>
            @endif
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Info Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('app.full_name') }}</div>
                        <div class="fw-semibold">{{ $customer->full_name }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('app.phone') }}</div>
                        <div class="fw-semibold">{{ $customer->phone }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('app.orders_count') }}</div>
                        <div class="fw-semibold fs-5">{{ $ordersCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('app.total_spent') }}</div>
                        <div class="fw-semibold fs-5 text-success">
                            {{ number_format($totalSpent, 2) }}
                            <small class="text-muted fs-6">{{ currency_symbol() }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('app.status') }}</div>
                        @if($customer->is_blocked)
                            <span class="badge bg-danger fs-6">{{ __('app.blocked') }}</span>
                            @if($customer->blocked_reason)
                                <p class="text-danger small mt-2 mb-0">
                                    <i class="bi bi-info-circle me-1"></i>{{ $customer->blocked_reason }}
                                </p>
                            @endif
                        @else
                            <span class="badge bg-success fs-6">{{ __('app.active') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @if($customer->default_address)
                <div class="col-md-9">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small mb-1">{{ __('app.address') }}</div>
                            <div class="fw-semibold">{{ $customer->default_address }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Orders Table --}}
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="bi bi-receipt me-2"></i>{{ __('app.orders') }}
                @if($lastOrder)
                    <small class="text-muted ms-2">
                        {{ __('app.last_order') }}: {{ $lastOrder->created_at->diffForHumans() }}
                    </small>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>{{ __('app.order_number') }}</th>
                            <th>{{ __('app.order_type') }}</th>
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.total') }}</th>
                            <th>{{ __('app.payment_status') }}</th>
                            <th>{{ __('app.date') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($orders as $order)
                            @php
                                $statusColors = [
                                    'pending'               => 'warning',
                                    'accepted'              => 'primary',
                                    'ready'                 => 'info',
                                    'delivered'             => 'primary',
                                    'completed'             => 'success',
                                    'rejected'              => 'danger',
                                    'cancelled_by_customer' => 'secondary',
                                    'modified'              => 'secondary',
                                ];
                                $color = $statusColors[$order->status] ?? 'secondary';
                                $paymentColors = [
                                    'unpaid'   => 'warning',
                                    'paid'     => 'success',
                                    'refunded' => 'info',
                                ];
                                $payColor = $paymentColors[$order->payment_status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="fw-semibold">#{{ $order->order_number }}</td>
                                <td>
                                <span class="badge bg-light text-dark border">
                                    {{ __('app.' . $order->order_type) }}
                                </span>
                                </td>
                                <td>
                                <span class="badge bg-{{ $color }}">
                                    {{ __('app.' . $order->status) }}
                                </span>
                                </td>
                                <td class="fw-semibold">
                                    {{ number_format($order->total, 2) }}
                                    <small class="text-muted">{{ currency_symbol() }}</small>
                                </td>
                                <td>
                                <span class="badge bg-{{ $payColor }}">
                                    {{ __('app.' . $order->payment_status) }}
                                </span>
                                </td>
                                <td class="small text-muted">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>{{ __('app.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
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
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        {{ __('app.showing') }} {{ $orders->firstItem() }}–{{ $orders->lastItem() }}
                        {{ __('app.of') }} {{ $orders->total() }}
                    </small>
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- Block Modal --}}
    <div class="modal fade" id="blockModal" tabindex="-1" aria-labelledby="blockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="blockForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="blockModalLabel">
                            <i class="bi bi-ban me-2"></i>{{ __('app.block_customer') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            {{ __('app.confirm_block_named', ['name' => $customer->full_name]) }}
                        </p>
                        <div class="mb-0">
                            <label for="blocked_reason" class="form-label fw-semibold">
                                {{ __('app.blocked_reason') }}
                                <small class="text-muted fw-normal">({{ __('app.optional') }})</small>
                            </label>
                            <textarea name="blocked_reason" id="blocked_reason"
                                      class="form-control" rows="3"
                                      placeholder="{{ __('app.blocked_reason_placeholder') }}"
                                      maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('app.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-ban me-1"></i>{{ __('app.block') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Unblock Modal --}}
    <div class="modal fade" id="unblockModal" tabindex="-1" aria-labelledby="unblockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.customers.unblock', $customer) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="unblockModalLabel">
                            <i class="bi bi-unlock me-2"></i>{{ __('app.unblock_customer') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            {{ __('app.confirm_unblock_named', ['name' => $customer->full_name]) }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('app.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-unlock me-1"></i>{{ __('app.unblock') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('blockModal').addEventListener('show.bs.modal', function (e) {
                const btn = e.relatedTarget;
                document.getElementById('blockForm').action = btn.dataset.action;
                document.getElementById('blocked_reason').value = '';
            });
        </script>
    @endpush

@endsection

