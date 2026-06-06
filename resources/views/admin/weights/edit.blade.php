@extends('layouts.app')

@section('title', __('app.edit_weight'))

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="{{ route('admin.weights.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h3 class="mb-0" style="color: var(--text);">
            <i class="bi bi-pencil me-2" style="color: var(--primary);"></i>
            {{ __('app.edit_weight') }}: <span class="fw-normal">{{ $weight->name }}</span>
        </h3>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm" style="border: 1px solid var(--border-color); background-color: var(--card-bg);">
                <div class="card-body p-4">

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.weights.update', $weight) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_back" value="{{ $back ?? route('admin.weights.index') }}">

                        @include('admin.weights._form', ['weight' => $weight])

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3" style="border-top: 1px solid var(--border-color);">
                            <a href="{{ $back ?? route('admin.weights.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>{{ __('app.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>{{ __('app.save') }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
