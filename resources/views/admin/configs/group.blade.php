@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('system_configs') . ' - ' . ucfirst($group))

@push('styles')
<style>
/* ── Opening Hours table ─────────────────────────────────── */
.oh-table { width:100%; border-collapse:separate; border-spacing:0 6px; }
.oh-table th { font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); padding:0 8px 4px; }
.oh-row td { padding:6px 8px; background:var(--sidebar-hover); }
.oh-row td:first-child { border-radius:8px 0 0 8px; }
.oh-row td:last-child  { border-radius:0 8px 8px 0; }
.oh-row.closed td { opacity:.5; }
.oh-toggle { width:42px; height:22px; position:relative; }
.oh-toggle input { opacity:0; width:0; height:0; }
.oh-toggle .slider {
    position:absolute; cursor:pointer; inset:0;
    background:#cbd5e1; border-radius:22px; transition:.25s;
}
.oh-toggle .slider:before {
    content:''; position:absolute;
    width:16px; height:16px; left:3px; bottom:3px;
    background:#fff; border-radius:50%; transition:.25s;
}
.oh-toggle input:checked + .slider { background:var(--primary,#4f46e5); }
.oh-toggle input:checked + .slider:before { transform:translateX(20px); }

/* ── Rejection reasons list ──────────────────────────────── */
.reason-list { display:flex; flex-direction:column; gap:8px; }
.reason-item { display:flex; gap:8px; align-items:center; }
.reason-item input { flex:1; }

/* ── Logo preview ─────────────────────────────────────────── */
.logo-preview-wrap {
    width:120px; height:120px; border:2px dashed var(--border-color);
    border-radius:12px; display:flex; align-items:center; justify-content:center;
    overflow:hidden; background:var(--sidebar-hover); cursor:pointer;
    position:relative; transition:border-color .2s;
}
.logo-preview-wrap:hover { border-color:var(--primary,#4f46e5); }
.logo-preview-wrap img { width:100%; height:100%; object-fit:contain; }
.logo-preview-wrap .logo-placeholder { color:var(--text-muted); font-size:2rem; }
.logo-upload-overlay {
    position:absolute; inset:0; background:rgba(0,0,0,.45);
    display:none; align-items:center; justify-content:center; border-radius:10px;
}
.logo-preview-wrap:hover .logo-upload-overlay { display:flex; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0">{{ ucfirst($group) }} {{ \App\Helpers\Helpers::translate('configurations') }}</h1>
            <p class="text-muted mb-0">{{ \App\Helpers\Helpers::translate('manage_group_configurations') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                <i class="bi bi-plus-circle me-1"></i> {{ \App\Helpers\Helpers::translate('add_new') }}
            </button>
            <a href="{{ route('admin.configs.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ \App\Helpers\Helpers::translate('back') }}
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @php
        $boolKeys     = ['is_accepting_orders'];
        $numberKeys   = ['customer_cancel_before_minutes'];
        $textareaKeys = ['order_closed_message', 'delivery_note'];
        $specialKeys  = ['opening_hours', 'rejection_reasons', 'restaurant_logo'];
        $ltrKeys      = ['restaurant_name_en', 'restaurant_phone', 'restaurant_whatsapp'];
        $arKeys       = ['restaurant_name_ar'];
    @endphp

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <span class="badge bg-success me-2">{{ $group }}</span>
                {{ \App\Helpers\Helpers::translate('settings') }}
            </h5>
        </div>
        <div class="card-body">
            {{-- multipart needed for logo file upload --}}
            <form action="{{ route('admin.configs.update') }}" method="POST"
                  enctype="multipart/form-data" id="configsForm">
                @csrf
                @method('PUT')

                <div class="row g-4">
                @foreach($configs as $config)

                @php
                    $label    = \App\Helpers\Helpers::translate($config->key, [], null, ucwords(str_replace('_', ' ', $config->key)));
                    $helpText = \App\Helpers\Helpers::translate($config->key . '_help', [], null, '');
                @endphp

                {{-- ══════════ OPENING HOURS ══════════ --}}
                @if($config->key === 'opening_hours')
                @php
                    $ohData = json_decode($config->value ?? '{}', true) ?? [];
                    $days   = [
                        'saturday'  => 'السبت',
                        'sunday'    => 'الأحد',
                        'monday'    => 'الاثنين',
                        'tuesday'   => 'الثلاثاء',
                        'wednesday' => 'الأربعاء',
                        'thursday'  => 'الخميس',
                        'friday'    => 'الجمعة',
                    ];
                @endphp
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <label class="form-label fw-semibold mb-0">
                            {{ $label }}
                            <code class="text-muted fw-normal small ms-1">{{ $config->key }}</code>
                        </label>
                    </div>

                    {{-- Hidden textarea with JSON (submitted to server) --}}
                    <textarea name="config_opening_hours" id="config_opening_hours"
                              class="d-none">{{ $config->value }}</textarea>

                    <div class="table-responsive rounded border">
                        <table class="oh-table mb-0">
                            <thead>
                                <tr>
                                    <th>اليوم</th>
                                    <th>مفتوح</th>
                                    <th>من</th>
                                    <th>إلى</th>
                                </tr>
                            </thead>
                            <tbody id="ohTableBody">
                            @foreach($days as $dayKey => $dayLabel)
                            @php
                                $d = $ohData[$dayKey] ?? ['is_open' => true, 'from' => '08:00', 'to' => '00:00'];
                                $isOpen = !empty($d['is_open']);
                            @endphp
                            <tr class="oh-row {{ $isOpen ? '' : 'closed' }}" data-day="{{ $dayKey }}">
                                <td class="fw-semibold" style="min-width:80px">{{ $dayLabel }}</td>
                                <td>
                                    <label class="oh-toggle">
                                        <input type="checkbox" class="oh-open-check"
                                               id="oh_{{ $dayKey }}_open"
                                               {{ $isOpen ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm oh-from"
                                           id="oh_{{ $dayKey }}_from"
                                           value="{{ $d['from'] ?? '08:00' }}"
                                           style="min-width:100px"
                                           {{ $isOpen ? '' : 'disabled' }}>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm oh-to"
                                           id="oh_{{ $dayKey }}_to"
                                           value="{{ $d['to'] ?? '00:00' }}"
                                           style="min-width:100px"
                                           {{ $isOpen ? '' : 'disabled' }}>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($helpText)<div class="form-text text-muted mt-1">{{ $helpText }}</div>@endif
                </div>

                {{-- ══════════ REJECTION REASONS ══════════ --}}
                @elseif($config->key === 'rejection_reasons')
                @php
                    $reasons = json_decode($config->value ?? '[]', true) ?? [];
                @endphp
                <div class="col-12 col-md-8">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <label class="form-label fw-semibold mb-0">
                            {{ $label }}
                            <code class="text-muted fw-normal small ms-1">{{ $config->key }}</code>
                        </label>
                    </div>

                    {{-- Hidden JSON input --}}
                    <input type="hidden" name="config_rejection_reasons"
                           id="config_rejection_reasons" value="{{ $config->value }}">

                    <div class="reason-list" id="reasonList">
                        @foreach($reasons as $i => $reason)
                        <div class="reason-item">
                            <input type="text" class="form-control reason-input"
                                   value="{{ $reason }}"
                                   placeholder="سبب الرفض...">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-reason"
                                    title="حذف">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addReasonBtn">
                        <i class="bi bi-plus me-1"></i>إضافة سبب
                    </button>
                    @if($helpText)<div class="form-text text-muted mt-1">{{ $helpText }}</div>@endif
                </div>

                {{-- ══════════ RESTAURANT LOGO ══════════ --}}
                @elseif($config->key === 'restaurant_logo')
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <label class="form-label fw-semibold mb-0">
                            {{ $label }}
                            <code class="text-muted fw-normal small ms-1">{{ $config->key }}</code>
                        </label>
                    </div>

                    <div class="d-flex align-items-start gap-4 flex-wrap">
                        {{-- Preview --}}
                        <div class="logo-preview-wrap" id="logoPreviewWrap"
                             onclick="document.getElementById('logoFileInput').click()"
                             title="انقر لتغيير الشعار">
                            @if($config->value)
                                <img src="{{ asset('storage/' . ltrim($config->value, '/')) }}"
                                     alt="Logo" id="logoPreviewImg"
                                     onerror="this.style.display='none';document.getElementById('logoPlaceholder').style.display='flex'">
                                <span id="logoPlaceholder" style="display:none">
                                    <i class="bi bi-image"></i>
                                </span>
                            @else
                                <span class="logo-placeholder" id="logoPlaceholder">
                                    <i class="bi bi-image"></i>
                                </span>
                            @endif
                            <div class="logo-upload-overlay text-white small text-center px-2">
                                <i class="bi bi-camera fs-4 d-block mb-1"></i>تغيير
                            </div>
                        </div>

                        <div class="flex-fill">
                            {{-- File upload (stores new logo) --}}
                            <input type="file" name="restaurant_logo_file" id="logoFileInput"
                                   accept="image/*" class="d-none">
                            <label for="logoFileInput" class="btn btn-sm btn-outline-primary mb-2">
                                <i class="bi bi-upload me-1"></i>رفع صورة جديدة
                            </label>
                            <div class="form-text text-muted mb-2">PNG, JPG, WebP — حجم أقصى 2 ميجا</div>

                            {{-- Manual path override --}}
                            <label class="form-label small text-muted mb-1">المسار الحالي في التخزين</label>
                            <input type="text" class="form-control form-control-sm"
                                   name="config_restaurant_logo"
                                   id="config_restaurant_logo"
                                   value="{{ $config->value }}"
                                   placeholder="logos/your-logo.png">
                        </div>
                    </div>
                    @if($helpText)<div class="form-text text-muted mt-1">{{ $helpText }}</div>@endif
                </div>

                {{-- ══════════ BOOLEAN ══════════ --}}
                @elseif(in_array($config->key, $boolKeys))
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <label for="config_{{ $config->key }}" class="form-label fw-semibold mb-0">
                            {{ $label }}
                            <code class="text-muted fw-normal small ms-1">{{ $config->key }}</code>
                        </label>
                    </div>
                    <select class="form-select" id="config_{{ $config->key }}" name="config_{{ $config->key }}">
                        <option value="true"  {{ in_array($config->value, ['true','1','yes','on']) ? 'selected' : '' }}>
                            ✅ {{ __('app.yes') }} ({{ __('app.active') }})
                        </option>
                        <option value="false" {{ !in_array($config->value, ['true','1','yes','on']) ? 'selected' : '' }}>
                            ❌ {{ __('app.no') }} ({{ __('app.inactive') }})
                        </option>
                    </select>
                    @if($helpText)<div class="form-text text-muted">{{ $helpText }}</div>@endif
                </div>

                {{-- ══════════ NUMBER ══════════ --}}
                @elseif(in_array($config->key, $numberKeys))
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <label for="config_{{ $config->key }}" class="form-label fw-semibold mb-0">
                            {{ $label }}
                            <code class="text-muted fw-normal small ms-1">{{ $config->key }}</code>
                        </label>
                    </div>
                    <div class="input-group">
                        <input type="number" min="0" class="form-control"
                               id="config_{{ $config->key }}" name="config_{{ $config->key }}"
                               value="{{ $config->value }}">
                        <span class="input-group-text text-muted small">دقيقة</span>
                    </div>
                    @if($helpText)<div class="form-text text-muted">{{ $helpText }}</div>@endif
                </div>

                {{-- ══════════ TEXTAREA ══════════ --}}
                @elseif(in_array($config->key, $textareaKeys))
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <label for="config_{{ $config->key }}" class="form-label fw-semibold mb-0">
                            {{ $label }}
                            <code class="text-muted fw-normal small ms-1">{{ $config->key }}</code>
                        </label>
                    </div>
                    <textarea class="form-control" rows="3"
                              id="config_{{ $config->key }}" name="config_{{ $config->key }}">{{ $config->value }}</textarea>
                    @if($helpText)<div class="form-text text-muted">{{ $helpText }}</div>@endif
                </div>

                {{-- ══════════ DEFAULT TEXT INPUT ══════════ --}}
                @else
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <label for="config_{{ $config->key }}" class="form-label fw-semibold mb-0">
                            {{ $label }}
                            <code class="text-muted fw-normal small ms-1">{{ $config->key }}</code>
                        </label>
                    </div>
                    <input type="text" class="form-control"
                           id="config_{{ $config->key }}" name="config_{{ $config->key }}"
                           value="{{ $config->value }}"
                           dir="{{ in_array($config->key, $ltrKeys) ? 'ltr' : (in_array($config->key, $arKeys) ? 'rtl' : 'auto') }}">
                    @if($helpText)<div class="form-text text-muted">{{ $helpText }}</div>@endif
                </div>
                @endif

                @endforeach
                </div>{{-- /row --}}

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> {{ \App\Helpers\Helpers::translate('save_changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Config Modal --}}
<div class="modal fade" id="addConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.configs.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>
                        {{ \App\Helpers\Helpers::translate('add_config_to_group') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ \App\Helpers\Helpers::translate('key') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('key') is-invalid @enderror"
                               name="key" value="{{ old('key') }}" required
                               placeholder="e.g. social_media_link">
                        @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ \App\Helpers\Helpers::translate('value') }}</label>
                        <textarea class="form-control @error('value') is-invalid @enderror"
                                  name="value" rows="3">{{ old('value') }}</textarea>
                        @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <input type="hidden" name="group" value="{{ $group }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ \App\Helpers\Helpers::translate('cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>{{ \App\Helpers\Helpers::translate('save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ \App\Helpers\Helpers::translate('confirm_delete') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{ \App\Helpers\Helpers::translate('are_you_sure') }}
                {{ __('app.delete') }} "<strong id="deleteConfigKey"></strong>"?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{ \App\Helpers\Helpers::translate('cancel') }}
                </button>
                <form id="deleteConfigForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>{{ \App\Helpers\Helpers::translate('delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Delete modal ─────────────────────────────────────────── */
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfigModal'));
    const keyEl  = document.getElementById('deleteConfigKey');
    const formEl = document.getElementById('deleteConfigForm');
    document.querySelectorAll('.delete-config-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            keyEl.textContent = btn.dataset.key;
            formEl.action = `/admin/configs/${btn.dataset.id}`;
            deleteModal.show();
        });
    });

    /* ── Opening Hours UI ─────────────────────────────────────── */
    const ohHidden  = document.getElementById('config_opening_hours');
    const ohDays    = ['saturday','sunday','monday','tuesday','wednesday','thursday','friday'];

    function collectOpeningHours() {
        if (!ohHidden) return;
        const result = {};
        ohDays.forEach(day => {
            const chk  = document.getElementById(`oh_${day}_open`);
            const from = document.getElementById(`oh_${day}_from`);
            const to   = document.getElementById(`oh_${day}_to`);
            if (chk) result[day] = {
                is_open: chk.checked,
                from: from ? from.value : '00:00',
                to:   to   ? to.value   : '00:00',
            };
        });
        ohHidden.value = JSON.stringify(result, null, 2);
    }

    // Toggle row state when the checkbox changes
    document.querySelectorAll('.oh-open-check').forEach(chk => {
        chk.addEventListener('change', function () {
            const row  = this.closest('.oh-row');
            const from = row.querySelector('.oh-from');
            const to   = row.querySelector('.oh-to');
            if (this.checked) {
                row.classList.remove('closed');
                from.disabled = false;
                to.disabled   = false;
            } else {
                row.classList.add('closed');
                from.disabled = true;
                to.disabled   = true;
            }
        });
    });

    /* ── Rejection Reasons UI ──────────────────────────────────── */
    const reasonList   = document.getElementById('reasonList');
    const reasonHidden = document.getElementById('config_rejection_reasons');
    const addReasonBtn = document.getElementById('addReasonBtn');

    function collectReasons() {
        if (!reasonHidden || !reasonList) return;
        const inputs  = reasonList.querySelectorAll('.reason-input');
        const reasons = Array.from(inputs).map(i => i.value.trim()).filter(Boolean);
        reasonHidden.value = JSON.stringify(reasons);
    }

    function makeReasonItem(value = '') {
        const div = document.createElement('div');
        div.className = 'reason-item';
        div.innerHTML = `
            <input type="text" class="form-control reason-input" value="${value.replace(/"/g,'&quot;')}" placeholder="سبب الرفض...">
            <button type="button" class="btn btn-sm btn-outline-danger remove-reason" title="حذف">
                <i class="bi bi-x-lg"></i>
            </button>`;
        div.querySelector('.remove-reason').addEventListener('click', () => {
            div.remove();
        });
        return div;
    }

    // Wire up existing remove buttons
    document.querySelectorAll('.remove-reason').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.reason-item').remove());
    });

    if (addReasonBtn) {
        addReasonBtn.addEventListener('click', () => {
            reasonList.appendChild(makeReasonItem(''));
            reasonList.lastElementChild.querySelector('.reason-input').focus();
        });
    }

    /* ── Logo file preview ─────────────────────────────────────── */
    const logoInput = document.getElementById('logoFileInput');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                let img = document.getElementById('logoPreviewImg');
                const placeholder = document.getElementById('logoPlaceholder');
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'logoPreviewImg';
                    img.alt = 'Logo';
                    document.getElementById('logoPreviewWrap').prepend(img);
                }
                img.src       = e.target.result;
                img.style.display = '';
                if (placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(this.files[0]);
        });
    }

    /* ── Collect all dynamic fields before submit ──────────────── */
    const configsForm = document.getElementById('configsForm');
    if (configsForm) {
        configsForm.addEventListener('submit', function () {
            collectOpeningHours();
            collectReasons();
        });
    }
});
</script>
@endpush
@endsection

