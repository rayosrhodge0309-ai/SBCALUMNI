@extends('layouts.app')

@section('title', 'Alumni Portal Login')

@section('content')
    <div class="page-card p-3 p-md-5">
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

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-success w-100">Login to Alumni Portal</button>
        </form>

        <div class="text-center mt-4 text-secondary">
            No alumni portal account yet?
            <a href="{{ route('portal.register') }}" class="text-decoration-none">Submit an account request</a>
        </div>
    </div>
@endsection
