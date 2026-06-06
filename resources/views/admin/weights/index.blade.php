@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('weights_management'))

@section('content')
<div class="container-fluid">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1" style="color: var(--text);">
                <i class="bi bi-speedometer2 me-2" style="color: var(--primary);"></i>
                {{ \App\Helpers\Helpers::translate('weights_management') }}
            </h3>
        </div>
        @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
        <a href="{{ route('admin.weights.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i>
            {{ __('app.add_weight') }}
        </a>
        @endif
    </div>

    {{-- Flash messages --}}
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
    <div class="card shadow-sm mb-4" style="border: 1px solid var(--border-color); background-color: var(--card-bg);">
        <div class="card-body">
            <form action="{{ route('admin.weights.index') }}" method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control"
                           placeholder="{{ __('app.search') }}..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i>{{ __('app.search') }}
                    </button>
                    <a href="{{ route('admin.weights.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm" style="border: 1px solid var(--border-color); background-color: var(--card-bg);">
        <div class="card-body p-0">
            @if($weights->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-speedometer2" style="font-size: 3rem; color: var(--text-muted);"></i>
                    <p class="mt-3 text-muted">{{ __('app.no_weights_found') }}</p>
                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                    <a href="{{ route('admin.weights.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('app.add_weight') }}
                    </a>
                    @endif
                </div>
            @else
            <div class="table-responsive">
                @php
                    $sort = request('sort', 'sort_order');
                    $dir  = request('direction', 'asc');
                    $sortUrl  = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'direction' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
                    $sortIcon = fn($col) => $sort === $col
                        ? ($dir === 'asc' ? '<i class="bi bi-sort-up-alt text-primary ms-1"></i>' : '<i class="bi bi-sort-down-alt text-primary ms-1"></i>')
                        : '<i class="bi bi-arrow-down-up text-muted ms-1 opacity-50"></i>';
                @endphp
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: var(--sidebar-hover);">
                        <tr>
                            <th class="px-4 py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">
                                <a href="{{ $sortUrl('id') }}" class="text-decoration-none" style="color:var(--text-muted)"># {!! $sortIcon('id') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">
                                <a href="{{ $sortUrl('name') }}" class="text-decoration-none" style="color:var(--text-muted)">{{ __('app.weight_name') }} {!! $sortIcon('name') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">
                                <a href="{{ $sortUrl('value_kg') }}" class="text-decoration-none" style="color:var(--text-muted)">{{ __('app.value_kg') }} {!! $sortIcon('value_kg') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">
                                <a href="{{ $sortUrl('sort_order') }}" class="text-decoration-none" style="color:var(--text-muted)">{{ __('app.sort_order') }} {!! $sortIcon('sort_order') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">{{ __('app.status') }}</th>
                            <th class="py-3 text-end pe-4" style="color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase;">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($weights as $weight)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td class="px-4 py-3 text-muted" style="font-size: .875rem;">{{ $weight->id }}</td>
                            <td class="py-3 fw-semibold" style="color: var(--text);">{{ $weight->name }}</td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border" style="border-color: var(--border-color) !important; font-size: .85rem;">
                                    {{ $weight->value_kg }} kg
                                </span>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border" style="border-color: var(--border-color) !important;">
                                    {{ $weight->sort_order }}
                                </span>
                            </td>
                            <td class="py-3">
                                @if($weight->is_active)
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                        <i class="bi bi-check-circle me-1"></i>{{ __('app.active') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">
                                        <i class="bi bi-x-circle me-1"></i>{{ __('app.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-end pe-4">
                                @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.weights.edit', $weight) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="{{ __('app.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger delete-weight"
                                            data-id="{{ $weight->id }}"
                                            data-name="{{ $weight->name }}"
                                            data-url="{{ route('admin.weights.destroy', $weight) }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($weights->hasPages())
            <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-top: 1px solid var(--border-color);">
                <small class="text-muted">
                    {{ $weights->firstItem() }} – {{ $weights->lastItem() }} {{ __('app.of') }} {{ $weights->total() }}
                </small>
                {{ $weights->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>
@endsection

{{-- Delete modal --}}
<div class="modal fade" id="deleteWeightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ __('app.confirm_delete') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <i class="bi bi-trash3 text-danger mb-3 d-block" style="font-size:3rem;"></i>
                <p class="mb-0" id="deleteWeightMessage" style="color: var(--text);"></p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    {{ __('app.cancel') }}
                </button>
                <form id="deleteWeightForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i>{{ __('app.delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal  = new bootstrap.Modal(document.getElementById('deleteWeightModal'));
    const msgEl  = document.getElementById('deleteWeightMessage');
    const formEl = document.getElementById('deleteWeightForm');

    document.querySelectorAll('.delete-weight').forEach(function (btn) {
        btn.addEventListener('click', function () {
            msgEl.textContent = '{{ __('app.delete_confirmation') }}'.replace(':name', this.dataset.name);
            formEl.action = this.dataset.url;
            modal.show();
        });
    });
});
</script>
@endpush
