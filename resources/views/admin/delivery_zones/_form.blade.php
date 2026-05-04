{{-- area_name --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('app.area_name') }} <span class="text-danger">*</span></label>
    <input type="text" name="area_name" class="form-control @error('area_name') is-invalid @enderror"
           value="{{ old('area_name', $deliveryZone->area_name ?? '') }}" required>
    @error('area_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- estimated_fee --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('app.estimated_fee') }} <span class="text-danger">*</span></label>
    <input type="number" name="estimated_fee" step="0.01" min="0"
           class="form-control @error('estimated_fee') is-invalid @enderror"
           value="{{ old('estimated_fee', $deliveryZone->estimated_fee ?? '') }}" required>
    @error('estimated_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- sort_order --}}
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('app.sort_order') }}</label>
    <input type="number" name="sort_order" min="0"
           class="form-control @error('sort_order') is-invalid @enderror"
           value="{{ old('sort_order', $deliveryZone->sort_order ?? 0) }}">
    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- is_active --}}
<div class="mb-3">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
               {{ old('is_active', $deliveryZone->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="is_active">{{ __('app.active') }}</label>
    </div>
</div>

