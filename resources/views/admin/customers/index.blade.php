@extends('layouts.app')

@section('title', __('app.customers_management'))

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-people me-2 text-primary"></i>
            {{ __('app.customers_management') }}
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

    {{-- Search --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.customers.index') }}" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control" style="max-width:320px"
                       placeholder="{{ __('app.search_customers') }}">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>{{ __('app.search') }}
                </button>
                @if(request('search'))
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>{{ __('app.clear') }}
                </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @php
                $sort = request('sort', 'created_at');
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
                                <a href="{{ $sortUrl('created_at') }}" class="text-dark text-decoration-none"># {!! $sortIcon('created_at') !!}</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('full_name') }}" class="text-dark text-decoration-none">{{ __('app.full_name') }} {!! $sortIcon('full_name') !!}</a>
                            </th>
                            <th>{{ __('app.phone') }}</th>
                            <th>
                                <a href="{{ $sortUrl('orders_count') }}" class="text-dark text-decoration-none">{{ __('app.orders_count') }} {!! $sortIcon('orders_count') !!}</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('orders_sum_total') }}" class="text-dark text-decoration-none">{{ __('app.total_spent') }} {!! $sortIcon('orders_sum_total') !!}</a>
                            </th>
                            <th>{{ __('app.last_order') }}</th>
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td class="text-muted small">{{ $customer->id }}</td>
                            <td class="fw-semibold">{{ $customer->full_name }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $customer->orders_count }}</span>
                            </td>
                            <td class="fw-semibold text-success">
                                {{ number_format($customer->orders_sum_total ?? 0, 2) }}
                                <small class="text-muted">{{ __('app.currency') }}</small>
                            </td>
                            <td class="small text-muted">
                                {{ $customer->orders_max_created_at
                                    ? \Carbon\Carbon::parse($customer->orders_max_created_at)->diffForHumans()
                                    : '—' }}
                            </td>
                            <td>
                                @if($customer->is_blocked)
                                    <span class="badge bg-danger">{{ __('app.blocked') }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('app.active') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('admin.customers.show', $customer) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>{{ __('app.view') }}
                                    </a>
                                    @if($customer->is_blocked)
                                    <form method="POST"
                                          action="{{ route('admin.customers.unblock', $customer) }}"
                                          onsubmit="return confirm('{{ __('app.confirm_unblock') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-unlock me-1"></i>{{ __('app.unblock') }}
                                        </button>
                                    </form>
                                    @else
                                    <form method="POST"
                                          action="{{ route('admin.customers.block', $customer) }}"
                                          onsubmit="return confirm('{{ __('app.confirm_block') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-ban me-1"></i>{{ __('app.block') }}
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-people fs-3 d-block mb-2"></i>
                                {{ __('app.no_customers_found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($customers->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                {{ __('app.showing') }} {{ $customers->firstItem() }}–{{ $customers->lastItem() }}
                {{ __('app.of') }} {{ $customers->total() }}
            </small>
            {{ $customers->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

