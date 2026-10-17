@extends('layouts.app')

@section('title', 'Request Alumni Portal Account')
@section('centered_guest', true)
@section('school_guest_header', true)

@section('content')
    @php
        // Keep the form renderable when an older deployed controller omits these limits.
        $registrationToday ??= now()->toDateString();
        $maximumGraduationYear ??= now()->year + 1;
        $birthdayMaxDate = \App\Support\BirthDate::maximumDate($registrationToday);

        // A malformed request can flash array values; only render plain field values.
        $registrationValue = static function (string $field): string {
            $value = old($field);

            return is_string($value) || is_int($value) ? (string) $value : '';
        };
    @endphp
    <div class="portal-register-shell">
        <div class="page-card portal-register-card p-3 p-md-5">
            <div class="text-center mb-4">
                <div class="portal-register-brand-mark mx-auto mb-3">
                    <img src="{{ asset('images/sbc-logo.svg') }}" alt="St. Bridget College Alumni Association seal">
                </div>
                <h1 class="h3 mb-2">Request your alumni portal account</h1>
                <p class="text-secondary mb-0">Submit your school details and Gmail for administrator review. You can log in after approval, then verify your Gmail with a one-time OTP.</p>
            </div>

            <form method="POST" action="{{ route('portal.register.store') }}" id="portal-register-form" data-today="{{ $registrationToday }}" data-maximum-graduation-year="{{ $maximumGraduationYear }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="student_id" class="form-label">Student ID <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input id="student_id" type="text" class="form-control @error('student_id') is-invalid @enderror" name="student_id" value="{{ $registrationValue('student_id') }}" maxlength="50" placeholder="e.g. 12-3456-789" aria-invalid="{{ $errors->has('student_id') ? 'true' : 'false' }}" aria-describedby="student_id-hint student_id-error" data-student-id-format data-registration-field>
                        <div id="student_id-hint" class="form-text">Enter your school ID if you still know it. You can continue without it if it was lost or forgotten.</div>
                        <div id="student_id-error" class="invalid-feedback {{ $errors->has('student_id') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('student_id')) hidden @endif>{{ $errors->first('student_id') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ $registrationValue('first_name') }}" maxlength="100" autocomplete="given-name" placeholder="e.g. Juan" aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}" aria-describedby="first_name-hint first_name-error" data-registration-field required>
                        <div id="first_name-hint" class="form-text">Use letters, spaces, apostrophes, hyphens, or periods for names.</div>
                        <div id="first_name-error" class="invalid-feedback {{ $errors->has('first_name') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('first_name')) hidden @endif>{{ $errors->first('first_name') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="middle_name" class="form-label">Middle Name <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input id="middle_name" type="text" class="form-control @error('middle_name') is-invalid @enderror" name="middle_name" value="{{ $registrationValue('middle_name') }}" maxlength="100" autocomplete="additional-name" placeholder="e.g. Santos" aria-invalid="{{ $errors->has('middle_name') ? 'true' : 'false' }}" aria-describedby="first_name-hint middle_name-error" data-registration-field>
                        <div id="middle_name-error" class="invalid-feedback {{ $errors->has('middle_name') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('middle_name')) hidden @endif>{{ $errors->first('middle_name') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ $registrationValue('last_name') }}" maxlength="100" autocomplete="family-name" placeholder="e.g. Dela Cruz" aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}" aria-describedby="first_name-hint last_name-error" data-registration-field required>
                        <div id="last_name-error" class="invalid-feedback {{ $errors->has('last_name') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('last_name')) hidden @endif>{{ $errors->first('last_name') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="maiden_name" class="form-label">Maiden Name <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input id="maiden_name" type="text" class="form-control @error('maiden_name') is-invalid @enderror" name="maiden_name" value="{{ $registrationValue('maiden_name') }}" maxlength="100" placeholder="e.g. Santos" aria-invalid="{{ $errors->has('maiden_name') ? 'true' : 'false' }}" aria-describedby="first_name-hint maiden_name-hint maiden_name-error" data-registration-field>
                        <div id="maiden_name-hint" class="form-text">Enter your surname before marriage, if applicable.</div>
                        <div id="maiden_name-error" class="invalid-feedback {{ $errors->has('maiden_name') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('maiden_name')) hidden @endif>{{ $errors->first('maiden_name') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="birthday" class="form-label">Birthday</label>
                        <input id="birthday" type="text" class="form-control @error('birthday') is-invalid @enderror" name="birthday" value="{{ $registrationValue('birthday') }}" placeholder="YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" data-maximum-date="{{ $birthdayMaxDate }}" autocomplete="bday" aria-controls="birthday-calendar" aria-expanded="false" aria-invalid="{{ $errors->has('birthday') ? 'true' : 'false' }}" aria-describedby="birthday-hint birthday-error birthday-age" data-registration-field required>
                        <div id="birthday-hint" class="form-text">Choose your birthday from the calendar. Use the month and year selectors to jump directly to older years, such as 1950.</div>
                        @include('portal.auth._birthday-calendar')
                        <output id="birthday-age" class="form-text d-block" aria-live="polite">Age will appear after you select a birthday.</output>
                        <div id="birthday-error" class="invalid-feedback {{ $errors->has('birthday') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('birthday')) hidden @endif>{{ $errors->first('birthday') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="education_level" class="form-label">School Level</label>
                        <select id="education_level" class="form-select @error('education_level') is-invalid @enderror" name="education_level" aria-invalid="{{ $errors->has('education_level') ? 'true' : 'false' }}" aria-describedby="education_level-hint education_level-error" data-registration-field required>
                            <option value="">Select level</option>
                            @foreach (array_keys($programsByLevel) as $level)
                                <option value="{{ $level }}" @selected($registrationValue('education_level') === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                        <div id="education_level-hint" class="form-text">Select the school level for the record the administrator will verify.</div>
                        <div id="education_level-error" class="invalid-feedback {{ $errors->has('education_level') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('education_level')) hidden @endif>{{ $errors->first('education_level') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="course" class="form-label">Program / Grade / Course</label>
                        <select id="course" class="form-select @error('course') is-invalid @enderror" name="course" aria-invalid="{{ $errors->has('course') ? 'true' : 'false' }}" aria-describedby="course-hint course-error" data-registration-field required>
                            <option value="">Select grade, strand, or course</option>
                            @foreach ($programsByLevel as $level => $programs)
                                <optgroup label="{{ $level }}" data-level="{{ $level }}">
                                    @foreach ($programs as $program)
                                        <option value="{{ $program }}" @selected($registrationValue('course') === $program && $registrationValue('education_level') === $level)>{{ $program }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <div id="course-hint" class="form-text">Select the program, grade, or course you completed.</div>
                        <div id="course-error" class="invalid-feedback {{ $errors->has('course') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('course')) hidden @endif>{{ $errors->first('course') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="year_graduated" class="form-label">Graduation Year</label>
                        <input id="year_graduated" type="number" min="1920" max="{{ $maximumGraduationYear }}" step="1" class="form-control @error('year_graduated') is-invalid @enderror" name="year_graduated" value="{{ $registrationValue('year_graduated') }}" inputmode="numeric" placeholder="e.g. {{ now()->year }}" aria-invalid="{{ $errors->has('year_graduated') ? 'true' : 'false' }}" aria-describedby="year_graduated-hint year_graduated-error" data-registration-field required>
                        <div id="year_graduated-hint" class="form-text">Enter a year from 1920 to {{ $maximumGraduationYear }}.</div>
                        <div id="year_graduated-error" class="invalid-feedback {{ $errors->has('year_graduated') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('year_graduated')) hidden @endif>{{ $errors->first('year_graduated') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $registrationValue('email') }}" maxlength="255" autocomplete="email" autocapitalize="none" spellcheck="false" placeholder="e.g. juan.delacruz@gmail.com" pattern="^[A-Za-z0-9._%+\-]+@[Gg][Mm][Aa][Ii][Ll]\.[Cc][Oo][Mm]$" title="Use a valid Gmail address ending in @gmail.com." aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-hint email-error" data-registration-field required>
                        <div id="email-hint" class="form-text">Only Gmail accounts ending in @gmail.com are accepted.</div>
                        <div id="email-error" class="invalid-feedback {{ $errors->has('email') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="contact_number" class="form-label">Contact Number <span class="text-secondary fw-normal">(Optional)</span></label>
                        <input id="contact_number" type="tel" class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ $registrationValue('contact_number') }}" minlength="11" maxlength="11" autocomplete="tel" inputmode="numeric" pattern="09[0-9]{9}" title="Enter exactly 11 digits starting with 09, such as 09123456789." placeholder="e.g. 09123456789" aria-invalid="{{ $errors->has('contact_number') ? 'true' : 'false' }}" aria-describedby="contact_number-hint contact_number-error" data-registration-field>
                        <div id="contact_number-hint" class="form-text">Enter exactly 11 digits starting with 09. Numbers only.</div>
                        <div id="contact_number-error" class="invalid-feedback {{ $errors->has('contact_number') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('contact_number')) hidden @endif>{{ $errors->first('contact_number') }}</div>
                    </div>
                    <div class="col-12">
                        <label for="address" class="form-label">Address <span class="text-secondary fw-normal">(Optional)</span></label>
                        <textarea id="address" class="form-control @error('address') is-invalid @enderror" name="address" rows="3" maxlength="1000" autocomplete="street-address" placeholder="e.g. Barangay, City, Province" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" aria-describedby="address-error" data-registration-field>{{ $registrationValue('address') }}</textarea>
                        <div id="address-error" class="invalid-feedback {{ $errors->has('address') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('address')) hidden @endif>{{ $errors->first('address') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <div class="portal-password-field">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="At least 8 characters" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-hint password-error password-match-message" data-registration-field required>
                            <button type="button" class="portal-password-toggle" data-password-toggle="password" data-password-label="password" aria-label="Show password" aria-controls="password" aria-pressed="false" title="Show password">
                                <svg class="portal-password-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                    <circle cx="12" cy="12" r="2.75" />
                                    <path class="portal-password-eye-slash" d="M4 4l16 16" />
                                </svg>
                            </button>
                        </div>
                        <div id="password-hint" class="form-text">Use at least 8 characters with letters and numbers (up to 72 bytes).</div>
                        <div id="password-error" class="invalid-feedback {{ $errors->has('password') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="portal-password-field">
                            <input id="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Retype your password" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password_confirmation-error password-match-message" data-registration-field required>
                            <button type="button" class="portal-password-toggle" data-password-toggle="password_confirmation" data-password-label="confirm password" aria-label="Show confirm password" aria-controls="password_confirmation" aria-pressed="false" title="Show confirm password">
                                <svg class="portal-password-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                    <circle cx="12" cy="12" r="2.75" />
                                    <path class="portal-password-eye-slash" d="M4 4l16 16" />
                                </svg>
                            </button>
                        </div>
                        <div id="password_confirmation-error" class="invalid-feedback {{ $errors->has('password_confirmation') ? 'd-block' : '' }}" aria-live="polite" @if (! $errors->has('password_confirmation')) hidden @endif>{{ $errors->first('password_confirmation') }}</div>
                    </div>
                    <div class="col-12">
                        <div id="password-match-message" class="small text-secondary" aria-live="polite"></div>
                    </div>
                </div>

                <button type="submit" id="portal-register-submit" class="btn btn-success w-100 mt-4">
                    Submit Account Request
                </button>
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
        .birthday-calendar summary {
            cursor: pointer;
            color: #155e3b;
            padding: .5rem 0;
            font-weight: 600;
        }

        .birthday-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .25rem;
            text-align: center;
        }

        .portal-register-card .birthday-calendar-grid .btn {
            min-width: 0;
            min-height: 2.5rem;
            width: 100%;
            padding: .25rem;
            white-space: nowrap;
        }

        .guest-centered-main {
            width: 100%;
            max-width: none;
            padding: 0 !important;
            background: transparent;
        }

        .guest-shell {
            min-height: 100vh;
            min-height: 100svh;
            background-image:
                linear-gradient(115deg, rgba(247, 252, 255, 0.96), rgba(221, 240, 255, 0.9) 52%, rgba(201, 224, 255, 0.78)),
                url("{{ asset('images/alumni-header.jpg') }}");
            background-position: center, center;
            background-size: auto, cover;
            background-repeat: no-repeat, no-repeat;
            background-attachment: fixed, fixed;
        }

        .guest-shell .app-main.guest-centered-main {
            align-items: stretch !important;
            justify-content: flex-start !important;
            padding: 0 !important;
        }

        .portal-register-shell {
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

        .portal-register-shell::after {
            content: "";
            position: absolute;
            z-index: -1;
            width: min(36rem, 70vw);
            aspect-ratio: 1;
            right: -12rem;
            bottom: -18rem;
            border-radius: 50%;
            background: rgba(214, 167, 0, 0.16);
            filter: blur(0.25rem);
            pointer-events: none;
        }

        .portal-register-card {
            width: min(100%, 68rem);
            margin: 0;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(11, 69, 184, 0.18);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 1.2rem 3.2rem rgba(38, 87, 130, 0.18);
            backdrop-filter: blur(0.4rem);
        }

        .portal-register-card::before {
            display: none;
        }

        .portal-register-brand-mark {
            width: 4.5rem;
            height: 4.5rem;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(11, 69, 184, 0.16);
            box-shadow: 0 0.8rem 1.9rem rgba(7, 17, 111, 0.16);
        }

        .portal-register-brand-mark img {
            width: 3.55rem;
            height: 3.55rem;
            object-fit: contain;
        }

        .portal-register-card .form-control,
        .portal-register-card .form-select {
            border-color: rgba(11, 69, 184, 0.24);
            background-color: rgba(255, 255, 255, 0.9);
        }

        .portal-register-card .form-control:focus,
        .portal-register-card .form-select:focus {
            border-color: #d6a700;
            box-shadow: 0 0 0 0.18rem rgba(214, 167, 0, 0.2);
        }

        .portal-register-card .form-control.is-invalid,
        .portal-register-card .form-select.is-invalid {
            border-color: #dc3545;
        }

        .portal-register-card .form-control.is-invalid:focus,
        .portal-register-card .form-select.is-invalid:focus {
            box-shadow: 0 0 0 0.18rem rgba(220, 53, 69, 0.2);
        }

        .portal-password-field .form-control.is-invalid {
            background-image: none;
        }

        .portal-register-card .btn-success {
            border: 0;
            background: linear-gradient(135deg, #07116f, #0b45b8);
            box-shadow: 0 0.7rem 1.3rem rgba(7, 17, 111, 0.18);
        }

        .portal-register-card .btn-success:hover,
        .portal-register-card .btn-success:focus-visible {
            background: linear-gradient(135deg, #07116f, #073b9d);
            box-shadow: 0 0.9rem 1.6rem rgba(7, 17, 111, 0.24);
        }

        .portal-register-card .text-decoration-none {
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
            .guest-shell {
                background-attachment: scroll, scroll;
            }

            .portal-register-shell {
                padding: 1rem;
            }

            .portal-register-brand-mark {
                width: 4rem;
                height: 4rem;
            }

            .portal-register-brand-mark img {
                width: 3.15rem;
                height: 3.15rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script type="module" src="{{ asset('js/portal-registration.js') }}?v={{ filemtime(public_path('js/portal-registration.js')) }}"></script>
@endpush
