@extends('layouts.app')

@section('title', 'Event Registrants')
@section('subtitle', 'Review alumni students who registered for posted events and send admin replies.')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div class="d-flex flex-wrap gap-2">
            <span class="badge text-bg-warning registration-summary-badge">{{ $pendingRegistrationCount }} pending</span>
            <span class="badge text-bg-primary registration-summary-badge">{{ $registrations->total() }} shown</span>
        </div>
        <a href="{{ route('events.index') }}" class="btn btn-outline-primary align-self-start align-self-md-center">Back to Events</a>
    </div>

    <div class="page-card p-3 p-md-4 mb-4">
        <form method="GET" action="{{ route('event-registrations.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="event_id">Event</label>
                <select id="event_id" name="event_id" class="form-select">
                    <option value="">All events</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" @selected($selectedEventId === $event->id)>
                            {{ $event->title }} ({{ $event->registrations_count }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $statusValue => $statusLabel)
                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-grid d-sm-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('event-registrations.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 event-registrants-table" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th>Alumni Student</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Admin Reply</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $registration)
                        @php
                            $alumnus = $registration->alumni;
                            $event = $registration->event;
                            $registrationStatus = $statusOptions[$registration->status] ?? $registration->status_label;
                            $registrationBadge = match ($registration->status) {
                                \App\Models\EventRegistration::STATUS_REGISTERED => 'text-bg-success',
                                \App\Models\EventRegistration::STATUS_DECLINED => 'text-bg-danger',
                                default => 'text-bg-warning',
                            };
                            $registrationName = $registration->attendee_name ?: ($alumnus?->full_name ?? $registration->user?->name ?? 'Alumni student');
                            $registrationStudentId = $registration->attendee_student_id ?: ($alumnus?->student_id_display ?? 'No student ID');
                            $registrationEmail = $registration->attendee_email ?: ($registration->user?->email ?: 'No Gmail submitted');
                            $registrationCourse = $registration->attendee_course ?: ($alumnus?->course ?: 'Not specified');
                            $registrationYearGraduated = $registration->attendee_year_graduated ?: ($alumnus?->year_graduated ?: 'Not specified');
                            $registrationContact = $registration->attendee_contact_number ?: ($alumnus?->contact_number ?: 'Not specified');
                        @endphp
                        <tr>
                            <td data-label="Alumni Student">
                                <div class="fw-semibold">{{ $registrationName }}</div>
                                <div class="small text-secondary">{{ $registrationStudentId }}</div>
                                <div class="small text-secondary registration-email">
                                    {{ $registrationEmail }}
                                </div>

                                <div class="registration-requirements mt-2">
                                    <div class="small">
                                        <span class="fw-semibold">Course:</span>
                                        {{ $registrationCourse }}
                                    </div>
                                    <div class="small">
                                        <span class="fw-semibold">Year Graduated:</span>
                                        {{ $registrationYearGraduated }}
                                    </div>
                                    <div class="small">
                                        <span class="fw-semibold">Contact:</span>
                                        {{ $registrationContact }}
                                    </div>
                                </div>

                                @if ($registration->attendee_note)
                                    <div class="registration-note mt-2">{{ $registration->attendee_note }}</div>
                                @endif
                            </td>
                            <td data-label="Event">
                                <div class="fw-semibold">{{ $event?->title ?? 'Deleted event' }}</div>
                                <div class="small text-secondary">
                                    {{ $event?->event_date?->format('M d, Y') ?? 'No date' }}
                                    @if ($event?->location)
                                        | {{ $event->location }}
                                    @endif
                                </div>
                            </td>
                            <td data-label="Status">
                                <span class="badge {{ $registrationBadge }}">{{ $registrationStatus }}</span>
                            </td>
                            <td data-label="Registered">
                                {{ $registration->registered_at?->format('M d, Y h:i A') ?? $registration->created_at?->format('M d, Y h:i A') }}
                            </td>
                            <td class="event-registration-reply-cell" data-label="Admin Reply">
                                <form method="POST" action="{{ route('event-registrations.reply', $registration) }}">
                                    @csrf
                                    @method('PATCH')

                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small" for="event_registration_status_{{ $registration->id }}">Status</label>
                                            <select id="event_registration_status_{{ $registration->id }}" name="status" class="form-select event-registration-status-select" required>
                                                @foreach ($statusOptions as $statusValue => $statusLabel)
                                                    <option value="{{ $statusValue }}" @selected(old('status', $registration->status) === $statusValue)>{{ $statusLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small" for="event_registration_reply_{{ $registration->id }}">Message</label>
                                            <textarea id="event_registration_reply_{{ $registration->id }}" name="admin_reply" class="form-control" rows="3" placeholder="Message for the alumni student">{{ old('admin_reply', $registration->admin_reply) }}</textarea>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-3">
                                        <div class="small text-secondary">
                                            @if ($registration->replied_at)
                                                Replied {{ $registration->replied_at->format('M d, Y h:i A') }}
                                                @if ($registration->repliedBy)
                                                    by {{ $registration->repliedBy->name }}
                                                @endif
                                            @else
                                                No admin reply yet.
                                            @endif
                                        </div>
                                        <button type="submit" class="btn btn-primary">Send Reply</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No event registrations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($registrations->hasPages())
        <div class="mt-4">
            {{ $registrations->links() }}
        </div>
    @endif
@endsection

@push('styles')
    <style>
        .registration-summary-badge {
            min-height: 2rem;
            display: inline-flex;
            align-items: center;
            padding-inline: 0.8rem;
        }

        .event-registrants-table th,
        .event-registrants-table td {
            vertical-align: top;
        }

        .event-registration-reply-cell {
            min-width: 26rem;
        }

        .event-registration-status-select {
            min-width: 8.5rem;
        }

        .registration-email {
            overflow-wrap: anywhere;
        }

        .registration-requirements {
            border: 1px solid rgba(11, 69, 184, 0.14);
            border-radius: 0.65rem;
            background: #f7fbff;
            color: #172033;
            padding: 0.65rem;
        }

        .registration-note {
            border-left: 0.25rem solid #0b45b8;
            color: #172033;
            font-size: 0.84rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
            padding-left: 0.65rem;
        }

        .event-registration-reply-cell textarea {
            resize: vertical;
        }

        @media (max-width: 767.98px) {
            .event-registration-reply-cell {
                min-width: 0;
            }
        }
    </style>
@endpush
