@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('system_configs'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">{{ \App\Helpers\Helpers::translate('system_configs') }}</h1>
            <p class="text-muted">{{ \App\Helpers\Helpers::translate('manage_system_configurations') }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ \App\Helpers\Helpers::translate('all_configurations') }}</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                        <i class="bi bi-plus-circle me-1"></i> {{ \App\Helpers\Helpers::translate('add_new') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ \App\Helpers\Helpers::translate('group') }}</th>
                                    <th>{{ \App\Helpers\Helpers::translate('key') }}</th>
                                    <th>{{ \App\Helpers\Helpers::translate('value') }}</th>
                                    <th>{{ \App\Helpers\Helpers::translate('actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($configs as $config)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $config->group }}</span>
                                    </td>
                                    <td>{{ $config->key }}</td>
                                    <td>
                                        @if(strlen($config->value) > 50)
                                            {{ substr($config->value, 0, 50) }}...
                                        @else
                                            {{ $config->value }}
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.configs.group', $config->group) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> {{ \App\Helpers\Helpers::translate('edit') }}
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
    </div>
</div>

<!-- Add Config Modal -->
<div class="modal fade" id="addConfigModal" tabindex="-1" aria-labelledby="addConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.configs.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addConfigModalLabel">{{ \App\Helpers\Helpers::translate('add_new_config') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="key" class="form-label">{{ \App\Helpers\Helpers::translate('key') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('key') is-invalid @enderror" id="key" name="key" value="{{ old('key') }}" required>
                        @error('key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="value" class="form-label">{{ \App\Helpers\Helpers::translate('value') }}</label>
                        <textarea class="form-control @error('value') is-invalid @enderror" id="value" name="value" rows="3">{{ old('value') }}</textarea>
                        @error('value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="group" class="form-label">{{ \App\Helpers\Helpers::translate('group') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('group') is-invalid @enderror" id="group" name="group" required>
                            @foreach($groups as $group)
                                <option value="{{ $group }}">{{ $group }}</option>
                            @endforeach
                            <option value="new">{{ \App\Helpers\Helpers::translate('new_group') }}</option>
                        </select>
                        @error('group')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 d-none" id="newGroupField">
                        <label for="new_group" class="form-label">{{ \App\Helpers\Helpers::translate('new_group_name') }}</label>
                        <input type="text" class="form-control" id="new_group" name="new_group">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ \App\Helpers\Helpers::translate('cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ \App\Helpers\Helpers::translate('save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const groupSelect = document.getElementById('group');
        const newGroupField = document.getElementById('newGroupField');
        
        groupSelect.addEventListener('change', function() {
            if (this.value === 'new') {
                newGroupField.classList.remove('d-none');
                document.getElementById('new_group').setAttribute('required', 'required');
            } else {
                newGroupField.classList.add('d-none');
                document.getElementById('new_group').removeAttribute('required');
            }
        });
    });
</script>
@endpush
@endsection