@extends('layouts.app')

@section('title', 'Request Alumni Portal Account')

@section('content')
    <div class="page-card p-3 p-md-5">
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
                    <input id="student_id" type="text" class="form-control" name="student_id" value="{{ old('student_id') }}" data-student-id-format required>
                    <div class="form-text">Use the same ID number shown in your school record.</div>
                </div>
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name</label>
                    <input id="first_name" type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input id="last_name" type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="birthday" class="form-label">Birthday</label>
                    <input id="birthday" type="date" class="form-control" name="birthday" value="{{ old('birthday') }}">
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
                    <input id="course" type="text" class="form-control" name="course" value="{{ old('course') }}">
                    <div class="form-text">Optional when you are claiming an existing alumni record.</div>
                </div>
                <div class="col-md-6">
                    <label for="year_graduated" class="form-label">Graduation Year</label>
                    <input id="year_graduated" type="number" min="1900" max="{{ now()->year + 1 }}" class="form-control" name="year_graduated" value="{{ old('year_graduated') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input id="contact_number" type="text" class="form-control" name="contact_number" value="{{ old('contact_number') }}">
                </div>
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" class="form-control" name="address" rows="3">{{ old('address') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" minlength="8" required>
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" autocomplete="new-password" minlength="8" required>
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
@endsection

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
            form.addEventListener('submit', (event) => {
                if (!validate()) {
                    event.preventDefault();
                }
            });

            validate();
        })();
    </script>
@endpush
