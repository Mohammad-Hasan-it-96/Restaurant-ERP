@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('manage_products'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h3>{{\App\Helpers\Helpers::translate('product_management')}}</h3>
        <div>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary me-2">
                <i class="bi bi-plus-lg me-2"></i>{{\App\Helpers\Helpers::translate('add_product')}}
            </a>
            <div class="btn-group">
                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-excel me-1"></i> {{\App\Helpers\Helpers::translate('excel')}}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header">{{\App\Helpers\Helpers::translate('export')}}</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.products.export') }}">
                            <i class="bi bi-file-earmark-excel me-2"></i>
                            @if(Auth::user()->role === 'admin')
                                {{\App\Helpers\Helpers::translate('export_all_products')}}
                            @else
                                {{\App\Helpers\Helpers::translate('export_your_products')}}
                            @endif
                        </a>
                    </li>
                    @if(Auth::user()->role === 'admin')
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header">{{\App\Helpers\Helpers::translate('export_by_user')}}</li>
                        @foreach($users as $user)
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.products.export', ['user_id' => $user->id]) }}">
                                    <i class="bi bi-person me-2"></i> {{ $user->name }}'s {{\App\Helpers\Helpers::translate('products')}}
                                </a>
                            </li>
                        @endforeach
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header">{{\App\Helpers\Helpers::translate('import')}}</li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.products.import') }}">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i> {{\App\Helpers\Helpers::translate('import_products')}}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter / Search --}}
    <div class="card shadow mb-4">
        <div class="card-header bg-light py-3">
            <h6 class="m-0 font-weight-bold">{{\App\Helpers\Helpers::translate('filter_options')}}</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.products.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">{{\App\Helpers\Helpers::translate('search')}}</label>
                    <input type="text" name="search" id="search" class="form-control"
                           placeholder="{{\App\Helpers\Helpers::translate('search_products')}}"
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label for="category_filter" class="form-label">{{\App\Helpers\Helpers::translate('category')}}</label>
                    <select name="category_id" id="category_filter" class="form-select">
                        <option value="">{{\App\Helpers\Helpers::translate('all_categories')}}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name_ar ?? $cat->name_en ?? $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-1"></i> {{\App\Helpers\Helpers::translate('apply_filters')}}
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle me-1"></i> {{\App\Helpers\Helpers::translate('clear_filters')}}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            @php
                $sort = request('sort', 'created_at');
                $dir  = request('direction', 'desc');
                $sortUrl  = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'direction' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
                $sortIcon = fn($col) => $sort === $col
                    ? ($dir === 'asc' ? '<i class="bi bi-sort-up-alt text-warning ms-1"></i>' : '<i class="bi bi-sort-down-alt text-warning ms-1"></i>')
                    : '<i class="bi bi-arrow-down-up ms-1" style="opacity:.55"></i>';
            @endphp
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="ps-4 sortable-col" onclick="window.location='{{ $sortUrl('id') }}'" style="cursor:pointer;user-select:none;white-space:nowrap">
                                # {!! $sortIcon('id') !!}
                            </th>
                            <th>{{\App\Helpers\Helpers::translate('image')}}</th>
                            <th class="sortable-col" onclick="window.location='{{ $sortUrl('name_ar') }}'" style="cursor:pointer;user-select:none;white-space:nowrap">
                                {{\App\Helpers\Helpers::translate('product_name')}} {!! $sortIcon('name_ar') !!}
                            </th>
                            <th>{{\App\Helpers\Helpers::translate('category')}}</th>
                            <th class="sortable-col" onclick="window.location='{{ $sortUrl('price') }}'" style="cursor:pointer;user-select:none;white-space:nowrap">
                                {{\App\Helpers\Helpers::translate('price')}} {!! $sortIcon('price') !!}
                            </th>
                            <th class="sortable-col" onclick="window.location='{{ $sortUrl('discount_price') }}'" style="cursor:pointer;user-select:none;white-space:nowrap">
                                {{\App\Helpers\Helpers::translate('discount_price')}} {!! $sortIcon('discount_price') !!}
                            </th>
                            <th>{{\App\Helpers\Helpers::translate('available')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('featured')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('active')}}</th>
                            <th class="sortable-col" onclick="window.location='{{ $sortUrl('created_at') }}'" style="cursor:pointer;user-select:none;white-space:nowrap">
                                {{\App\Helpers\Helpers::translate('created_at')}} {!! $sortIcon('created_at') !!}
                            </th>
                            <th class="text-end pe-4">{{\App\Helpers\Helpers::translate('actions')}}</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($products as $product)
                        <tr class="py-3">
                            <td class="ps-4 fw-medium">{{ $product->id }}</td>
                            <td>
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->display_name }}"
                                         class="rounded" style="width:50px;height:50px;object-fit:cover;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                         style="width:50px;height:50px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium">{{ $product->name_ar ?? $product->name }}</div>
                                @if($product->name_en)
                                    <small class="text-muted">{{ $product->name_en }}</small>
                                @endif
                            </td>
                            <td>{{ $product->category?->name_ar ?? $product->category?->name_en ?? '—' }}</td>
                            <td class="text-success fw-medium">${{ number_format($product->price, 2) }}</td>
                            <td>
                                @if($product->discount_price)
                                    <span class="text-danger fw-medium">${{ number_format($product->discount_price, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_available)
                                    <span class="badge bg-success">{{\App\Helpers\Helpers::translate('yes')}}</span>
                                @else
                                    <span class="badge bg-secondary">{{\App\Helpers\Helpers::translate('no')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_featured)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i></span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge bg-success">{{\App\Helpers\Helpers::translate('active')}}</span>
                                @else
                                    <span class="badge bg-danger">{{\App\Helpers\Helpers::translate('inactive')}}</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $product->created_at?->format('Y-m-d') }}</td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('admin.products.edit', $product->id) }}"
                                       class="btn btn-sm btn-light rounded-circle p-2">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-light rounded-circle p-2 delete-product"
                                            data-name="{{ $product->name_ar ?? $product->name }}"
                                            data-url="{{ route('admin.products.delete', $product->id) }}">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-database-exclamation fs-1"></i>
                                <p class="mt-3">{{\App\Helpers\Helpers::translate('no_products_found')}}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                <small class="text-muted">
                    @if($products->total())
                        {{\App\Helpers\Helpers::translate('showing')}}
                        {{ $products->firstItem() }}
                        {{\App\Helpers\Helpers::translate('to')}}
                        {{ $products->lastItem() }}
                        {{\App\Helpers\Helpers::translate('of')}}
                        {{ $products->total() }}
                        {{\App\Helpers\Helpers::translate('entries')}}
                    @endif
                </small>
                <div>
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Shared Delete Confirmation Modal --}}
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="modal-header" style="border-color: var(--border-color);">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{\App\Helpers\Helpers::translate('confirm_delete')}}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color: var(--text);">
                <span id="deleteProductMessage"></span>
            </div>
            <div class="modal-footer" style="border-color: var(--border-color);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{\App\Helpers\Helpers::translate('cancel')}}
                </button>
                <form id="deleteProductForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>{{\App\Helpers\Helpers::translate('delete')}}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteBtns = document.querySelectorAll('.delete-product');
    const modal      = new bootstrap.Modal(document.getElementById('deleteProductModal'));
    const msgEl      = document.getElementById('deleteProductMessage');
    const formEl     = document.getElementById('deleteProductForm');

    deleteBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const name = this.dataset.name;
            const url  = this.dataset.url;
            msgEl.textContent = '{{\App\Helpers\Helpers::translate('delete_confirmation')}}'.replace(':name', name);
            formEl.action = url;
            modal.show();
        });
    });
});
</script>
@endpush

