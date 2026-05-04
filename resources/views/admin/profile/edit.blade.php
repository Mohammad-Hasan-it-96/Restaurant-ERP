@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('edit_profile'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{\App\Helpers\Helpers::translate('edit_profile')}}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <div class="row g-4">
                            <!-- Profile Picture Section -->
                            <div class="col-12 text-center mb-3">
                                <div class="position-relative d-inline-block">
                                    @if(Auth::user()->profile_picture)
                                    <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}"
                                        alt="{{ Auth::user()->name }}" class="rounded-circle" width="120" height="120"
                                        id="profile-preview" style="object-fit: cover;">
                                    @else
                                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto"
                                        style="width: 120px; height: 120px; background-color: {{ '#' . substr(md5(Auth::user()->email), 0, 6) }}; color: white; font-size: 3rem;"
                                        id="profile-initials">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                    @endif
                                    <label for="profile_picture"
                                        class="position-absolute bottom-0 {{ App::isLocale('ar') || (session('site_direction') == 'rtl') ? 'start-0' : 'end-0' }} bg-primary text-white rounded-circle p-2"
                                        style="cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-camera"></i>
                                        <input type="file" id="profile_picture" name="profile_picture" class="d-none"
                                            accept="image/*">
                                    </label>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">{{\App\Helpers\Helpers::translate('click_camera_to_change_profile')}}</small>
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
                                        id="name" name="name" value="{{ old('name', Auth::user()->name) }}" required>
                                </div>
                                @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">{{\App\Helpers\Helpers::translate('email_address')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email"
                                        value="{{ Auth::user()->email }}" disabled>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">{{\App\Helpers\Helpers::translate('new_password')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password" placeholder="{{\App\Helpers\Helpers::translate('leave_blank_to_keep_current')}}">
                                </div>
                                @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">{{\App\Helpers\Helpers::translate('confirm_password')}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i
                                            class="bi bi-check2-circle"></i></span>
                                    <input type="password" class="form-control" id="password_confirmation"
                                        name="password_confirmation">
                                </div>
                            </div>

                            <div class="col-12 mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-2"></i>{{\App\Helpers\Helpers::translate('update_profile')}}
                                </button>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary px-4">
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
        const profilePictureInput = document.getElementById('profile_picture');
        
        if (profilePictureInput) {
            profilePictureInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const profilePreview = document.getElementById('profile-preview');
                        const profileInitials = document.getElementById('profile-initials');
                        
                        if (profilePreview) {
                            // Update existing image
                            profilePreview.src = e.target.result;
                        } else if (profileInitials) {
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