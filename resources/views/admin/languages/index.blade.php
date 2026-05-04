@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('languages_management'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">{{\App\Helpers\Helpers::translate('languages_management')}}</h2>
            <p class="text-muted">{{\App\Helpers\Helpers::translate('manage_languages')}}</p>
        </div>
        <a href="{{ route('admin.languages.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>{{\App\Helpers\Helpers::translate('add_language')}}
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>{{\App\Helpers\Helpers::translate('success')}}!</strong> {{ session('success') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>{{\App\Helpers\Helpers::translate('error')}}!</strong> {{ session('error') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card border-0 shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">{{\App\Helpers\Helpers::translate('all_languages')}}</h5>
            <div class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text border-end-0" style="background-color: var(--input-bg);">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="searchLanguages" placeholder="{{\App\Helpers\Helpers::translate('search_languages')}}">
                </div>
                <button class="btn" style="background-color: var(--input-bg);" title="{{\App\Helpers\Helpers::translate('refresh')}}">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">ID</th>
                            <th>{{\App\Helpers\Helpers::translate('flag_image')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('language_name')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('language_code')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('direction')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('status')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('default')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('created_on')}}</th>
                            <th class="text-end pe-4">{{\App\Helpers\Helpers::translate('actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($languages as $language)
                        <tr>
                            <td class="ps-4">{{ $language->id }}</td>
                            <td>
                                @if($language->flag_path)
                                    <img src="{{ asset('storage/' . $language->flag_path) }}" alt="{{ $language->name }}" class="rounded" width="30" height="20" style="object-fit: cover;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 30px; height: 20px;">
                                        <i class="bi bi-flag text-muted" style="font-size: 0.8rem;"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $language->name }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $language->code }}</span>
                            </td>
                            <td>
                                @if($language->direction == 'rtl')
                                    <span class="badge bg-info">{{\App\Helpers\Helpers::translate('rtl')}}</span>
                                @else
                                    <span class="badge bg-light text-dark">{{\App\Helpers\Helpers::translate('ltr')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($language->status)
                                    <span class="badge bg-success">{{\App\Helpers\Helpers::translate('active')}}</span>
                                @else
                                    <span class="badge bg-danger">{{\App\Helpers\Helpers::translate('inactive')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($language->is_default)
                                    <span class="badge bg-primary">{{\App\Helpers\Helpers::translate('default')}}</span>
                                @else
                                    <span class="badge bg-light text-dark">{{\App\Helpers\Helpers::translate('no')}}</span>
                                @endif
                            </td>
                            <td>{{ $language->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2 pe-4">
                                    <a href="{{ route('admin.languages.edit', $language->id) }}" class="btn btn-sm btn-outline-primary" title="{{\App\Helpers\Helpers::translate('edit')}}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if(!$language->is_default)
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-language" 
                                            title="{{\App\Helpers\Helpers::translate('delete')}}" 
                                            data-id="{{ $language->id }}" 
                                            data-name="{{ $language->name }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="{{\App\Helpers\Helpers::translate('cannot_delete_default')}}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center py-5">
                                    <i class="bi bi-translate text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3">{{\App\Helpers\Helpers::translate('no_languages_found')}}</h5>
                                    <p class="text-muted">{{\App\Helpers\Helpers::translate('try_adding_language')}}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer border-top d-flex justify-content-between align-items-center py-3">
            <div>
                <span class="text-muted">{{\App\Helpers\Helpers::translate('showing')}} {{ $languages->firstItem() ?? 0 }} {{\App\Helpers\Helpers::translate('to')}} {{ $languages->lastItem() ?? 0 }} {{\App\Helpers\Helpers::translate('of')}} {{ $languages->total() }} {{\App\Helpers\Helpers::translate('entries')}}</span>
            </div>
            <div>
                {{ $languages->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteLanguageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">{{\App\Helpers\Helpers::translate('confirm_delete')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
                </div>
                <h4 class="mb-3">{{\App\Helpers\Helpers::translate('are_you_sure')}}</h4>
                <p class="text-muted mb-0">{!! \App\Helpers\Helpers::translate('delete_confirmation', ['name' => '<span id="languageName" class="fw-bold"></span>']) !!}</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">{{\App\Helpers\Helpers::translate('cancel')}}</button>
                <form id="deleteLanguageForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger px-4">{{\App\Helpers\Helpers::translate('delete')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('searchLanguages');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('tbody tr');

                tableRows.forEach(row => {
                    const languageName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const languageCode = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

                    if (languageName.includes(searchTerm) || languageCode.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Delete language confirmation
        const deleteButtons = document.querySelectorAll('.delete-language');
        const deleteLanguageModal = new bootstrap.Modal(document.getElementById('deleteLanguageModal'));
        const deleteLanguageForm = document.getElementById('deleteLanguageForm');
        const languageNameSpan = document.getElementById('languageName');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const languageId = this.getAttribute('data-id');
                const languageName = this.getAttribute('data-name');

                languageNameSpan.textContent = languageName;
                deleteLanguageForm.action = "{{ url('admin/languages') }}/" + languageId;

                deleteLanguageModal.show();
            });
        });
    });
</script>
@endpush
