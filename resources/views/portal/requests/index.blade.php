@extends('layouts.app')

@section('title', 'My Record Requests')
@section('subtitle', 'Submit a digital request, then personally claim the approved record at school.')

@section('content')
    <div class="page-card p-3 p-sm-4 mb-3 d-lg-none">
        <div class="mobile-portal-badge mb-2">Record Requests</div>
        <h3 class="h5 mb-2">Use your phone to submit and track requests</h3>
        <p class="text-secondary mb-3">
            Tap a button below to jump to the form or scroll to your request history.
        </p>
        <div class="d-grid gap-2">
            <a href="#request-form" class="btn btn-success">New Request</a>
            <a href="#request-history" class="btn btn-outline-dark">View History</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="page-card p-4 h-100" id="request-form">
                <h3 class="h5 mb-3">Submit New Request</h3>
                <form method="POST" action="{{ route('portal.requests.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Linked Alumni Record</label>
                        <input type="text" class="form-control" value="{{ $alumnus->full_name }} - {{ $alumnus->student_id_display }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="request_type">Request Type</label>
                        <select id="request_type" name="request_type" class="form-select" required>
                            <option value="">Select request type</option>
                            @foreach ($requestTypes as $requestType)
                                <option value="{{ $requestType }}" @selected(old('request_type') === $requestType)>{{ $requestType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="request-type-details mb-3 d-none" data-request-details>
                        <div class="request-requirements" data-standard-requirements>
                            <div class="fw-semibold small mb-2">Requirements</div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label" for="requester_name">Name</label>
                                    <input id="requester_name" type="text" name="requester_name" class="form-control" value="{{ old('requester_name', $alumnus->full_name) }}" placeholder="e.g. Juan Dela Cruz" data-standard-requirement-input>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="requester_course">Course</label>
                                    <input id="requester_course" type="text" name="requester_course" class="form-control" value="{{ old('requester_course', $alumnus->course) }}" placeholder="e.g. BS Information Technology" data-standard-requirement-input>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="requester_year_graduate">Yr Graduate</label>
                                    <input id="requester_year_graduate" type="number" min="1900" max="{{ now()->year + 1 }}" name="requester_year_graduate" class="form-control" value="{{ old('requester_year_graduate', $alumnus->year_graduated) }}" placeholder="e.g. 2025" data-standard-requirement-input>
                                </div>
                            </div>
                        </div>
                        <div class="d-none mt-3" data-facility-note>
                            <label class="form-label" for="requester_note">Message / Note</label>
                            <textarea id="requester_note" name="requester_note" class="form-control" rows="4" placeholder="e.g. Our Batch 2025 would like to request facility use for a reunion. Preferred date: June 15, 2026.">{{ old('requester_note') }}</textarea>
                            <div class="form-text">Include the purpose, preferred date, batch year, and facility you want to use.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="year_requested">Graduation / Record Year</label>
                        <input id="year_requested" type="number" min="1900" max="{{ now()->year + 1 }}" name="year_requested" class="form-control" value="{{ old('year_requested', $alumnus->year_graduated) }}" required>
                    </div>
                    <div class="small text-secondary mb-3">
                        After online submission, the school administrator will process the request. You will retrieve the released document personally from the school.
                    </div>
                    <button class="btn btn-success w-100" type="submit">Submit Request</button>
                </form>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="page-card p-0 overflow-hidden" id="request-history">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" data-mobile-card-table>
                        <thead class="table-light">
                            <tr>
                                <th>Request</th>
                                <th>Year</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Admin Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $request)
                                <tr>
                                    <td data-label="Request">{{ $request->request_type }}</td>
                                    <td data-label="Year">{{ $request->year_requested }}</td>
                                    <td data-label="Message">{!! $request->requester_note ? nl2br(e($request->requester_note)) : '-' !!}</td>
                                    <td class="fw-semibold" data-label="Status">{{ $statusOptions[$request->status] ?? ucfirst(str_replace('_', ' ', $request->status)) }}</td>
                                    <td data-label="Admin Notes">{{ $request->admin_notes ?: 'No notes from the school yet.' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-5">You have not submitted any requests yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($requests->hasPages())
                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .request-type-details {
            border: 1px solid rgba(11, 69, 184, 0.18);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.62);
            padding: 0.85rem;
        }

        .request-requirements {
            color: var(--action-dark);
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const requestType = document.getElementById('request_type');
            const details = document.querySelector('[data-request-details]');
            const requirements = document.querySelector('[data-standard-requirements]');
            const facilityNote = document.querySelector('[data-facility-note]');
            const requesterNote = document.getElementById('requester_note');
            const standardInputs = document.querySelectorAll('[data-standard-requirement-input]');
            const facilityType = 'Facility Use-(Message/Note)';

            if (!requestType || !details || !requirements || !facilityNote || !requesterNote) {
                return;
            }

            const updateDetails = () => {
                const selectedType = requestType.value;
                const isFacilityUse = selectedType === facilityType;
                const hasRequestType = selectedType !== '';

                details.classList.toggle('d-none', !hasRequestType);
                requirements.classList.toggle('d-none', !hasRequestType || isFacilityUse);
                facilityNote.classList.toggle('d-none', !isFacilityUse);
                requesterNote.required = isFacilityUse;
                requesterNote.disabled = !isFacilityUse;

                standardInputs.forEach((input) => {
                    input.required = hasRequestType && !isFacilityUse;
                    input.disabled = !hasRequestType || isFacilityUse;
                });
            };

            requestType.addEventListener('change', updateDetails);
            updateDetails();
        })();
    </script>
@endpush
