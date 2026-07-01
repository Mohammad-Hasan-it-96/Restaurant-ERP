@extends('installer.layout')
@section('title', 'Application URL')
@section('step', '2')

@section('content')
    <p class="text-muted">The public URL of this site (used for links and assets).</p>

    <form method="POST" action="{{ route('install.app.store') }}">
        @csrf
        <label class="form-label">App URL</label>
        <input name="app_url" type="url" class="form-control" placeholder="https://example.com"
               value="{{ old('app_url', $data['app_url'] ?? '') }}" required>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('install.database') }}" class="btn btn-outline-secondary">Back</a>
            <button class="btn btn-primary">Continue <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </form>
@endsection
