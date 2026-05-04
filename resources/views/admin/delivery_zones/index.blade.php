@extends('layouts.app')

@section('title', __('app.delivery_zones_management'))

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-geo-alt me-2 text-primary"></i>
            {{ __('app.delivery_zones_management') }}
        </h2>
        @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
        <a href="{{ route('admin.delivery-zones.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i>
            {{ __('app.add_delivery_zone') }}
        </a>
        @endif
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Search --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.delivery-zones.index') }}" method="GET" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="{{ __('app.search_delivery_zones') }}"
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>{{ __('app.search') }}
                    </button>
                    @if(request('search'))
                    <a href="{{ route('admin.delivery-zones.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>{{ __('app.clear') }}
                    </a>
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
                            <th>#</th>
                            <th>{{ __('app.area_name') }}</th>
                            <th>{{ __('app.estimated_fee') }}</th>
                            <th>{{ __('app.sort_order') }}</th>
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zones as $zone)
                        <tr>
                            <td class="text-muted small">{{ $zone->id }}</td>
                            <td class="fw-semibold">{{ $zone->area_name }}</td>
                            <td>{{ number_format($zone->estimated_fee, 2) }}</td>
                            <td>{{ $zone->sort_order }}</td>
                            <td>
                                @if($zone->is_active)
                                    <span class="badge bg-success">{{ __('app.active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('app.inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                                    <a href="{{ route('admin.delivery-zones.edit', $zone) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.delivery-zones.destroy', $zone) }}" method="POST"
                                          onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-geo-alt fs-3 d-block mb-2"></i>
                                {{ __('app.no_delivery_zones_found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($zones->hasPages())
        <div class="card-footer">
            {{ $zones->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

