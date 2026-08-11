@extends('layouts.app')

@section('title', 'Profile Settings')
@section('subtitle', 'Keep your account details, password, and profile photo up to date.')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-card p-4 p-lg-5">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @if ($user->isAlumni() && $user->alumni)
                <div class="alert alert-info">
                    Your portal account is linked to alumni record <strong>{{ $user->alumni->student_id_display }}</strong>. Status updates and pickup notes will appear in your alumni portal.
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="dashboard-banner p-4 h-100">
                        <div class="profile-avatar mb-3">
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                            @else
                                <div class="profile-avatar-placeholder">{{ $user->initials }}</div>
                            @endif
                        </div>

                        <h3 class="h5 mb-2">Profile Picture</h3>
                        <p class="text-secondary small mb-3">
                            Upload a clear image for your alumni or administrator profile. JPG, PNG, and other image files up to 2 MB are accepted.
                        </p>

                        <label class="form-label" for="profile_photo">Choose Image</label>
                        <input id="profile_photo" type="file" name="profile_photo" class="form-control" accept="image/*">

                        @if ($user->profile_photo_path)
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" value="1" id="remove_profile_photo" name="remove_profile_photo">
                                <label class="form-check-label" for="remove_profile_photo">Remove current profile picture</label>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Full Name</label>
                            <input id="name" type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email Address</label>
                            <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">New Password</label>
                            <input id="password" type="password" name="password" class="form-control">
                            <div class="form-text">Leave blank if you do not want to change your password.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="{{ $user->isAdmin() ? route('dashboard') : route('portal.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>
@endsection
