@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('add_product'))

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-4">{{\App\Helpers\Helpers::translate('add_product')}}</h5>

            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    {{-- name_ar --}}
                    <div class="col-md-6">
                        <label for="name_ar" class="form-label">
                            {{\App\Helpers\Helpers::translate('product_name')}} (AR) <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name_ar') is-invalid @enderror"
                               id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required>
                        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- name_en --}}
                    <div class="col-md-6">
                        <label for="name_en" class="form-label">{{\App\Helpers\Helpers::translate('product_name')}} (EN)</label>
                        <input type="text" class="form-control @error('name_en') is-invalid @enderror"
                               id="name_en" name="name_en" value="{{ old('name_en') }}">
                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- category --}}
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">{{\App\Helpers\Helpers::translate('category')}}</label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">{{\App\Helpers\Helpers::translate('no_category')}}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name_ar ?? $cat->name_en ?? $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- is_weight_based toggle --}}
                    @feature('products.weight_products')
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_weight_based" value="0">
                            <input class="form-check-input" type="checkbox" name="is_weight_based" id="is_weight_based"
                                   value="1" {{ old('is_weight_based') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_weight_based">
                                {{\App\Helpers\Helpers::translate('is_weight_based')}}
                            </label>
                        </div>
                    </div>
                    @endfeature

                    {{-- Normal price section (hidden when weight-based) --}}
                    <div id="normal-price-section" class="col-md-3">
                        <label for="price" class="form-label">{{\App\Helpers\Helpers::translate('price')}} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0"
                               class="form-control @error('price') is-invalid @enderror"
                               id="price" name="price" value="{{ old('price') }}">
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- discount_price (hidden when weight-based) --}}
                    <div id="discount-price-section" class="col-md-3">
                        <label for="discount_price" class="form-label">{{\App\Helpers\Helpers::translate('discount_price')}}</label>
                        <input type="number" step="0.01" min="0"
                               class="form-control @error('discount_price') is-invalid @enderror"
                               id="discount_price" name="discount_price" value="{{ old('discount_price') }}">
                        @error('discount_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Weight-based section (hidden when not weight-based) --}}
                    @feature('products.weight_products')
                    <div id="weight-based-section" class="col-12" style="display:none;">
                        <div class="card border-warning">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="price_per_kg" class="form-label">
                                            {{\App\Helpers\Helpers::translate('price_per_kg')}} <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0"
                                                   class="form-control @error('price_per_kg') is-invalid @enderror"
                                                   id="price_per_kg" name="price_per_kg" value="{{ old('price_per_kg') }}">
                                            <span class="input-group-text">/ kg</span>
                                        </div>
                                        @error('price_per_kg')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    @if($weights->isNotEmpty())
                                    <div class="col-md-8">
                                        <label class="form-label">{{\App\Helpers\Helpers::translate('available_weights')}}</label>
                                        <div class="d-flex flex-wrap gap-2 border rounded p-2">
                                            @foreach($weights as $weight)
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="checkbox"
                                                       name="weights[]" id="weight_{{ $weight->id }}"
                                                       value="{{ $weight->id }}"
                                                       {{ in_array($weight->id, old('weights', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="weight_{{ $weight->id }}">
                                                    {{ $weight->name }} ({{ $weight->value_kg }} kg)
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                        @error('weights')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    @else
                                    <div class="col-md-8">
                                        <p class="text-muted mt-4 mb-0">
                                            <i class="bi bi-info-circle me-1"></i>
                                            {{\App\Helpers\Helpers::translate('no_weights_defined')}}
                                            <a href="{{ route('admin.weights.create') }}" class="ms-1">{{\App\Helpers\Helpers::translate('add_weight')}}</a>
                                        </p>
                                    </div>
                                    @endif
                                </div>
                                <small class="text-muted">{{\App\Helpers\Helpers::translate('weight_based_hint')}}</small>
                            </div>
                        </div>
                    </div>
                    @endfeature

                    {{-- sort_order --}}
                    <div class="col-md-3">
                        <label for="sort_order" class="form-label">{{\App\Helpers\Helpers::translate('sort_order')}}</label>
                        <input type="number" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- image --}}
                    <div class="col-md-6">
                        <label for="image" class="form-label">{{\App\Helpers\Helpers::translate('image')}}</label>
                        <input type="file" accept="image/*"
                               class="form-control @error('image') is-invalid @enderror"
                               id="image" name="image">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Checkboxes --}}
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_available" value="0">
                                <input class="form-check-input" type="checkbox" name="is_available" id="is_available"
                                       value="1" {{ old('is_available', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_available">
                                    {{\App\Helpers\Helpers::translate('available')}}
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_featured" value="0">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                       value="1" {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">
                                    {{\App\Helpers\Helpers::translate('featured')}}
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                       value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    {{\App\Helpers\Helpers::translate('active')}}
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Legacy optional fields --}}
                    <div class="col-12">
                        <label for="details" class="form-label">{{\App\Helpers\Helpers::translate('description')}}</label>
                        <textarea class="form-control @error('details') is-invalid @enderror"
                                  id="details" name="details" rows="3">{{ old('details') }}</textarea>
                        @error('details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-save me-2"></i>{{\App\Helpers\Helpers::translate('save_product')}}
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>{{\App\Helpers\Helpers::translate('cancel')}}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const toggle = document.getElementById('is_weight_based');
    if (!toggle) return; // weight-products feature disabled — nothing to toggle
    const normalPrice = document.getElementById('normal-price-section');
    const discountPrice = document.getElementById('discount-price-section');
    const weightSection = document.getElementById('weight-based-section');
    const priceInput = document.getElementById('price');

    function applyToggle() {
        if (toggle.checked) {
            normalPrice.style.display = 'none';
            discountPrice.style.display = 'none';
            weightSection.style.display = '';
            priceInput.removeAttribute('required');
        } else {
            normalPrice.style.display = '';
            discountPrice.style.display = '';
            weightSection.style.display = 'none';
            priceInput.setAttribute('required', '');
        }
    }

    toggle.addEventListener('change', applyToggle);
    applyToggle();
})();
</script>
@endpush
@endsection

