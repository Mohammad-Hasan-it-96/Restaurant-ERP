@extends('layouts.app')

@section('title', __('app.customer') . ' — ' . $customer->full_name)

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-right me-1"></i>{{ __('app.back') }}
            </a>
            <h2 class="fw-bold mb-0">
                <i class="bi bi-person-circle me-2 text-primary"></i>
                {{ $customer->full_name }}
            </h2>
        </div>

        <form method="POST"
              action="{{ route('admin.customers.toggle-block', $customer) }}"
              onsubmit="return confirm('{{ $customer->is_blocked ? __('app.confirm_unblock') : __('app.confirm_block') }}')">
            @csrf
            <button type="submit"
                    class="btn {{ $customer->is_blocked ? 'btn-success' : 'btn-danger' }}">
                @if($customer->is_blocked)
                    <i class="bi bi-unlock me-1"></i>{{ __('app.unblock') }}
                @else
                    <i class="bi bi-ban me-1"></i>{{ __('app.block') }}
                @endif
            </button>
        </form>
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
                    <div class="text-muted small mb-1">{{ __('app.status') }}</div>
                    @if($customer->is_blocked)
                        <span class="badge bg-danger fs-6">{{ __('app.blocked') }}</span>
                    @else
                        <span class="badge bg-success fs-6">{{ __('app.active') }}</span>
                    @endif
                </div>
            </div>
        </div>
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
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.total') }}</th>
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
                                'completed'             => 'success',
                                'rejected'              => 'danger',
                                'cancelled_by_admin'    => 'dark',
                                'cancelled_by_customer' => 'secondary',
                            ];
                            $color = $statusColors[$order->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="fw-semibold">#{{ $order->order_number }}</td>
                            <td>
                                <span class="badge bg-{{ $color }}">
                                    {{ __('app.' . $order->status) }}
                                </span>
                            </td>
                            <td>{{ number_format($order->total, 2) }}</td>
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
                            <td colspan="5" class="text-center py-4 text-muted">
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
@endsection

