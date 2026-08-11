@php
    $record = $alumni ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="student_id">Student ID</label>
        <input id="student_id" type="text" name="student_id" class="form-control" value="{{ old('student_id', $record?->student_id_display ?? '') }}" data-student-id-format required>
        <div class="form-text">Use the same ID number shown in the imported Excel/CSV file.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="education_level">School Level</label>
        <select id="education_level" name="education_level" class="form-select" required>
            <option value="">Select level</option>
            @foreach (($educationLevels ?? []) as $level)
                <option value="{{ $level }}" @selected(old('education_level', $record?->education_level) === $level)>{{ $level }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="course">Program / Grade / Course</label>
        <input id="course" type="text" name="course" class="form-control" value="{{ old('course', $record?->course) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="first_name">First Name</label>
        <input id="first_name" type="text" name="first_name" class="form-control" value="{{ old('first_name', $record?->first_name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="last_name">Last Name</label>
        <input id="last_name" type="text" name="last_name" class="form-control" value="{{ old('last_name', $record?->last_name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="year_graduated">Graduation Year</label>
        <input id="year_graduated" type="number" min="1900" max="{{ now()->year + 1 }}" name="year_graduated" class="form-control" value="{{ old('year_graduated', $record?->year_graduated) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="birthday">Birthday</label>
        <input id="birthday" type="date" name="birthday" class="form-control" value="{{ old('birthday', $record?->birthday?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="email">Email</label>
        <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $record?->email) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="contact_number">Contact Number</label>
        <input id="contact_number" type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $record?->contact_number) }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="address">Address</label>
        <textarea id="address" name="address" class="form-control" rows="4">{{ old('address', $record?->address) }}</textarea>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">{{ $submitLabel ?? 'Save' }}</button>
    <a href="{{ route('alumni.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
