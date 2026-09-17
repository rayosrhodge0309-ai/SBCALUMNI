@extends('layouts.app')

@section('title', 'Alumni Portal Login')
@section('centered_guest', true)
@section('school_guest_header', true)

@section('content')
    <div class="portal-login-shell">
        <div class="page-card portal-login-card p-3 p-md-5">
            <div class="text-center mb-4">
                <div class="portal-login-brand-mark mx-auto mb-3">
                    <img src="{{ asset('images/sbc-logo.svg') }}" alt="St. Bridget College Alumni Association seal">
                </div>
                <div class="stat-pill text-success bg-success-subtle mb-3">Alumni Self-Service</div>
                <h1 class="h3 mb-2">Sign in to request school records</h1>
                <p class="text-secondary mb-0">Use your approved alumni portal account to submit digital requests and monitor when records are ready for pickup.</p>
            </div>

            @if (session('success') || session('status'))
                <div class="alert alert-success">
                    {{ session('success') ?? session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.login.attempt') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="yourname@gmail.com" autocomplete="username" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Alumni portal login accepts Gmail accounts only.</div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <label for="password" class="form-label">Password</label>
                        <a href="{{ route('portal.password.request') }}" class="small text-decoration-none">Forgot password?</a>
                    </div>
                    <div class="portal-password-field">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="current-password" required>
                        <button type="button" class="portal-password-toggle" data-password-toggle="password" data-password-label="password" aria-label="Show password" title="Show password">
                            <svg class="portal-password-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                <circle cx="12" cy="12" r="2.75" />
                                <path class="portal-password-eye-slash" d="M4 4l16 16" />
                            </svg>
                        </button>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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
            padding: 0 !important;
            background: transparent;
        }

        .guest-shell {
            min-height: 100vh;
            min-height: 100svh;
            background:
                linear-gradient(115deg, rgba(247, 252, 255, 0.96), rgba(221, 240, 255, 0.9) 52%, rgba(201, 224, 255, 0.78)),
                url("{{ asset('images/alumni-header.jpg') }}") center / cover no-repeat;
        }

        .guest-shell .app-main.guest-centered-main {
            align-items: stretch !important;
            justify-content: flex-start !important;
            padding: 0 !important;
        }

        .portal-login-shell {
            width: 100%;
            min-height: calc(100vh - 3rem);
            min-height: calc(100svh - 3rem);
            display: grid;
            place-items: center;
            position: relative;
            isolation: isolate;
            overflow: hidden;
            padding: clamp(2rem, 5vw, 4.5rem) 1rem;
            box-sizing: border-box;
            background: transparent;
        }

        .portal-login-shell::before,
        .portal-login-shell::after {
            content: "";
            position: absolute;
            z-index: -1;
            pointer-events: none;
        }

        .portal-login-shell::before {
            display: none;
        }

        .portal-login-shell::after {
            width: min(36rem, 70vw);
            aspect-ratio: 1;
            right: -12rem;
            bottom: -18rem;
            border-radius: 50%;
            background: rgba(214, 167, 0, 0.16);
            filter: blur(0.25rem);
        }

        .portal-login-card {
            width: min(100%, 32rem);
            margin: 0;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(11, 69, 184, 0.18);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 1.2rem 3.2rem rgba(38, 87, 130, 0.18);
            backdrop-filter: blur(0.4rem);
        }

        .portal-login-card::before {
            display: none;
        }

        .portal-login-brand-mark {
            width: 4.5rem;
            height: 4.5rem;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(11, 69, 184, 0.16);
            box-shadow: 0 0.8rem 1.9rem rgba(7, 17, 111, 0.16);
        }

        .portal-login-brand-mark img {
            width: 3.55rem;
            height: 3.55rem;
            object-fit: contain;
        }

        .portal-login-card .form-control {
            border-color: rgba(11, 69, 184, 0.24);
            background-color: rgba(255, 255, 255, 0.9);
        }

        .portal-login-card .form-control:focus {
            border-color: #d6a700;
            box-shadow: 0 0 0 0.18rem rgba(214, 167, 0, 0.2);
        }

        .portal-login-card .btn-success {
            border: 0;
            background: linear-gradient(135deg, #07116f, #0b45b8);
            box-shadow: 0 0.7rem 1.3rem rgba(7, 17, 111, 0.18);
        }

        .portal-login-card .btn-success:hover,
        .portal-login-card .btn-success:focus-visible {
            background: linear-gradient(135deg, #07116f, #073b9d);
            box-shadow: 0 0.9rem 1.6rem rgba(7, 17, 111, 0.24);
        }

        .portal-login-card .text-decoration-none {
            font-weight: 600;
        }

        .portal-password-field {
            position: relative;
        }

        .portal-password-field .form-control {
            padding-right: 3rem;
        }

        .portal-password-field input::-ms-reveal,
        .portal-password-field input::-ms-clear {
            display: none;
        }

        .portal-password-toggle {
            position: absolute;
            top: 50%;
            right: 0.45rem;
            z-index: 5;
            width: 2.2rem;
            height: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: #fff;
            color: var(--action);
            cursor: pointer;
            transform: translateY(-50%);
        }

        .portal-password-field:focus-within .portal-password-toggle {
            background: #fffef0;
        }

        .portal-password-toggle:hover,
        .portal-password-toggle:focus {
            background: rgba(11, 69, 184, 0.1);
            color: var(--action-dark);
        }

        .portal-password-toggle:focus {
            outline: 0;
            box-shadow: 0 0 0 0.16rem rgba(11, 69, 184, 0.22);
        }

        .portal-password-eye {
            width: 1.15rem;
            height: 1.15rem;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .portal-password-eye-slash {
            display: none;
        }

        .portal-password-toggle.is-visible .portal-password-eye-slash {
            display: block;
        }

        @media (max-width: 575.98px) {
            .portal-login-shell {
                padding: 1rem;
            }

            .portal-login-shell::before {
                border-radius: 1rem;
            }

            .portal-login-brand-mark {
                width: 4rem;
                height: 4rem;
            }

            .portal-login-brand-mark img {
                width: 3.15rem;
                height: 3.15rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
                const input = document.getElementById(toggle.dataset.passwordToggle);

                if (!input) {
                    return;
                }

                toggle.addEventListener('click', () => {
                    const isVisible = input.type === 'text';
                    const label = toggle.dataset.passwordLabel || 'password';
                    input.type = isVisible ? 'password' : 'text';
                    toggle.classList.toggle('is-visible', !isVisible);
                    toggle.setAttribute('aria-label', `${isVisible ? 'Show' : 'Hide'} ${label}`);
                    toggle.setAttribute('title', `${isVisible ? 'Show' : 'Hide'} ${label}`);
                });
            });
        })();
    </script>
@endpush
