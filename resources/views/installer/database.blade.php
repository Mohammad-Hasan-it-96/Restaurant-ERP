@extends('installer.layout')
@section('title', 'Database')
@section('step', '1')

@section('content')
    <p class="text-muted">Enter your MySQL connection details. They'll be tested before continuing.</p>

    <form method="POST" action="{{ route('install.database.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-8">
                <label class="form-label">Host</label>
                <input name="db_host" class="form-control" value="{{ old('db_host', $data['db_host'] ?? '127.0.0.1') }}" required>
            </div>
            <div class="col-4">
                <label class="form-label">Port</label>
                <input name="db_port" class="form-control" value="{{ old('db_port', $data['db_port'] ?? '3306') }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Database name</label>
                <input name="db_database" class="form-control" value="{{ old('db_database', $data['db_database'] ?? '') }}" required>
            </div>
            <div class="col-6">
                <label class="form-label">Username</label>
                <input name="db_username" class="form-control" value="{{ old('db_username', $data['db_username'] ?? 'root') }}" required>
            </div>
            <div class="col-6">
                <label class="form-label">Password</label>
                <input name="db_password" type="password" class="form-control" value="{{ old('db_password', $data['db_password'] ?? '') }}">
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('install.index') }}" class="btn btn-outline-secondary">Back</a>
            <button class="btn btn-primary">Test &amp; continue <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </form>
@endsection
