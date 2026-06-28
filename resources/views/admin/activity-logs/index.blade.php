@extends('layouts.app')

@section('title', __('app.activity_log'))

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i>
                {{ __('app.activity_log') }}
            </h2>
        </div>

        {{-- Filters --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label small text-muted mb-1">{{ __('app.search') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-control" placeholder="{{ __('app.activity_search_placeholder') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1">{{ __('app.activity_action') }}</label>
                        <select name="action" class="form-select">
                            <option value="">{{ __('app.all') }}</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>
                                    {{ trans()->has('app.activity_actions.'.$action) ? __('app.activity_actions.'.$action) : $action }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-muted mb-1">{{ __('app.from') }}</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-muted mb-1">{{ __('app.to') }}</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                    </div>
                    <div class="col-6 col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    @if(request()->hasAny(['search', 'action', 'from', 'to']))
                        <div class="col-12">
                            <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>{{ __('app.clear') }}
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card shadow-sm">
            <div class="card-body p-0">
                @php
                    $sort = request('sort', 'created_at');
                    $dir  = request('direction', 'desc');
                    $sortUrl  = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'direction' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
                    $sortIcon = fn($col) => $sort === $col
                        ? ($dir === 'asc' ? '<i class="bi bi-sort-up-alt text-primary ms-1"></i>' : '<i class="bi bi-sort-down-alt text-primary ms-1"></i>')
                        : '<i class="bi bi-arrow-down-up text-muted ms-1" style="opacity:.5"></i>';
                @endphp
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ $sortUrl('created_at') }}"
                                   class="text-dark text-decoration-none">{{ __('app.activity_time') }} {!! $sortIcon('created_at') !!}</a>
                            </th>
                            <th>{{ __('app.activity_actor') }}</th>
                            <th>
                                <a href="{{ $sortUrl('action') }}"
                                   class="text-dark text-decoration-none">{{ __('app.activity_action') }} {!! $sortIcon('action') !!}</a>
                            </th>
                            <th>{{ __('app.activity_subject') }}</th>
                            <th>{{ __('app.activity_details') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="small text-muted text-nowrap" title="{{ $log->created_at }}">
                                    {{ $log->created_at?->diffForHumans() }}
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $log->causer_label ?? __('app.system') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary-emphasis">
                                        {{ trans()->has('app.activity_actions.'.$log->action) ? __('app.activity_actions.'.$log->action) : $log->action }}
                                    </span>
                                </td>
                                <td class="small">{{ $log->subject_label ?? '—' }}</td>
                                <td class="small text-muted">
                                    {{ $log->description }}
                                    @if($log->properties)
                                        <code class="d-block mt-1 text-secondary" style="font-size:.72rem">{{ json_encode($log->properties, JSON_UNESCAPED_UNICODE) }}</code>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
                                    {{ __('app.activity_none_found') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($logs->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        {{ __('app.showing') }} {{ $logs->firstItem() }}–{{ $logs->lastItem() }}
                        {{ __('app.of') }} {{ $logs->total() }}
                    </small>
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
