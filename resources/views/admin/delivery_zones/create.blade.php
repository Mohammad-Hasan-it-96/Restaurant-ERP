@extends('layouts.app')

@section('title', __('app.add_delivery_zone'))

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('admin.delivery-zones.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}-short fs-5"></i>
        </a>
        <h2 class="fw-bold mb-0">{{ __('app.add_delivery_zone') }}</h2>
    </div>

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form action="{{ route('admin.delivery-zones.store') }}" method="POST">
                @csrf
                @include('admin.delivery_zones._form')
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>{{ __('app.save') }}
                    </button>
                    <a href="{{ route('admin.delivery-zones.index') }}" class="btn btn-outline-secondary">
                        {{ __('app.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

