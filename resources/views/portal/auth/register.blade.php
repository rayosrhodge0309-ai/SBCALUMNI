@extends('layouts.app')

@section('title', 'Request Alumni Portal Account')
@section('centered_guest', true)

@section('content')
    <div class="portal-register-shell">
        <div class="page-card portal-register-card p-3 p-md-5">
            <div class="text-center mb-4">
                <div class="stat-pill text-success bg-success-subtle mb-3">Self Registration</div>
                <h1 class="h3 mb-2">Request or claim your alumni portal access</h1>
                <p class="text-secondary mb-0">If your alumni record is already in the system, you can claim it right away and continue to OTP verification. New records will still be reviewed by the administrator before portal login is enabled.</p>
            </div>

            <form method="POST" action="{{ route('portal.register.store') }}" id="portal-register-form">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input id="student_id" type="text" class="form-control" name="student_id" value="{{ old('student_id') }}" placeholder="e.g. 12-3456-789" data-student-id-format required>
                        <div class="form-text">Use the same ID number shown in your school record.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input id="first_name" type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Juan" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input id="last_name" type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Dela Cruz" required>
                    </div>
                    <div class="col-md-6">
                        <label for="birthday" class="form-label">Birthday</label>
                        <input id="birthday" type="date" class="form-control" name="birthday" value="{{ old('birthday') }}" placeholder="e.g. 2004-05-20">
                        <div class="form-text">Example: May 20, 2004.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="education_level" class="form-label">School Level</label>
                        <select id="education_level" class="form-select" name="education_level">
                            <option value="">Select level</option>
                            @foreach (['Elementary', 'Junior High School', 'Senior High School', 'College'] as $level)
                                <option value="{{ $level }}" @selected(old('education_level') === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Optional when you are claiming an existing alumni record.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="course" class="form-label">Program / Grade / Course</label>
                        <input id="course" type="text" class="form-control" name="course" value="{{ old('course') }}" placeholder="e.g. BS Information Technology or Grade 12 - STEM">
                        <div class="form-text">Optional when you are claiming an existing alumni record.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="year_graduated" class="form-label">Graduation Year</label>
                        <input id="year_graduated" type="number" min="1900" max="{{ now()->year + 1 }}" class="form-control" name="year_graduated" value="{{ old('year_graduated') }}" placeholder="e.g. 2025" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="e.g. juan.delacruz@gmail.com" required>
                    </div>
                    <div class="col-md-6">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input id="contact_number" type="text" class="form-control" name="contact_number" value="{{ old('contact_number') }}" placeholder="e.g. 09123456789">
                    </div>
                    <div class="col-12">
                        <label for="address" class="form-label">Address <span class="text-secondary fw-normal">(Optional)</span></label>
                        <textarea id="address" class="form-control" name="address" rows="3" placeholder="e.g. Barangay, City, Province">{{ old('address') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <div class="portal-password-field">
                            <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" minlength="8" placeholder="At least 8 characters" required>
                            <button type="button" class="portal-password-toggle" data-password-toggle="password" data-password-label="password" aria-label="Show password" title="Show password">
                                <svg class="portal-password-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                    <circle cx="12" cy="12" r="2.75" />
                                    <path class="portal-password-eye-slash" d="M4 4l16 16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="portal-password-field">
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" autocomplete="new-password" minlength="8" placeholder="Retype your password" required>
                            <button type="button" class="portal-password-toggle" data-password-toggle="password_confirmation" data-password-label="confirm password" aria-label="Show confirm password" title="Show confirm password">
                                <svg class="portal-password-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                    <circle cx="12" cy="12" r="2.75" />
                                    <path class="portal-password-eye-slash" d="M4 4l16 16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div id="password-match-message" class="small text-secondary"></div>
                    </div>
                </div>

                <button type="submit" id="portal-register-submit" class="btn btn-success w-100 mt-4">Submit Account Request</button>
            </form>

            <div class="text-center mt-4 text-secondary">
                Already approved by admin?
                <a href="{{ route('portal.login') }}" class="text-decoration-none">Login here</a>
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

        .portal-register-shell {
            width: 100%;
            min-height: calc(100vh - 12rem);
            display: grid;
            place-items: center;
            padding: 1rem;
            box-sizing: border-box;
        }

        .portal-register-card {
            width: min(100%, 68rem);
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
            const form = document.getElementById('portal-register-form');
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const submit = document.getElementById('portal-register-submit');
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
