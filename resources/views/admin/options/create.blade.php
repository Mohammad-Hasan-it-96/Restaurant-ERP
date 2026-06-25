@extends('layouts.app')

@section('title', 'إضافة خيار جديد')

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="{{ route('admin.options.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h3 class="mb-0" style="color: var(--text);">
            <i class="bi bi-plus-circle me-2" style="color: var(--primary);"></i>
            إضافة خيار جديد
        </h3>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
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

                    <form action="{{ route('admin.options.store') }}" method="POST">
                        @csrf

                        @include('admin.options._form', ['option' => null])

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3" style="border-top: 1px solid var(--border-color);">
                            <a href="{{ route('admin.options.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>حفظ
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
