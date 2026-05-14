@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('system_configs'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ \App\Helpers\Helpers::translate('system_configs') }}</h1>
                    <p class="text-muted mb-0">{{ \App\Helpers\Helpers::translate('manage_system_configurations') }}</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                    <i class="bi bi-plus-circle me-1"></i> {{ \App\Helpers\Helpers::translate('add_new') }}
                </button>
            </div>
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
        // Group → badge colour mapping
        $groupColors = ['restaurant' => 'success', 'general' => 'primary'];
        // Keys that store JSON (show formatted hint instead of raw JSON)
        $jsonKeys = ['opening_hours', 'rejection_reasons'];
    @endphp

    {{-- Group cards --}}
    <div class="row g-4 mb-4">
        @foreach($groups as $grp)
        @php
            $grpColor  = $groupColors[$grp] ?? 'secondary';
            $grpCount  = $configs->where('group', $grp)->count();
        @endphp
        <div class="col-md-4 col-sm-6">
            <a href="{{ route('admin.configs.group', $grp) }}" class="text-decoration-none">
                <div class="card shadow-sm border-{{ $grpColor }} h-100 config-group-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-{{ $grpColor }} bg-opacity-10 p-3">
                            <i class="bi {{ $grp === 'restaurant' ? 'bi-shop' : 'bi-gear' }} text-{{ $grpColor }} fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-{{ $grpColor }}">{{ ucfirst($grp) }}</h6>
                            <small class="text-muted">{{ $grpCount }} {{ __('app.configurations') }}</small>
                        </div>
                        <i class="bi bi-chevron-left ms-auto text-muted"></i>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Full table --}}
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ \App\Helpers\Helpers::translate('all_configurations') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ \App\Helpers\Helpers::translate('group') }}</th>
                            <th>{{ \App\Helpers\Helpers::translate('key') }}</th>
                            <th>{{ \App\Helpers\Helpers::translate('value') }}</th>
                            <th class="text-end pe-4">{{ \App\Helpers\Helpers::translate('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($configs as $config)
                        @php
                            $gc = $groupColors[$config->group] ?? 'secondary';
                            $isJson = in_array($config->key, $jsonKeys);
                            if ($isJson) {
                                $preview = '{ JSON }';
                            } elseif (in_array($config->value, ['true','false','1','0','yes','no','on','off'], true)) {
                                $preview = $config->value;
                            } elseif (strlen($config->value ?? '') > 60) {
                                $preview = mb_substr($config->value, 0, 60) . '…';
                            } else {
                                $preview = $config->value ?? '—';
                            }
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-{{ $gc }}">{{ $config->group }}</span>
                            </td>
                            <td class="fw-semibold font-monospace small">{{ $config->key }}</td>
                            <td class="text-muted small">
                                @if($isJson)
                                    <span class="badge bg-light text-secondary border">JSON</span>
                                @elseif(in_array($config->value, ['true','1','yes','on'], true))
                                    <span class="badge bg-success">{{ $config->value }}</span>
                                @elseif(in_array($config->value, ['false','0','no','off'], true))
                                    <span class="badge bg-secondary">{{ $config->value }}</span>
                                @else
                                    {{ $preview }}
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.configs.group', $config->group) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>{{ \App\Helpers\Helpers::translate('edit') }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
                        {{ \App\Helpers\Helpers::translate('add_new_config') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ \App\Helpers\Helpers::translate('key') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('key') is-invalid @enderror"
                               name="key" value="{{ old('key') }}" required>
                        @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ \App\Helpers\Helpers::translate('value') }}</label>
                        <textarea class="form-control @error('value') is-invalid @enderror"
                                  name="value" rows="3">{{ old('value') }}</textarea>
                        @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ \App\Helpers\Helpers::translate('group') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('group') is-invalid @enderror"
                                id="groupSelectMain" name="group" required>
                            @foreach($groups as $grp)
                                <option value="{{ $grp }}">{{ ucfirst($grp) }}</option>
                            @endforeach
                            <option value="__new__">+ {{ \App\Helpers\Helpers::translate('new_group') }}</option>
                        </select>
                        @error('group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3 d-none" id="newGroupField">
                        <label class="form-label">{{ \App\Helpers\Helpers::translate('new_group_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newGroupInput" name="new_group">
                    </div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sel   = document.getElementById('groupSelectMain');
    const field = document.getElementById('newGroupField');
    const inp   = document.getElementById('newGroupInput');

    sel.addEventListener('change', function () {
        if (this.value === '__new__') {
            field.classList.remove('d-none');
            inp.setAttribute('required', 'required');
        } else {
            field.classList.add('d-none');
            inp.removeAttribute('required');
        }
    });
});
</script>
@endpush
@endsection

