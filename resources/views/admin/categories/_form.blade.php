{{-- Shared form partial for create & edit --}}

<div class="row g-3">
    {{-- Name Arabic --}}
    <div class="col-md-6">
        <label for="name_ar" class="form-label fw-semibold">
            {{ \App\Helpers\Helpers::translate('name_ar') }} <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="name_ar"
               id="name_ar"
               dir="rtl"
               class="form-control @error('name_ar') is-invalid @enderror"
               value="{{ old('name_ar', $category->name_ar ?? '') }}"
               required>
        @error('name_ar')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Name English --}}
    <div class="col-md-6">
        <label for="name_en" class="form-label fw-semibold">
            {{ \App\Helpers\Helpers::translate('name_en') }}
        </label>
        <input type="text"
               name="name_en"
               id="name_en"
               class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en', $category->name_en ?? '') }}">
        @error('name_en')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Parent Category --}}
    <div class="col-md-6">
        <label for="parent_id" class="form-label fw-semibold">
            {{ \App\Helpers\Helpers::translate('parent_category') }}
        </label>
        <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
            <option value="">— {{ \App\Helpers\Helpers::translate('no_parent') }} —</option>
            @foreach($parentCategories as $parent)
                <option value="{{ $parent->id }}"
                    {{ old('parent_id', $category->parent_id ?? '') == $parent->id ? 'selected' : '' }}>
                    {{ $parent->name_ar }}{{ $parent->name_en ? ' / ' . $parent->name_en : '' }}
                </option>
            @endforeach
        </select>
        @error('parent_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Sort Order --}}
    <div class="col-md-3">
        <label for="sort_order" class="form-label fw-semibold">
            {{ \App\Helpers\Helpers::translate('sort_order') }}
        </label>
        <input type="number"
               name="sort_order"
               id="sort_order"
               min="0"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $category->sort_order ?? 0) }}">
        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is Active --}}
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input"
                   type="checkbox"
                   role="switch"
                   name="is_active"
                   id="is_active"
                   value="1"
                   {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="is_active">
                {{ \App\Helpers\Helpers::translate('active') }}
            </label>
        </div>
    </div>

    {{-- Image Upload --}}
    <div class="col-12">
        <label for="image" class="form-label fw-semibold">
            {{ \App\Helpers\Helpers::translate('category_image') }}
        </label>

        {{-- Current image preview (edit mode) --}}
        @if(!empty($category->image))
            <div class="mb-2">
                <img src="{{ asset('storage/' . $category->image) }}"
                     alt="{{ $category->name_ar }}"
                     class="rounded border"
                     style="height: 80px; width: 80px; object-fit: cover;">
                <small class="text-muted ms-2">{{ \App\Helpers\Helpers::translate('current_image') }}</small>
            </div>
        @endif

        <input type="file"
               name="image"
               id="image"
               accept="image/*"
               class="form-control @error('image') is-invalid @enderror">
        <small class="text-muted">{{ \App\Helpers\Helpers::translate('image_hint') }}</small>
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        {{-- Image live preview --}}
        <div id="imagePreviewContainer" class="mt-2 d-none">
            <img id="imagePreview" src="#" alt="preview"
                 class="rounded border"
                 style="height: 80px; width: 80px; object-fit: cover;">
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('image').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (ev) {
            document.getElementById('imagePreview').src = ev.target.result;
            document.getElementById('imagePreviewContainer').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>
@endpush

