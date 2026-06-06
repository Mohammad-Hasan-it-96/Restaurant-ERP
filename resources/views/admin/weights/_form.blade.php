<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label for="name" class="form-label fw-semibold">
            {{ __('app.weight_name') }} <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="name"
               id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $weight->name ?? '') }}"
               placeholder="{{ __('app.weight_name_placeholder') }}"
               required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Value (kg) --}}
    <div class="col-md-6">
        <label for="value_kg" class="form-label fw-semibold">
            {{ __('app.value_kg') }} <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="number"
                   name="value_kg"
                   id="value_kg"
                   step="0.001"
                   min="0.001"
                   class="form-control @error('value_kg') is-invalid @enderror"
                   value="{{ old('value_kg', $weight->value_kg ?? '') }}"
                   required>
            <span class="input-group-text">kg</span>
        </div>
        @error('value_kg')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Sort Order --}}
    <div class="col-md-6">
        <label for="sort_order" class="form-label fw-semibold">
            {{ \App\Helpers\Helpers::translate('sort_order') }}
        </label>
        <input type="number"
               name="sort_order"
               id="sort_order"
               min="0"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $weight->sort_order ?? 0) }}">
        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is Active --}}
    <div class="col-12 d-flex align-items-center">
        <div class="form-check form-switch">
            <input class="form-check-input"
                   type="checkbox"
                   role="switch"
                   name="is_active"
                   id="is_active"
                   value="1"
                   {{ old('is_active', $weight->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="is_active">
                {{ \App\Helpers\Helpers::translate('active') }}
            </label>
        </div>
    </div>

</div>
