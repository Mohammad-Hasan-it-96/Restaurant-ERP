@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('users_management'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">{{\App\Helpers\Helpers::translate('users_management')}}</h2>
            <p class="text-muted">{{\App\Helpers\Helpers::translate('manage_your_system_users')}}</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>{{\App\Helpers\Helpers::translate('add_new_user')}}
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
            <h5 class="mb-0">{{\App\Helpers\Helpers::translate('all_users')}}</h5>
            <div class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text border-end-0" style="background-color: var(--input-bg);">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="searchUsers" placeholder="{{\App\Helpers\Helpers::translate('search_users')}}">
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
                            <th class="ps-4" style="width: 50px;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>{{\App\Helpers\Helpers::translate('user')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('email')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('role')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('status')}}</th>
                            <th>{{\App\Helpers\Helpers::translate('registered_on')}}</th>
                            <th class="text-end pe-4">{{\App\Helpers\Helpers::translate('actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td class="ps-4">
                                <div class="form-check">
                                    <input class="form-check-input user-checkbox" type="checkbox" value="{{ $user->id }}">
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($user->profile_picture)
                                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="{{ $user->name }}" class="rounded-circle me-3" width="40" height="40">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center rounded-circle me-3" 
                                             style="width: 40px; height: 40px; background-color: {{ '#' . substr(md5($user->email), 0, 6) }}; color: white;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="mb-0">{{ $user->name }}</h6>
                                        @if($user->id === auth()->id())
                                        <span class="badge bg-primary">{{\App\Helpers\Helpers::translate('you')}}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->role === 'admin')
                                    <span class="badge bg-danger">{{\App\Helpers\Helpers::translate('admin')}}</span>
                                @elseif($user->role === 'moderator')
                                    <span class="badge bg-warning text-dark">{{\App\Helpers\Helpers::translate('moderator')}}</span>
                                @else
                                    <span class="badge bg-info">{{\App\Helpers\Helpers::translate('user')}}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-success">{{\App\Helpers\Helpers::translate('active')}}</span>
                            </td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2 pe-4">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary" title="{{\App\Helpers\Helpers::translate('edit')}}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-user" title="{{\App\Helpers\Helpers::translate('delete')}}" data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="{{\App\Helpers\Helpers::translate('cannot_delete_own_account')}}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center py-5">
                                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3">{{\App\Helpers\Helpers::translate('no_users_found')}}</h5>
                                    <p class="text-muted">{{\App\Helpers\Helpers::translate('try_adding_new_user')}}</p>
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
                <span class="text-muted">{{\App\Helpers\Helpers::translate('showing')}} {{ $users->firstItem() ?? 0 }} {{\App\Helpers\Helpers::translate('to')}} {{ $users->lastItem() ?? 0 }} {{\App\Helpers\Helpers::translate('of')}} {{ $users->total() }} {{\App\Helpers\Helpers::translate('users')}}</span>
            </div>
            <div>
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
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
                <p class="text-muted mb-0">{{\App\Helpers\Helpers::translate('do_you_really_want_to_delete')}} <span id="userName" class="fw-bold"></span>? {{\App\Helpers\Helpers::translate('this_process_cannot_be_undone')}}</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">{{\App\Helpers\Helpers::translate('cancel')}}</button>
                <form id="deleteUserForm" method="POST">
                    @csrf
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
        // Select all checkbox functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                userCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }
        
        // Search functionality
        const searchInput = document.getElementById('searchUsers');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('tbody tr');
                
                tableRows.forEach(row => {
                    const userName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const userEmail = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const userRole = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                    
                    if (userName.includes(searchTerm) || userEmail.includes(searchTerm) || userRole.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Delete user confirmation
        const deleteButtons = document.querySelectorAll('.delete-user');
        const deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        const deleteUserForm = document.getElementById('deleteUserForm');
        const userNameSpan = document.getElementById('userName');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                
                userNameSpan.textContent = userName;
                deleteUserForm.action = "{{ url('admin/users/delete') }}/" + userId;
                
                deleteUserModal.show();
            });
        });
    });
</script>
@endpush