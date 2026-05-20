@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('edit_product'))

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-4">{{\App\Helpers\Helpers::translate('edit_product')}}</h5>

            <form method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="_back" value="{{ $back ?? route('admin.products.index') }}">

                <div class="row g-3">
                    {{-- name_ar --}}
                    <div class="col-md-6">
                        <label for="name_ar" class="form-label">
                            {{\App\Helpers\Helpers::translate('product_name')}} (AR) <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name_ar') is-invalid @enderror"
                               id="name_ar" name="name_ar" value="{{ old('name_ar', $product->name_ar) }}" required>
                        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- name_en --}}
                    <div class="col-md-6">
                        <label for="name_en" class="form-label">{{\App\Helpers\Helpers::translate('product_name')}} (EN)</label>
                        <input type="text" class="form-control @error('name_en') is-invalid @enderror"
                               id="name_en" name="name_en" value="{{ old('name_en', $product->name_en) }}">
                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- category --}}
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">{{\App\Helpers\Helpers::translate('category')}}</label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">{{\App\Helpers\Helpers::translate('no_category')}}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name_ar ?? $cat->name_en ?? $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- price --}}
                    <div class="col-md-3">
                        <label for="price" class="form-label">{{\App\Helpers\Helpers::translate('price')}} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0"
                               class="form-control @error('price') is-invalid @enderror"
                               id="price" name="price" value="{{ old('price', $product->price) }}" required>
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- discount_price --}}
                    <div class="col-md-3">
                        <label for="discount_price" class="form-label">{{\App\Helpers\Helpers::translate('discount_price')}}</label>
                        <input type="number" step="0.01" min="0"
                               class="form-control @error('discount_price') is-invalid @enderror"
                               id="discount_price" name="discount_price" value="{{ old('discount_price', $product->discount_price) }}">
                        @error('discount_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- sort_order --}}
                    <div class="col-md-3">
                        <label for="sort_order" class="form-label">{{\App\Helpers\Helpers::translate('sort_order')}}</label>
                        <input type="number" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               id="sort_order" name="sort_order" value="{{ old('sort_order', $product->sort_order) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- current image + upload --}}
                    <div class="col-md-6">
                        <label for="image" class="form-label">{{\App\Helpers\Helpers::translate('image')}}</label>
                        @if($product->image)
                            <div class="mb-2">
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->display_name }}"
                                     class="rounded" style="height:80px;object-fit:cover;">
                                <small class="text-muted d-block mt-1">
                                    {{\App\Helpers\Helpers::translate('leave_empty_to_keep_current')}}
                                </small>
                            </div>
                        @endif
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
                                       value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_available">
                                    {{\App\Helpers\Helpers::translate('available')}}
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_featured" value="0">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                       value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">
                                    {{\App\Helpers\Helpers::translate('featured')}}
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                       value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
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
                                  id="details" name="details" rows="3">{{ old('details', $product->details) }}</textarea>
                        @error('details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-save me-2"></i>{{\App\Helpers\Helpers::translate('update_product')}}
                        </button>
                        <a href="{{ $back ?? route('admin.products.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>{{\App\Helpers\Helpers::translate('cancel')}}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

