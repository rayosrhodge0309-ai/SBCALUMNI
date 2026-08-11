@extends('layouts.app')

@section('title', 'Edit User Account')
@section('subtitle', 'Update the selected login account and keep linked alumni information aligned.')

@section('content')
    <div class="page-card p-4 p-lg-5">
        <form method="POST" action="{{ route('users.update', $managedUser) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="dashboard-banner p-4 h-100">
                        <div class="profile-avatar mb-3">
                            @if ($managedUser->profile_photo_url)
                                <img src="{{ $managedUser->profile_photo_url }}" alt="{{ $managedUser->name }}">
                            @else
                                <div class="profile-avatar-placeholder">{{ $managedUser->initials }}</div>
                            @endif
                        </div>
                        <div class="small text-secondary text-uppercase fw-semibold mb-2">{{ $managedUser->role }}</div>
                        <div class="small text-secondary mb-2">Account Status: <span class="text-capitalize">{{ str_replace('_', ' ', $managedUser->account_status ?? 'approved') }}</span></div>
                        <h3 class="h5 mb-2">{{ $managedUser->name }}</h3>
                        <p class="text-secondary small mb-3">{{ $managedUser->email }}</p>

                        @if ($managedUser->alumni)
                            <div class="small text-secondary mb-2">Linked Alumni Record</div>
                            <div class="fw-semibold">{{ $managedUser->alumni->student_id_display }}</div>
                            <div class="text-secondary small mb-3">{{ $managedUser->alumni->full_name }}</div>
                            <a href="{{ route('alumni.edit', $managedUser->alumni) }}" class="btn btn-outline-dark btn-sm">Open Alumni Record</a>
                        @else
                            <div class="text-secondary small">This account is not linked to an alumni record.</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Full Name</label>
                            <input id="name" type="text" name="name" class="form-control" value="{{ old('name', $managedUser->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email Address</label>
                            <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $managedUser->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">New Password</label>
                            <input id="password" type="password" name="password" class="form-control">
                            <div class="form-text">Leave blank if you do not want to change this user password.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Update Account</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Back to Users</a>
            </div>
        </form>

        @if ($managedUser->isAlumni() && $managedUser->isPendingApproval())
            <div class="d-flex gap-2 mt-3">
                <form method="POST" action="{{ route('users.approve', $managedUser) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Approve Account</button>
                </form>
                <form method="POST" action="{{ route('users.reject', $managedUser) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Reject this account request?')">Reject Account</button>
                </form>
            </div>
        @endif
    </div>
@endsection
