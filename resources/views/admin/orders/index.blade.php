@extends('layouts.app')

@section('title', __('app.orders_management'))

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-receipt me-2 text-primary"></i>
            {{ __('app.orders_management') }}
        </h2>
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

    {{-- Status Tabs --}}
    @php
        $statuses = [
            'all'                   => ['label' => __('app.all'),                   'color' => 'secondary'],
            'pending'               => ['label' => __('app.pending'),               'color' => 'warning'],
            'accepted'              => ['label' => __('app.accepted'),              'color' => 'primary'],
            'rejected'              => ['label' => __('app.rejected'),              'color' => 'danger'],
            'completed'             => ['label' => __('app.completed'),             'color' => 'success'],
            'cancelled_by_admin'    => ['label' => __('app.cancelled_by_admin'),    'color' => 'dark'],
            'cancelled_by_customer' => ['label' => __('app.cancelled_by_customer'), 'color' => 'secondary'],
        ];
        $currentStatus = request('status', 'all');
    @endphp

    <ul class="nav nav-tabs mb-3 flex-wrap">
        @foreach($statuses as $key => $meta)
        <li class="nav-item">
            <a href="{{ route('admin.orders.index', array_merge(request()->except('status','page'), $key !== 'all' ? ['status' => $key] : [])) }}"
               class="nav-link {{ ($currentStatus === $key || ($key === 'all' && !request('status'))) ? 'active' : '' }}">
                {{ $meta['label'] }}
                @php $cnt = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0); @endphp
                @if($cnt)
                <span class="badge bg-{{ $meta['color'] }} ms-1">{{ $cnt }}</span>
                @endif
            </a>
        </li>
        @endforeach
    </ul>

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
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('app.order_number') }}</th>
                            <th>{{ __('app.customer_name') }}</th>
                            <th>{{ __('app.phone') }}</th>
                            <th>{{ __('app.order_type') }}</th>
                            <th>{{ __('app.subtotal') }}</th>
                            <th>{{ __('app.status') }}</th>
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
                                @php
                                    $typeIcons = ['delivery' => 'bi-bicycle', 'table' => 'bi-grid-1x2', 'takeaway' => 'bi-bag'];
                                    $icon = $typeIcons[$order->order_type] ?? 'bi-question';
                                @endphp
                                <i class="bi {{ $icon }} me-1"></i>{{ __('app.' . $order->order_type) }}
                            </td>
                            <td>{{ number_format($order->subtotal, 2) }}</td>
                            <td>@include('admin.orders.partials.status-badge', ['status' => $order->status])</td>
                            <td class="text-muted small">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
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
        <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>

</div>
@endsection

