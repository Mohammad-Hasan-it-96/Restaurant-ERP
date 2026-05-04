@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('create_new_user'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0">{{\App\Helpers\Helpers::translate('create_new_user')}}</h2>
            </div>
            
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-4">
                            <!-- Profile Picture Section -->
                            <div class="col-12 text-center mb-3">
                                <div class="position-relative d-inline-block">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto" 
                                         style="width: 120px; height: 120px; background-color: #e9ecef; color: #6c757d; font-size: 3rem;" id="profile-initials">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <label for="profile_picture" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 cursor-pointer" style="cursor: pointer;">
                                        <i class="bi bi-camera"></i>
                                        <input type="file" id="profile_picture" name="profile_picture" class="d-none" accept="image/*">
                                    </label>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">{{\App\Helpers\Helpers::translate('click_camera_to_upload')}}</small>
                                </div>
                                @error('profile_picture')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="name" class="form-label">{{\App\Helpers\Helpers::translate('full_name')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                </div>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">{{\App\Helpers\Helpers::translate('email_address')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email') }}" required>
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="role" class="form-label">{{\App\Helpers\Helpers::translate('role')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-shield"></i></span>
                                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                        <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>{{\App\Helpers\Helpers::translate('user')}}</option>
                                        <option value="moderator" {{ old('role') === 'moderator' ? 'selected' : '' }}>{{\App\Helpers\Helpers::translate('moderator')}}</option>
                                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>{{\App\Helpers\Helpers::translate('admin')}}</option>
                                    </select>
                                </div>
                                @error('role')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">{{\App\Helpers\Helpers::translate('password')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" required>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">{{\App\Helpers\Helpers::translate('confirm_password')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-shield-lock"></i></span>
                                    <input type="password" class="form-control" 
                                           id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>

                            <div class="col-12 mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-person-plus me-2"></i>{{\App\Helpers\Helpers::translate('create_user')}}
                                </button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary px-4">
                                    <i class="bi bi-x-circle me-2"></i>{{\App\Helpers\Helpers::translate('cancel')}}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Preview uploaded profile picture
        const profilePictureInput = document.getElementById('profile_picture');
        
        if (profilePictureInput) {
            profilePictureInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const profileInitials = document.getElementById('profile-initials');
                        
                        if (profileInitials) {
                            // Replace initials with image
                            const parent = profileInitials.parentNode;
                            profileInitials.remove();
                            
                            const newImage = document.createElement('img');
                            newImage.src = e.target.result;
                            newImage.alt = "Profile Preview";
                            newImage.id = "profile-preview";
                            newImage.className = "rounded-circle";
                            newImage.width = 120;
                            newImage.height = 120;
                            newImage.style.objectFit = "cover";
                            
                            // Insert before the label (which is the second child)
                            parent.insertBefore(newImage, parent.children[0]);
                        }
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });
</script>
@endpush