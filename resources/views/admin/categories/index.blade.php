@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('categories_management'))

@section('content')
<div class="container-fluid">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1" style="color: var(--text);">
                <i class="bi bi-tags me-2" style="color: var(--primary);"></i>
                {{ \App\Helpers\Helpers::translate('categories_management') }}
            </h3>
            <p class="text-muted mb-0">{{ \App\Helpers\Helpers::translate('manage_categories') }}</p>
        </div>
        @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i>
            {{ __('app.add_category') }}
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

    {{-- Search & Filter --}}
    <div class="card shadow-sm mb-4" style="border: 1px solid var(--border-color); background-color: var(--card-bg);">
        <div class="card-header py-3" style="background-color: var(--sidebar-hover); border-bottom: 1px solid var(--border-color);">
            <h6 class="m-0 fw-bold" style="color: var(--text);">
                <i class="bi bi-funnel me-2"></i>{{ \App\Helpers\Helpers::translate('filter_options') }}
            </h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.categories.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">{{ \App\Helpers\Helpers::translate('search') }}</label>
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="{{ \App\Helpers\Helpers::translate('search_categories') }}"
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ \App\Helpers\Helpers::translate('parent_category') }}</label>
                    <select name="parent_id" class="form-select">
                        <option value="">{{ \App\Helpers\Helpers::translate('all_categories') }}</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name_ar }}{{ $parent->name_en ? ' / ' . $parent->name_en : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i>{{ \App\Helpers\Helpers::translate('apply_filters') }}
                    </button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Categories table --}}
    <div class="card shadow-sm" style="border: 1px solid var(--border-color); background-color: var(--card-bg);">
        <div class="card-body p-0">
            @if($categories->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-tags" style="font-size: 3rem; color: var(--text-muted);"></i>
                    <p class="mt-3 text-muted">{{ \App\Helpers\Helpers::translate('no_categories_found') }}</p>
                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('app.add_category') }}
                    </a>
                    @endif
                </div>
            @else
            <div class="table-responsive">
                @php
                    $sort = request('sort', 'created_at');
                    $dir  = request('direction', 'desc');
                    $sortUrl  = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'direction' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
                    $sortIcon = fn($col) => $sort === $col
                        ? ($dir === 'asc' ? '<i class="bi bi-sort-up-alt text-primary ms-1"></i>' : '<i class="bi bi-sort-down-alt text-primary ms-1"></i>')
                        : '<i class="bi bi-arrow-down-up text-muted ms-1 opacity-50"></i>';
                @endphp
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: var(--sidebar-hover);">
                        <tr>
                            <th class="px-4 py-3" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <a href="{{ $sortUrl('id') }}" class="text-decoration-none" style="color:var(--text-muted)"># {!! $sortIcon('id') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">{{ \App\Helpers\Helpers::translate('category_image') }}</th>
                            <th class="py-3" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <a href="{{ $sortUrl('name_ar') }}" class="text-decoration-none" style="color:var(--text-muted)">{{ \App\Helpers\Helpers::translate('name_ar') }} {!! $sortIcon('name_ar') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <a href="{{ $sortUrl('name_en') }}" class="text-decoration-none" style="color:var(--text-muted)">{{ \App\Helpers\Helpers::translate('name_en') }} {!! $sortIcon('name_en') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">{{ \App\Helpers\Helpers::translate('parent_category') }}</th>
                            <th class="py-3" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <a href="{{ $sortUrl('sort_order') }}" class="text-decoration-none" style="color:var(--text-muted)">{{ \App\Helpers\Helpers::translate('sort_order') }} {!! $sortIcon('sort_order') !!}</a>
                            </th>
                            <th class="py-3" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">{{ \App\Helpers\Helpers::translate('status') }}</th>
                            <th class="py-3 text-end pe-4" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">{{ \App\Helpers\Helpers::translate('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $index => $category)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td class="px-4 py-3 text-muted" style="font-size: 0.875rem;">
                                {{ $categories->firstItem() + $index }}
                            </td>
                            <td class="py-3">
                                @if($category->image)
                                    <img src="{{ asset('storage/' . $category->image) }}"
                                         alt="{{ $category->name_ar }}"
                                         class="rounded"
                                         style="width: 48px; height: 48px; object-fit: cover; border: 1px solid var(--border-color);">
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center"
                                         style="width: 48px; height: 48px; background-color: var(--sidebar-hover); border: 1px solid var(--border-color);">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3">
                                <span class="fw-semibold" style="color: var(--text);">{{ $category->name_ar }}</span>
                            </td>
                            <td class="py-3">
                                <span style="color: var(--text-muted);">{{ $category->name_en ?? '—' }}</span>
                            </td>
                            <td class="py-3">
                                @if($category->parent)
                                    <span class="badge rounded-pill bg-secondary">{{ $category->parent->name_ar }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border" style="border-color: var(--border-color) !important;">
                                    {{ $category->sort_order }}
                                </span>
                            </td>
                            <td class="py-3">
                                @if($category->is_active)
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                        <i class="bi bi-check-circle me-1"></i>{{ \App\Helpers\Helpers::translate('active') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">
                                        <i class="bi bi-x-circle me-1"></i>{{ \App\Helpers\Helpers::translate('inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'moderator')
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="{{ \App\Helpers\Helpers::translate('edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger delete-category"
                                            title="{{ \App\Helpers\Helpers::translate('delete') }}"
                                            data-id="{{ $category->id }}"
                                            data-name="{{ $category->name_ar }}"
                                            data-url="{{ route('admin.categories.destroy', $category) }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($categories->hasPages())
            <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-top: 1px solid var(--border-color);">
                <small class="text-muted">
                    {{ \App\Helpers\Helpers::translate('showing') }}
                    {{ $categories->firstItem() }} {{ \App\Helpers\Helpers::translate('to') }}
                    {{ $categories->lastItem() }} {{ \App\Helpers\Helpers::translate('of') }}
                    {{ $categories->total() }} {{ \App\Helpers\Helpers::translate('entries') }}
                </small>
                <div>
                    {{ $categories->links() }}
                </div>
            </div>
            @endif

            @endif
        </div>
    </div>

</div>
@endsection

