@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('system_configs') . ' - ' . ucfirst($group))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ ucfirst($group) }} {{ \App\Helpers\Helpers::translate('configurations') }}</h1>
                    <p class="text-muted">{{ \App\Helpers\Helpers::translate('manage_group_configurations') }}</p>
                </div>
                <a href="{{ route('admin.configs.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> {{ \App\Helpers\Helpers::translate('back') }}
                </a>
            </div>
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
                    <h5 class="mb-0">{{ ucfirst($group) }} {{ \App\Helpers\Helpers::translate('settings') }}</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                        <i class="bi bi-plus-circle me-1"></i> {{ \App\Helpers\Helpers::translate('add_new') }}
                    </button>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.configs.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            @foreach($configs as $config)
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="config_{{ $config->key }}" class="form-label">
                                        {{ \App\Helpers\Helpers::translate($config->key, [], null, ucwords(str_replace('_', ' ', $config->key))) }}
                                    </label>
                                    
                                    @if(in_array($config->key, ['maintenance_mode', 'analytics_enabled', 'allow_guest_checkout', 'two_factor_enabled', 'require_password_confirmation']))
                                        <select class="form-select" id="config_{{ $config->key }}" name="config_{{ $config->key }}">
                                            <option value="on" {{ $config->value == 'on' ? 'selected' : '' }}>{{ \App\Helpers\Helpers::translate('on') }}</option>
                                            <option value="off" {{ $config->value == 'off' ? 'selected' : '' }}>{{ \App\Helpers\Helpers::translate('off') }}</option>
                                        </select>
                                    @elseif(in_array($config->key, ['active_dashboard']))
                                        <select class="form-select" id="config_{{ $config->key }}" name="config_{{ $config->key }}">
                                            {{-- Use translation helper for option text --}}
                                            <option value="blade" {{ $config->value == 'blade' ? 'selected' : '' }}>{{ \App\Helpers\Helpers::translate('blade') }}</option>
                                            <option value="livewire" {{ $config->value == 'livewire' ? 'selected' : '' }}>{{ \App\Helpers\Helpers::translate('livewire') }}</option>
                                            <option value="filament" {{ $config->value == 'filament' ? 'selected' : '' }}>{{ \App\Helpers\Helpers::translate('filament') }}</option>
                                        </select>
                                    @elseif(in_array($config->key, ['custom_css', 'custom_js', 'home_banner_text']))
                                        <textarea class="form-control" id="config_{{ $config->key }}" name="config_{{ $config->key }}" rows="4">{{ $config->value }}</textarea>
                                    @elseif(str_contains($config->key, 'password'))
                                        <input type="password" class="form-control" id="config_{{ $config->key }}" name="config_{{ $config->key }}" value="{{ $config->value }}">
                                    @else
                                        <input type="text" class="form-control" id="config_{{ $config->key }}" name="config_{{ $config->key }}" value="{{ $config->value }}">
                                    @endif
                                    
                                    <small class="form-text text-muted">{{ \App\Helpers\Helpers::translate($config->key . '_help', [], null, '') }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> {{ \App\Helpers\Helpers::translate('save_changes') }}
                            </button>
                        </div>
                    </form>
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
                    <input type="hidden" name="group" value="{{ $group }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ \App\Helpers\Helpers::translate('cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ \App\Helpers\Helpers::translate('save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection