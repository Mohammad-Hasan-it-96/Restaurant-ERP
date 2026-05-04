@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('login'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="text-center mb-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 70px; height: 70px; background-color: var(--primary); color: white;">
                    <i class="bi bi-shop fs-1"></i>
                </div>
                <h2 class="fw-bold">{{\App\Helpers\Helpers::translate('welcome_back')}}</h2>
                <p class="text-muted">{{\App\Helpers\Helpers::translate('sign_in_to_continue')}}</p>
            </div>

            <div class="card border-0 shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('auth.login') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label">{{\App\Helpers\Helpers::translate('email_address')}}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                       placeholder="your@email.com">
                            </div>
                            @error('email')
                            <div class="invalid-feedback d-block mt-1">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label">{{\App\Helpers\Helpers::translate('password')}}</label>
                                @if (Route::has('auth.forgot-password'))
                                <a class="text-decoration-none small" href="{{ route('auth.forgot-password') }}">
                                    {{\App\Helpers\Helpers::translate('forgot_password')}}
                                </a>
                                @endif
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-lock"></i></span>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                       name="password" required autocomplete="current-password" placeholder="••••••••">
                            </div>
                            @error('password')
                            <div class="invalid-feedback d-block mt-1">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                       {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    {{\App\Helpers\Helpers::translate('remember_me')}}
                                </label>
                            </div>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary py-2">
                                <i class="bi bi-box-arrow-in-right me-2"></i>{{\App\Helpers\Helpers::translate('sign_in')}}
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="mb-0">{{\App\Helpers\Helpers::translate('dont_have_account')}}
                                <a href="{{ route('auth.view_register') }}" class="text-decoration-none fw-medium">
                                    {{\App\Helpers\Helpers::translate('create_account')}}
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
