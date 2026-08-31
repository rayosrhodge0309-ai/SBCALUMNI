@extends('layouts.app')

@section('title', 'Administrator Login')

@section('content')
    <div class="page-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="stat-pill text-primary bg-primary-subtle mb-3">Admin Access</div>
            <h1 class="h3 mb-2">Sign in to the school admin panel</h1>
            <p class="text-secondary mb-0">Process online requests, import alumni records, and prepare documents for school pickup.</p>
        </div>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <div class="text-center mt-4 text-secondary">
            Alumni user?
            <a href="{{ route('portal.login') }}" class="text-decoration-none">Use the alumni portal instead</a>
        </div>
    </div>
@endsection
