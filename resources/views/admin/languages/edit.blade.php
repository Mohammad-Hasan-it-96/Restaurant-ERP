@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('edit_language'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('admin.languages.index') }}" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0">{{\App\Helpers\Helpers::translate('edit_language')}}</h2>
            </div>
            
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.languages.update', $language->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('POST')
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label">{{\App\Helpers\Helpers::translate('language_name')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-translate"></i></span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $language->name) }}" required>
                                </div>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="code" class="form-label">{{\App\Helpers\Helpers::translate('language_code')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-code-slash"></i></span>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                           id="code" name="code" value="{{ old('code', $language->code) }}" required>
                                </div>
                                <div class="form-text">ISO 639-1 language code (e.g. en, fr, es)</div>
                                @error('code')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="flag" class="form-label">{{\App\Helpers\Helpers::translate('flag_image')}}</label>
                                @if($language->flag_path)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $language->flag_path) }}" alt="{{ $language->name }}" class="rounded border" style="height: 40px;">
                                </div>
                                @endif
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-flag"></i></span>
                                    <input type="file" class="form-control @error('flag') is-invalid @enderror" 
                                           id="flag" name="flag" accept="image/*">
                                </div>
                                <div class="form-text">{{\App\Helpers\Helpers::translate('leave_empty_to_keep_current')}}</div>
                                @error('flag')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input @error('status') is-invalid @enderror" type="checkbox" 
                                           id="status" name="status" value="1" {{ old('status', $language->status) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status">{{\App\Helpers\Helpers::translate('active')}}</label>
                                </div>
                                <div class="form-text">{{\App\Helpers\Helpers::translate('enable_or_disable_language')}}</div>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input @error('is_default') is-invalid @enderror" type="checkbox" 
                                           id="is_default" name="is_default" value="1" {{ old('is_default', $language->is_default) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_default">{{\App\Helpers\Helpers::translate('set_as_default')}}</label>
                                </div>
                                <div class="form-text">{{\App\Helpers\Helpers::translate('default_language_info')}}</div>
                                @error('is_default')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Add this after the is_default checkbox -->
                            <div class="col-md-12 mt-3">
                                <label for="direction" class="form-label">{{\App\Helpers\Helpers::translate('direction')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-text-left"></i></span>
                                    <select class="form-select @error('direction') is-invalid @enderror" id="direction" name="direction" required>
                                        <option value="ltr" {{ old('direction', $language->direction) == 'ltr' ? 'selected' : '' }}>{{\App\Helpers\Helpers::translate('ltr')}}</option>
                                        <option value="rtl" {{ old('direction', $language->direction) == 'rtl' ? 'selected' : '' }}>{{\App\Helpers\Helpers::translate('rtl')}}</option>
                                    </select>
                                </div>
                                <div class="form-text">{{\App\Helpers\Helpers::translate('select_text_direction')}}</div>
                                @error('direction')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-2"></i>{{\App\Helpers\Helpers::translate('update_language')}}
                                </button>
                                <a href="{{ route('admin.languages.index') }}" class="btn btn-outline-secondary px-4">
                                    <i class="bi bi-x-circle me-2"></i>{{\App\Helpers\Helpers::translate('cancel')}}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Configure Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 5000
    };
    
    // Display Toastr messages for validation errors
    @if ($errors->any())
        @foreach ($errors->all() as $error)
            toastr.error('{{ $error }}');
        @endforeach
    @endif
    
    // Display Toastr messages for success
    @if (session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    
    // Display Toastr messages for error
    @if (session('error'))
        toastr.error('{{ session('error') }}');
    @endif
</script>
@endpush

@push('styles')
<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush