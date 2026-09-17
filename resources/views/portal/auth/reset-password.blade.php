@extends('layouts.app')

@section('title', 'Reset Password')
@section('centered_guest', true)

@section('content')
    @php
        $usesResetCode = (bool) ($usesResetCode ?? false);
    @endphp

    <div class="portal-password-reset-shell">
        <div class="page-card portal-password-reset-card p-3 p-md-5">
            <div class="text-center mb-4">
                <div class="stat-pill text-success bg-success-subtle mb-3">Password Reset</div>
                <h1 class="h3 mb-2">Create your new password</h1>
                <p class="text-secondary mb-0">Enter the reset code from your Gmail, then create a new password.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('portal.password.update') }}" id="portal-reset-password-form">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                @if ($usesResetCode || $token === '')
                    <div class="mb-3">
                        <label for="reset_code" class="form-label">Reset Code</label>
                        <input
                            id="reset_code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            class="form-control text-center fw-semibold @error('reset_code') is-invalid @enderror"
                            name="reset_code"
                            value="{{ old('reset_code') }}"
                            placeholder="6-digit code"
                            required
                            autofocus
                        >
                        @error('reset_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Use the 6-digit code sent to your Gmail. You do not need to open the email button link.</div>
                    </div>
                @endif

                <div class="mb-3">
                    <label for="email" class="form-label">Gmail Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $email) }}" placeholder="yourname@gmail.com" required @if (! ($usesResetCode || $token === '')) autofocus @endif>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="portal-password-field">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="portal-password-toggle" data-password-toggle="password" data-password-label="new password" aria-label="Show new password" title="Show new password">
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

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <div class="portal-password-field">
                        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="portal-password-toggle" data-password-toggle="password_confirmation" data-password-label="confirm password" aria-label="Show confirm password" title="Show confirm password">
                            <svg class="portal-password-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                <circle cx="12" cy="12" r="2.75" />
                                <path class="portal-password-eye-slash" d="M4 4l16 16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="password-match-message" class="small text-secondary mb-3"></div>

                <button type="submit" id="portal-reset-password-submit" class="btn btn-success w-100">Reset Password</button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('portal.login') }}" class="text-decoration-none">Back to login</a>
                <span class="text-secondary mx-2">|</span>
                <a href="{{ route('portal.password.request') }}" class="text-decoration-none">Request new code</a>
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
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('portal-reset-password-form');
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const submit = document.getElementById('portal-reset-password-submit');
            const message = document.getElementById('password-match-message');

            if (!form || !password || !confirmation || !submit || !message) {
                return;
            }

            const setState = (text, cls) => {
                message.textContent = text;
                message.classList.remove('text-secondary', 'text-success', 'text-danger');
                message.classList.add(cls);
            };

            const validate = () => {
                const p = password.value;
                const c = confirmation.value;

                if (!p && !c) {
                    submit.disabled = false;
                    setState('Use at least 8 characters with letters and numbers.', 'text-secondary');
                    return true;
                }

                if (p.length < 8) {
                    submit.disabled = true;
                    setState('Password must be at least 8 characters.', 'text-danger');
                    return false;
                }

                if (c && p !== c) {
                    submit.disabled = true;
                    setState('Password and Confirm Password must match.', 'text-danger');
                    return false;
                }

                if (c && p === c) {
                    submit.disabled = false;
                    setState('Passwords match.', 'text-success');
                    return true;
                }

                submit.disabled = false;
                setState('Confirm your password to continue.', 'text-secondary');
                return true;
            };

            password.addEventListener('input', validate);
            confirmation.addEventListener('input', validate);
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
            form.addEventListener('submit', (event) => {
                if (!validate()) {
                    event.preventDefault();
                }
            });

            validate();
        })();
    </script>
@endpush
