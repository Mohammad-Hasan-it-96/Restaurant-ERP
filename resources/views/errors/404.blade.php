@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('page_not_found'))

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-question-circle-fill me-2"></i>{{\App\Helpers\Helpers::translate('page_not_found')}}</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-search text-primary" style="font-size: 5rem;"></i>
                    </div>
                    <h5 class="text-center mb-3">{{\App\Helpers\Helpers::translate('page_does_not_exist')}}</h5>
                    <p class="text-center text-muted">{{\App\Helpers\Helpers::translate('page_might_be_removed')}}</p>
                    <div class="d-flex justify-content-center mt-4">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary me-2">
                            <i class="bi bi-house-door me-1"></i> {{\App\Helpers\Helpers::translate('go_to_dashboard')}}
                        </a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> {{\App\Helpers\Helpers::translate('go_back')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection