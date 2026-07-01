@extends('installer.layout')
@section('title', 'Restaurant info')
@section('step', '3')

@section('content')
    <p class="text-muted">Basic details for your restaurant. You can change these later in admin settings.</p>

    <form method="POST" action="{{ route('install.restaurant.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name (Arabic) *</label>
                <input name="restaurant_name_ar" class="form-control" dir="rtl"
                       value="{{ old('restaurant_name_ar', $data['restaurant_name_ar'] ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Name (English)</label>
                <input name="restaurant_name_en" class="form-control"
                       value="{{ old('restaurant_name_en', $data['restaurant_name_en'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone *</label>
                <input name="restaurant_phone" class="form-control"
                       value="{{ old('restaurant_phone', $data['restaurant_phone'] ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">WhatsApp</label>
                <input name="restaurant_whatsapp" class="form-control"
                       value="{{ old('restaurant_whatsapp', $data['restaurant_whatsapp'] ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Logo <span class="text-muted">(optional, ≤ 2 MB)</span></label>
                <input name="logo" type="file" accept="image/*" class="form-control">
            </div>

            <div class="col-12"><hr class="my-2"><h6 class="text-muted mb-0">Localization &amp; branding</h6></div>

            <div class="col-md-6">
                <label class="form-label">Timezone *</label>
                <select name="timezone" class="form-select" required>
                    @foreach (timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" @selected(old('timezone', $data['timezone'] ?? 'Asia/Damascus') === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Default language *</label>
                <select name="default_language" class="form-select" required>
                    @foreach (['ar' => 'Arabic', 'en' => 'English'] as $code => $label)
                        <option value="{{ $code }}" @selected(old('default_language', $data['default_language'] ?? 'ar') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Currency code *</label>
                <input name="currency_code" class="form-control" maxlength="8"
                       value="{{ old('currency_code', $data['currency_code'] ?? 'USD') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Symbol *</label>
                <input name="currency_symbol" class="form-control" maxlength="8"
                       value="{{ old('currency_symbol', $data['currency_symbol'] ?? '$') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Symbol position *</label>
                <select name="currency_position" class="form-select" required>
                    @foreach (['suffix' => 'After amount (100 $)', 'prefix' => 'Before amount ($ 100)'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('currency_position', $data['currency_position'] ?? 'suffix') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Decimals *</label>
                <input name="currency_decimals" type="number" min="0" max="4" class="form-control"
                       value="{{ old('currency_decimals', $data['currency_decimals'] ?? '0') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Primary brand color *</label>
                <input name="theme_primary" type="color" class="form-control form-control-color"
                       value="{{ old('theme_primary', $data['theme_primary'] ?? '#C0392B') }}" required>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('install.app') }}" class="btn btn-outline-secondary">Back</a>
            <button class="btn btn-primary">Continue <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </form>
@endsection
