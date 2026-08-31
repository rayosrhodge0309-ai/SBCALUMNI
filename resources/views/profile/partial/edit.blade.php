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
                            <div class="password-toggle-field">
                                <input id="password" type="password" name="password" class="form-control" autocomplete="new-password">
                                <button type="button" class="password-toggle-button" data-password-toggle aria-label="Show new password" aria-controls="password" aria-pressed="false">
                                    <span class="password-toggle-icon password-toggle-icon-show" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="M12 5c5.1 0 8.4 4.2 9.5 6.1a1.8 1.8 0 0 1 0 1.8C20.4 14.8 17.1 19 12 19s-8.4-4.2-9.5-6.1a1.8 1.8 0 0 1 0-1.8C3.6 9.2 6.9 5 12 5Zm0 2C7.9 7 5.2 10.3 4.3 12c0.9 1.7 3.6 5 7.7 5s6.8-3.3 7.7-5C18.8 10.3 16.1 7 12 7Zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6Z"/></svg>
                                    </span>
                                    <span class="password-toggle-icon password-toggle-icon-hide" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="m3.3 2 18.7 18.7-1.3 1.3-3.1-3.1A10.2 10.2 0 0 1 12 20c-5.1 0-8.4-4.2-9.5-6.1a1.8 1.8 0 0 1 0-1.8 16.5 16.5 0 0 1 4-4.5L2 3.3 3.3 2Zm4.6 7.1A14.4 14.4 0 0 0 4.3 13c0.9 1.7 3.6 5 7.7 5 1.5 0 2.9-.5 4-1.1l-2-2A3 3 0 0 1 9.1 10l-1.2-.9ZM12 6c5.1 0 8.4 4.2 9.5 6.1a1.8 1.8 0 0 1 0 1.8 14.8 14.8 0 0 1-2.1 2.8l-1.4-1.4a13 13 0 0 0 1.7-2.3C18.8 11.3 16.1 8 12 8c-.7 0-1.4.1-2 .3L8.5 6.8A9 9 0 0 1 12 6Zm2.8 6.4-3.2-3.2a3 3 0 0 1 3.2 3.2Z"/></svg>
                                    </span>
                                </button>
                            </div>
                            <div class="form-text">Leave blank if you do not want to change your password.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                            <div class="password-toggle-field">
                                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                                <button type="button" class="password-toggle-button" data-password-toggle aria-label="Show confirm password" aria-controls="password_confirmation" aria-pressed="false">
                                    <span class="password-toggle-icon password-toggle-icon-show" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="M12 5c5.1 0 8.4 4.2 9.5 6.1a1.8 1.8 0 0 1 0 1.8C20.4 14.8 17.1 19 12 19s-8.4-4.2-9.5-6.1a1.8 1.8 0 0 1 0-1.8C3.6 9.2 6.9 5 12 5Zm0 2C7.9 7 5.2 10.3 4.3 12c0.9 1.7 3.6 5 7.7 5s6.8-3.3 7.7-5C18.8 10.3 16.1 7 12 7Zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6Z"/></svg>
                                    </span>
                                    <span class="password-toggle-icon password-toggle-icon-hide" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="m3.3 2 18.7 18.7-1.3 1.3-3.1-3.1A10.2 10.2 0 0 1 12 20c-5.1 0-8.4-4.2-9.5-6.1a1.8 1.8 0 0 1 0-1.8 16.5 16.5 0 0 1 4-4.5L2 3.3 3.3 2Zm4.6 7.1A14.4 14.4 0 0 0 4.3 13c0.9 1.7 3.6 5 7.7 5 1.5 0 2.9-.5 4-1.1l-2-2A3 3 0 0 1 9.1 10l-1.2-.9ZM12 6c5.1 0 8.4 4.2 9.5 6.1a1.8 1.8 0 0 1 0 1.8 14.8 14.8 0 0 1-2.1 2.8l-1.4-1.4a13 13 0 0 0 1.7-2.3C18.8 11.3 16.1 8 12 8c-.7 0-1.4.1-2 .3L8.5 6.8A9 9 0 0 1 12 6Zm2.8 6.4-3.2-3.2a3 3 0 0 1 3.2 3.2Z"/></svg>
                                    </span>
                                </button>
                            </div>
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
