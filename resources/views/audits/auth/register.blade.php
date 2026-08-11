@extends('layouts.app')

@section('title', 'Create Administrator')

@section('content')
    <div class="page-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="stat-pill text-primary bg-primary-subtle mb-3">Initial Admin Setup</div>
            <h1 class="h3 mb-2">Create the first administrator account</h1>
            <p class="text-secondary mb-0">This page is only available before the first admin account exists.</p>
        </div>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" class="form-control" name="password" required autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>

        <div class="text-center mt-4 text-secondary">
            Already have admin access?
            <a href="{{ route('login') }}" class="text-decoration-none">Login here</a>
        </div>
    </div>
@endsection
