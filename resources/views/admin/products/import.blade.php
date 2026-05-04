@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('import_products'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h3>{{ \App\Helpers\Helpers::translate('import_products') }}</h3>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>{{ \App\Helpers\Helpers::translate('back_to_products') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-equal-height">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-download me-2"></i>{{ \App\Helpers\Helpers::translate('download_template') }}</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="flex-grow-1">
                        <p>{{ \App\Helpers\Helpers::translate('template_description') }}</p>
                        <p class="text-muted small">{{ \App\Helpers\Helpers::translate('template_columns') }}</p>
                    </div>
                    <div class="mt-auto pt-3">
                        <a href="{{ route('admin.products.template') }}" class="btn btn-primary">
                            <i class="bi bi-file-earmark-excel me-2"></i>{{ \App\Helpers\Helpers::translate('download_template') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-upload me-2"></i>{{ \App\Helpers\Helpers::translate('upload_products') }}</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="flex-grow-1">
                        <p>{{ \App\Helpers\Helpers::translate('upload_description') }}</p>
                        <p class="text-muted small">{{ \App\Helpers\Helpers::translate('products_association') }}</p>
                    </div>
                    
                    <form action="{{ route('admin.products.import.process') }}" method="POST" enctype="multipart/form-data" class="mt-auto">
                        @csrf
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">{{ \App\Helpers\Helpers::translate('excel_file') }}</label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx, .xls" required>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload me-2"></i>{{ \App\Helpers\Helpers::translate('upload_and_import') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .row-equal-height {
        display: flex;
        flex-wrap: wrap;
    }
    
    .row-equal-height > [class*='col-'] {
        display: flex;
        flex-direction: column;
    }
</style>
@endsection