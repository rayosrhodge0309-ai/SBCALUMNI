@extends('layouts.app')

@section('title', 'Alumni Portal Login')
@section('centered_guest', true)

@section('content')
    <div class="portal-login-shell">
        <div class="page-card portal-login-card p-3 p-md-5">
            <div class="text-center mb-4">
                <div class="stat-pill text-success bg-success-subtle mb-3">Alumni Self-Service</div>
                <h1 class="h3 mb-2">Sign in to request school records</h1>
                <p class="text-secondary mb-0">Use your approved alumni portal account to submit digital requests and monitor when records are ready for pickup.</p>
            </div>

            <form method="POST" action="{{ route('portal.login.attempt') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control" name="password" required>
                </div>

                <button type="submit" class="btn btn-success w-100">Login to Alumni Portal</button>
            </form>

            <div class="text-center mt-4 text-secondary">
                No alumni portal account yet?
                <a href="{{ route('portal.register') }}" class="text-decoration-none">Submit an account request</a>
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

        .portal-login-shell {
            width: 100%;
            min-height: calc(100vh - 12rem);
            display: grid;
            place-items: center;
            padding: 1rem;
            box-sizing: border-box;
        }

        .portal-login-card {
            width: min(100%, 32rem);
            margin: 0;
        }
    </style>
@endpush
