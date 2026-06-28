@extends('layouts.app')

@section('title', __('app.release_notes'))

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-rocket-takeoff me-2 text-primary"></i>
                {{ __('app.release_notes') }}
            </h2>
            <span class="badge bg-primary fs-6">
                {{ __('app.current_version') }}: v{{ $current }}
            </span>
        </div>

        @if(empty($releases))
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    {{ __('app.no_data') }}
                </div>
            </div>
        @else
            @foreach($releases as $release)
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <h5 class="fw-bold mb-0">
                                v{{ $release['version'] ?? '' }}
                            </h5>
                            @if(($release['version'] ?? null) === $current)
                                <span class="badge bg-success">{{ __('app.current_version') }}</span>
                            @endif
                            @if(! empty($release['date']))
                                <span class="text-muted small ms-auto">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $release['date'] }}
                                </span>
                            @endif
                        </div>
                        <ul class="mb-0 ps-3">
                            @foreach(($release['notes'] ?? []) as $note)
                                <li class="mb-1">{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
