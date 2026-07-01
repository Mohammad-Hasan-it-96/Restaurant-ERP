@extends('installer.layout')
@section('title', 'Admin account')
@section('step', '4')

@section('content')
    <p class="text-muted">Create the administrator account you'll use to sign in.</p>

    <form method="POST" action="{{ route('install.admin.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Full name</label>
                <input name="name" class="form-control" value="{{ old('name', $data['name'] ?? '') }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control" value="{{ old('email', $data['email'] ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control" minlength="8" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm password</label>
                <input name="password_confirmation" type="password" class="form-control" minlength="8" required>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('install.restaurant') }}" class="btn btn-outline-secondary">Back</a>
            <button class="btn btn-success">Install <i class="bi bi-check-lg ms-1"></i></button>
        </div>
    </form>
@endsection
