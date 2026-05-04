@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('edit_category') . ' - ' . $category->name_ar)

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h3 class="mb-0" style="color: var(--text);">
                <i class="bi bi-pencil-square me-2" style="color: var(--primary);"></i>
                {{ \App\Helpers\Helpers::translate('edit_category') }}:
                <span class="fw-normal">{{ $category->name_ar }}</span>
            </h3>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
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

                    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @include('admin.categories._form')

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3" style="border-top: 1px solid var(--border-color);">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>{{ \App\Helpers\Helpers::translate('cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>{{ \App\Helpers\Helpers::translate('update_category') }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection

