@extends('installer.layout')
@section('title', 'Welcome')
@section('step', '0')

@section('content')
    <p class="text-muted">This wizard will configure your <code>.env</code>, set up the database, and
        create your admin account — no manual file editing required.</p>

    <h6 class="fw-bold mt-4 mb-2">Server requirements</h6>
    @php $allPass = ! in_array(false, $checks, true); @endphp
    <ul class="list-group mb-4">
        @foreach($checks as $label => $ok)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                {{ $label }}
                @if($ok)
                    <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                @else
                    <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                @endif
            </li>
        @endforeach
    </ul>

    @if($allPass)
        <a href="{{ route('install.database') }}" class="btn btn-primary w-100">
            Start <i class="bi bi-arrow-right ms-1"></i>
        </a>
    @else
        <div class="alert alert-warning mb-0">Resolve the failing requirements above, then reload this page.</div>
    @endif
@endsection
