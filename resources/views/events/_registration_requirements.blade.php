@php
    $registrationFieldId = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($registrationFieldId ?? 'event_registration'));
    $registrationEventId = (int) ($registrationEventId ?? 0);
    $registrationAlumnus = $registrationAlumnus ?? auth()->user()?->alumni;
    $registrationUser = $registrationUser ?? auth()->user();
    $registrationExpanded = (bool) ($registrationExpanded ?? false);
    $registrationHasErrors = (bool) ($registrationHasErrors ?? false);
    $registrationSubmitClass = (string) ($registrationSubmitClass ?? 'btn btn-primary');
    $defaultName = $registrationAlumnus?->full_name ?: $registrationUser?->name;
    $defaultEmail = \App\Support\GmailAddress::normalize($registrationUser?->email ?: $registrationAlumnus?->email);
    $invalid = fn (string $field): bool => $registrationHasErrors && $errors->has($field);
@endphp

<div id="{{ $registrationFieldId }}" class="event-registration-requirements mt-3 {{ $registrationExpanded ? '' : 'd-none' }}" data-event-registration-fields>
    <input type="hidden" name="event_registration_event_id" value="{{ $registrationEventId }}" data-event-registration-input @disabled(! $registrationExpanded)>

    <div class="event-registration-requirements-title">Registration Requirements</div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="{{ $registrationFieldId }}_attendee_name">Full Name</label>
            <input
                id="{{ $registrationFieldId }}_attendee_name"
                type="text"
                name="attendee_name"
                class="form-control {{ $invalid('attendee_name') ? 'is-invalid' : '' }}"
                value="{{ old('attendee_name', $defaultName) }}"
                autocomplete="name"
                data-event-registration-input
                required
                @disabled(! $registrationExpanded)>
            @if ($invalid('attendee_name'))
                <div class="invalid-feedback">{{ $errors->first('attendee_name') }}</div>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label" for="{{ $registrationFieldId }}_attendee_email">Verified Gmail</label>
            <input
                id="{{ $registrationFieldId }}_attendee_email"
                type="email"
                name="attendee_email"
                class="form-control {{ $invalid('attendee_email') ? 'is-invalid' : '' }}"
                value="{{ old('attendee_email', $defaultEmail) }}"
                autocomplete="email"
                data-event-registration-input
                required
                @disabled(! $registrationExpanded)>
            @if ($invalid('attendee_email'))
                <div class="invalid-feedback">{{ $errors->first('attendee_email') }}</div>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label" for="{{ $registrationFieldId }}_attendee_student_id">Student ID</label>
            <input
                id="{{ $registrationFieldId }}_attendee_student_id"
                type="text"
                name="attendee_student_id"
                class="form-control {{ $invalid('attendee_student_id') ? 'is-invalid' : '' }}"
                value="{{ old('attendee_student_id', $registrationAlumnus?->student_id_display) }}"
                data-event-registration-input
                required
                @disabled(! $registrationExpanded)>
            @if ($invalid('attendee_student_id'))
                <div class="invalid-feedback">{{ $errors->first('attendee_student_id') }}</div>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label" for="{{ $registrationFieldId }}_attendee_contact_number">Contact Number</label>
            <input
                id="{{ $registrationFieldId }}_attendee_contact_number"
                type="text"
                name="attendee_contact_number"
                class="form-control {{ $invalid('attendee_contact_number') ? 'is-invalid' : '' }}"
                value="{{ old('attendee_contact_number', $registrationAlumnus?->contact_number) }}"
                inputmode="tel"
                autocomplete="tel"
                data-event-registration-input
                required
                @disabled(! $registrationExpanded)>
            @if ($invalid('attendee_contact_number'))
                <div class="invalid-feedback">{{ $errors->first('attendee_contact_number') }}</div>
            @endif
        </div>

        <div class="col-md-8">
            <label class="form-label" for="{{ $registrationFieldId }}_attendee_course">Course / Strand</label>
            <input
                id="{{ $registrationFieldId }}_attendee_course"
                type="text"
                name="attendee_course"
                class="form-control {{ $invalid('attendee_course') ? 'is-invalid' : '' }}"
                value="{{ old('attendee_course', $registrationAlumnus?->course) }}"
                data-event-registration-input
                required
                @disabled(! $registrationExpanded)>
            @if ($invalid('attendee_course'))
                <div class="invalid-feedback">{{ $errors->first('attendee_course') }}</div>
            @endif
        </div>

        <div class="col-md-4">
            <label class="form-label" for="{{ $registrationFieldId }}_attendee_year_graduated">Year Graduated</label>
            <input
                id="{{ $registrationFieldId }}_attendee_year_graduated"
                type="number"
                name="attendee_year_graduated"
                class="form-control {{ $invalid('attendee_year_graduated') ? 'is-invalid' : '' }}"
                value="{{ old('attendee_year_graduated', $registrationAlumnus?->year_graduated) }}"
                min="1900"
                max="2100"
                inputmode="numeric"
                data-event-registration-input
                required
                @disabled(! $registrationExpanded)>
            @if ($invalid('attendee_year_graduated'))
                <div class="invalid-feedback">{{ $errors->first('attendee_year_graduated') }}</div>
            @endif
        </div>

        <div class="col-12">
            <label class="form-label" for="{{ $registrationFieldId }}_attendee_note">Message / Special Concern</label>
            <textarea
                id="{{ $registrationFieldId }}_attendee_note"
                name="attendee_note"
                class="form-control {{ $invalid('attendee_note') ? 'is-invalid' : '' }}"
                rows="3"
                placeholder="Optional note for the event admin"
                data-event-registration-input
                @disabled(! $registrationExpanded)>{{ old('attendee_note') }}</textarea>
            @if ($invalid('attendee_note'))
                <div class="invalid-feedback">{{ $errors->first('attendee_note') }}</div>
            @endif
        </div>
    </div>

    <div class="d-grid d-sm-flex gap-2 mt-3">
        <button type="submit" class="{{ $registrationSubmitClass }}">Submit Registration</button>
        <button type="button" class="btn btn-outline-secondary" data-event-registration-cancel>Cancel</button>
    </div>
</div>
