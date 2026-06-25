<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label for="name" class="form-label fw-semibold">
            اسم الخيار <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="name"
               id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $option->name ?? '') }}"
               placeholder="مثال: نوع الفرم، درجة النضج..."
               required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is Active --}}
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input"
                   type="checkbox"
                   role="switch"
                   name="is_active"
                   id="is_active"
                   value="1"
                   {{ old('is_active', $option->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="is_active">مفعّل</label>
        </div>
    </div>

    {{-- Values --}}
    <div class="col-12 mt-2">
        <label class="form-label fw-semibold">
            قيم الخيار <span class="text-danger">*</span>
        </label>
        <div id="values-list" class="d-flex flex-column gap-2 mb-2">
            @php
                $existingValues = old('values', isset($option) ? $option->values->toArray() : [['id'=>null,'name'=>'','sort_order'=>0]]);
            @endphp
            @foreach($existingValues as $i => $val)
            <div class="input-group value-row">
                <input type="hidden" name="values[{{ $i }}][id]" value="{{ $val['id'] ?? '' }}">
                <span class="input-group-text text-muted" style="font-size:.8rem; min-width:2.5rem;">{{ $i + 1 }}</span>
                <input type="text"
                       name="values[{{ $i }}][name]"
                       class="form-control @error('values.'.$i.'.name') is-invalid @enderror"
                       value="{{ $val['name'] ?? '' }}"
                       placeholder="اسم القيمة (مثال: ناعمة، متوسطة...)"
                       required>
                <input type="number"
                       name="values[{{ $i }}][sort_order]"
                       class="form-control"
                       style="max-width:90px;"
                       value="{{ $val['sort_order'] ?? $i }}"
                       placeholder="ترتيب"
                       min="0">
                <button type="button" class="btn btn-outline-danger remove-value-btn">
                    <i class="bi bi-trash"></i>
                </button>
                @error('values.'.$i.'.name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            @endforeach
        </div>
        <button type="button" id="add-value-btn" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>إضافة قيمة
        </button>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const list    = document.getElementById('values-list');
    const addBtn  = document.getElementById('add-value-btn');

    function reindex() {
        list.querySelectorAll('.value-row').forEach(function (row, i) {
            row.querySelector('.input-group-text').textContent = i + 1;
            row.querySelectorAll('input').forEach(function (inp) {
                inp.name = inp.name.replace(/values\[\d+\]/, 'values[' + i + ']');
            });
        });
    }

    addBtn.addEventListener('click', function () {
        const idx  = list.querySelectorAll('.value-row').length;
        const row  = document.createElement('div');
        row.className = 'input-group value-row';
        row.innerHTML =
            '<input type="hidden" name="values[' + idx + '][id]" value="">' +
            '<span class="input-group-text text-muted" style="font-size:.8rem;min-width:2.5rem;">' + (idx + 1) + '</span>' +
            '<input type="text" name="values[' + idx + '][name]" class="form-control" placeholder="اسم القيمة" required>' +
            '<input type="number" name="values[' + idx + '][sort_order]" class="form-control" style="max-width:90px;" value="' + idx + '" placeholder="ترتيب" min="0">' +
            '<button type="button" class="btn btn-outline-danger remove-value-btn"><i class="bi bi-trash"></i></button>';
        list.appendChild(row);
        row.querySelector('input[type="text"]').focus();
        bindRemove(row.querySelector('.remove-value-btn'));
    });

    function bindRemove(btn) {
        btn.addEventListener('click', function () {
            this.closest('.value-row').remove();
            reindex();
        });
    }

    list.querySelectorAll('.remove-value-btn').forEach(bindRemove);
})();
</script>
@endpush
