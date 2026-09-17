@extends('layouts.app')

@section('title', 'Forgot Password')
@section('centered_guest', true)

@section('content')
    <div class="portal-password-reset-shell">
        <div class="page-card portal-password-reset-card p-3 p-md-5">
            <div class="text-center mb-4">
                <div class="stat-pill text-success bg-success-subtle mb-3">Password Help</div>
                <h1 class="h3 mb-2">Reset your alumni portal password</h1>
                <p class="text-secondary mb-0">Enter the approved Gmail account connected to your alumni portal. We will send a 6-digit reset code there.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('portal.password.email') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Gmail Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="yourname@gmail.com" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Only approved alumni Gmail accounts can receive a reset code.</div>
                </div>

                <button type="submit" class="btn btn-success w-100">Send Password Reset Code</button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('portal.login') }}" class="text-decoration-none">Back to login</a>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .guest-centered-main {
            width: 100%;
            max-width: none;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .portal-password-reset-shell {
            width: 100%;
            min-height: calc(100vh - 12rem);
            display: grid;
            place-items: center;
            padding: 1rem;
            box-sizing: border-box;
        }

        .portal-password-reset-card {
            width: min(100%, 32rem);
            margin: 0;
        }
    </style>
@endpush
