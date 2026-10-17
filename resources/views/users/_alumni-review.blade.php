@php
    $recordSearch ??= trim((string) request()->query('record_search', ''));
    $recordMatchError ??= null;
    if (! isset($matchingRecords)) {
        // Missing lookup data is not proof that no school record exists.
        $recordMatchError = $recordMatchError ?: 'School-record matching is temporarily unavailable. You can still review the submitted account details.';
    }
    $profile = $managedUser->registration_profile;
    $reviewFields = [
        'student_id' => 'Student ID', 'first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name',
        'maiden_name' => 'Maiden Name', 'birthday' => 'Birthday', 'age' => 'Age (years)', 'education_level' => 'School Level',
        'course' => 'Program / Grade / Course', 'year_graduated' => 'Graduation Year',
        'email' => 'Gmail / Email', 'contact_number' => 'Contact Number', 'address' => 'Address',
    ];
    $fieldValue = static function ($record, $field) {
        return $field === 'birthday' ? $record->birthday?->format('F j, Y') : $record->{$field};
    };
    $submittedValue = static function ($field) use ($managedUser, $profile, $fieldValue) {
        return match ($field) {
            'email' => $managedUser->email,
            'first_name' => $managedUser->registration_details['first_name'] ?? $profile->first_name,
            'middle_name' => $managedUser->registration_details['middle_name'] ?? null,
            default => $fieldValue($profile, $field),
        };
    };
@endphp

<div class="page-card p-4 mt-4" id="alumni-record-review">
    <h2 class="h5">Review Submitted Details</h2>
    <p class="text-secondary">Compare this account request with the school list before approving access. Approval keeps the account in User Accounts. Use Add to Alumni Records separately after approval.</p>
    <dl class="row mb-0">
        @foreach ($reviewFields as $field => $label)
            <dt class="col-sm-4">{{ $label }}</dt>
            @php
                $value = $submittedValue($field);
            @endphp
            <dd class="col-sm-8">{{ filled($value) ? $value : 'Not provided' }}</dd>
        @endforeach
    </dl>
</div>

<div class="page-card p-4 mt-4" id="add-alumni-record">
    <h2 class="h5">Compare with Alumni Records</h2>
    <p class="text-secondary">Possible matches use the student ID, name, maiden name, or Gmail. Check every detail and select the correct school record. Existing school details are preserved when the account is linked.</p>
    @if (filled($managedUser->registration_details['middle_name'] ?? null))
        <p class="small text-secondary">School records may include the middle name together with the first name.</p>
    @endif

    @if ($recordMatchError)
        <div class="alert alert-warning mb-0">{{ $recordMatchError }}</div>
    @else
    <form method="GET" action="{{ route('users.edit', $managedUser) }}" class="d-flex flex-wrap gap-2 mb-3">
        <label for="record_search" class="visually-hidden">Search school records</label>
        <input id="record_search" name="record_search" class="form-control flex-grow-1 w-auto" value="{{ $recordSearch }}" placeholder="Search school records by name or student ID">
        <button class="btn btn-outline-primary" type="submit">Find Record</button>
        @if ($recordSearch !== '')
            <a class="btn btn-outline-secondary" href="{{ route('users.edit', $managedUser) }}#add-alumni-record">Clear</a>
        @endif
    </form>

    @forelse ($matchingRecords as $schoolRecord)
        <details class="border rounded p-3 mb-3" @if ($loop->first) open @endif>
            <summary class="fw-semibold">
                {{ $schoolRecord->full_name }} · {{ $schoolRecord->student_id_display }} · {{ $schoolRecord->academic_label }}
                @if ($schoolRecord->user)
                    <span class="badge text-bg-secondary">Already linked</span>
                @endif
            </summary>
            <div class="table-responsive mt-3">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Detail</th><th>Account Request</th><th>School Record</th></tr></thead>
                    <tbody>
                        @foreach ($reviewFields as $field => $label)
                            @php
                                $submitted = $submittedValue($field);
                                $schoolValue = $fieldValue($schoolRecord, $field);
                                $different = filled($submitted) && filled($schoolValue) && mb_strtolower(trim((string) $submitted)) !== mb_strtolower(trim((string) $schoolValue));
                                if ($field === 'first_name' && $different) {
                                    $different = mb_strtolower(trim((string) $profile->first_name)) !== mb_strtolower(trim((string) $schoolValue));
                                }
                            @endphp
                            <tr @class(['table-warning' => $different])>
                                <th scope="row">{{ $label }}</th>
                                <td>{{ filled($submitted) ? $submitted : 'Not provided' }}</td>
                                <td>{{ filled($schoolValue) ? $schoolValue : 'Not recorded' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        <p class="text-secondary">No matching school record found. Search the school list before adding a new record.</p>
    @endforelse

    @if ($managedUser->alumni_id)
        <div class="alert alert-success mb-0">This account is linked to Alumni Records and remains in User Accounts.</div>
    @elseif ($managedUser->isApproved())
        <form method="POST" action="{{ route('users.alumni-record', $managedUser) }}">
            @csrf
            <div class="form-check mb-2">
                <input id="record_choice_existing" class="form-check-input" type="radio" name="record_choice" value="existing" @checked(old('record_choice', $matchingRecords->isNotEmpty() ? 'existing' : 'new') === 'existing')>
                <label class="form-check-label" for="record_choice_existing">Link to an existing school record</label>
            </div>
            <label class="visually-hidden" for="alumni_record_id">School record</label>
            <select id="alumni_record_id" name="alumni_record_id" class="form-select mb-3">
                <option value="">Select the verified school record</option>
                @foreach ($matchingRecords as $schoolRecord)
                    <option value="{{ $schoolRecord->id }}" @disabled($schoolRecord->user !== null) @selected((string) old('alumni_record_id') === (string) $schoolRecord->id)>
                        {{ $schoolRecord->full_name }} — {{ $schoolRecord->student_id_display }} — {{ $schoolRecord->academic_label }}{{ $schoolRecord->user ? ' (Already linked)' : '' }}
                    </option>
                @endforeach
            </select>
            <div class="form-check mb-3">
                <input id="record_choice_new" class="form-check-input" type="radio" name="record_choice" value="new" @checked(old('record_choice', $matchingRecords->isNotEmpty() ? 'existing' : 'new') === 'new')>
                <label class="form-check-label" for="record_choice_new">Add a new alumni record from the submitted details, if the school list has no matching record</label>
            </div>
            <button type="submit" class="btn btn-primary">Add to Alumni Records</button>
        </form>
    @else
        <p class="text-secondary mb-0">Approve the account after reviewing these details to enable Add to Alumni Records.</p>
    @endif
    @endif
</div>
