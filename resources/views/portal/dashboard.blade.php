@extends('layouts.app')

@section('title', 'Alumni Dashboard')
@section('subtitle', 'Your requests, school updates, and alumni community in one place.')

@section('content')
    @php
        $user = auth()->user();
        $requestTabCount = $requestCount;
        $updateTabCount = count($announcements) + count($upcomingEvents);
        $activityTabCount = count($activities);
        $eventRegistrationsByEventId = $eventRegistrationsByEventId ?? collect();
        $eventRegistrationStatuses = $eventRegistrationStatuses ?? [];
        $activeDashboardTab = old('event_registration_event_id') ? 'updates' : (session()->hasOldInput('request_type') ? 'requests' : 'overview');
    @endphp

    <div class="dashboard-page">
    <section class="dashboard-hero dashboard-hero-portal mb-4" aria-labelledby="portal-welcome-heading">
        <div class="dashboard-hero-copy">
            <span class="dashboard-eyebrow">St. Bridget College Batangas / Alumni portal</span>
            <h1 id="portal-welcome-heading">Welcome back, {{ $user->name }}.</h1>
            <p>Stay connected to your school. Follow your requests, find upcoming events, and keep your alumni record up to date.</p>
            <div class="dashboard-hero-actions">
                <a href="{{ route('portal.requests.index') }}" class="btn btn-primary">Open My Requests <span aria-hidden="true">&rarr;</span></a>
                <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">Update Profile</a>
                <button type="button" class="btn btn-outline-primary" data-enable-notifications>Enable Notifications</button>
            </div>
        </div>
        <div class="dashboard-member-card">
            <div class="profile-avatar">
                @if ($user->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" width="64" height="64" decoding="async">
                @else
                    <div class="profile-avatar-placeholder">{{ $user->initials }}</div>
                @endif
            </div>
            <div class="dashboard-member-copy">
                <span class="dashboard-eyebrow">Linked alumni record</span>
                <strong>{{ $alumnus->student_id_display }}</strong>
                <span>{{ $alumnus->full_name }}</span>
                <span class="small">{{ $alumnus->academic_label }}</span>
            </div>
        </div>
    </section>

    <div class="dashboard-metrics dashboard-metrics-three mb-4" aria-label="Your request totals">
        <div class="page-card dashboard-stat">
            <span class="dashboard-stat-label">My total requests</span>
            <span class="dashboard-stat-value">{{ number_format($requestCount) }}</span>
            <span class="dashboard-stat-hint">All your submissions</span>
        </div>
        <div class="page-card dashboard-stat">
            <span class="dashboard-stat-label">In progress</span>
            <span class="dashboard-stat-value">{{ number_format($pendingCount) }}</span>
            <span class="dashboard-stat-hint">Pending or processing</span>
        </div>
        <div class="page-card dashboard-stat dashboard-stat-ready">
            <span class="dashboard-stat-label">Ready for pickup</span>
            <span class="dashboard-stat-value">{{ number_format($readyCount) }}</span>
            <span class="dashboard-stat-hint">Collect at school</span>
        </div>
    </div>

    @if ($readyCount > 0)
        <div class="dashboard-pickup-callout mb-4">
            <div><strong>{{ $readyCount }} {{ $readyCount === 1 ? 'request is' : 'requests are' }} ready for pickup</strong><p class="small mb-0">Check your request details and the school's notes before visiting.</p></div>
            <a href="{{ route('portal.requests.index') }}" class="btn btn-sm btn-outline-primary">View pickup details</a>
        </div>
    @endif

    @if ($requestUpdates->isNotEmpty())
        <div class="page-card p-3 p-md-4 mb-4 request-update-notifications">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <div class="small text-secondary text-uppercase fw-semibold">Request Notifications</div>
                    <h2 class="h5 mb-0">Admin updates about your requests</h2>
                </div>
                <a href="{{ route('portal.requests.index') }}" class="btn btn-sm btn-outline-dark align-self-start align-self-md-center">View All Requests</a>
            </div>

            <div class="d-grid gap-2">
                @foreach ($requestUpdates as $requestUpdate)
                    <div class="request-update-alert">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold">{{ $requestUpdate->request_type }}</div>
                                <div class="small text-secondary">
                                    {{ $statusOptions[$requestUpdate->status] ?? ucfirst(str_replace('_', ' ', $requestUpdate->status)) }}
                                    for {{ $requestUpdate->year_requested }}
                                </div>
                            </div>
                            <div class="small text-secondary">
                                {{ $requestUpdate->admin_replied_at?->format('M d, Y h:i A') }}
                            </div>
                        </div>

                        @if ($requestUpdate->admin_notes)
                            <div class="request-update-note mt-2">{{ $requestUpdate->admin_notes }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="dashboard-tabs-shell mb-4">
        <div class="dashboard-tabstrip">
            <ul class="nav nav-pills dashboard-tablist gap-2" id="portalDashboardTabs" role="tablist" aria-label="Dashboard sections">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeDashboardTab === 'overview' ? 'active' : '' }}" id="portal-overview-tab" data-bs-toggle="pill" data-bs-target="#portal-overview" type="button" role="tab" aria-controls="portal-overview" aria-selected="{{ $activeDashboardTab === 'overview' ? 'true' : 'false' }}">
                        <span class="dashboard-tab-label">Overview</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeDashboardTab === 'requests' ? 'active' : '' }}" id="portal-requests-tab" data-bs-toggle="pill" data-bs-target="#portal-requests" type="button" role="tab" aria-controls="portal-requests" aria-selected="{{ $activeDashboardTab === 'requests' ? 'true' : 'false' }}">
                        <span class="dashboard-tab-label">Requests</span>
                        <span class="dashboard-tab-count">{{ $requestTabCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeDashboardTab === 'updates' ? 'active' : '' }}" id="portal-updates-tab" data-bs-toggle="pill" data-bs-target="#portal-updates" type="button" role="tab" aria-controls="portal-updates" aria-selected="{{ $activeDashboardTab === 'updates' ? 'true' : 'false' }}">
                        <span class="dashboard-tab-label">Updates</span>
                        <span class="dashboard-tab-count">{{ $updateTabCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeDashboardTab === 'activities' ? 'active' : '' }}" id="portal-activities-tab" data-bs-toggle="pill" data-bs-target="#portal-activities" type="button" role="tab" aria-controls="portal-activities" aria-selected="{{ $activeDashboardTab === 'activities' ? 'true' : 'false' }}">
                        <span class="dashboard-tab-label">Activities</span>
                        <span class="dashboard-tab-count">{{ $activityTabCount }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content pt-3 pt-md-4">
            <div class="tab-pane fade {{ $activeDashboardTab === 'overview' ? 'show active' : '' }}" id="portal-overview" role="tabpanel" aria-labelledby="portal-overview-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="page-card dashboard-section h-100">
                            <h2 class="h5 mb-3">My Alumni Record</h2>
                            <dl class="row mb-0 dashboard-record-details">
                                <dt class="col-5">Student ID</dt>
                                <dd class="col-7">{{ $alumnus->student_id_display }}</dd>

                                <dt class="col-5">Name</dt>
                                <dd class="col-7">{{ $alumnus->full_name }}</dd>

                                <dt class="col-5">Birthday</dt>
                                <dd class="col-7">{{ $alumnus->birthday?->format('F j, Y') ?? 'Not specified' }}</dd>

                                <dt class="col-5">Contact Number</dt>
                                <dd class="col-7">{{ $alumnus->contact_number ?? 'Not specified' }}</dd>

                                <dt class="col-5">Address</dt>
                                <dd class="col-7">{{ $alumnus->address ?? 'Not specified' }}</dd>

                                <dt class="col-5">School Level</dt>
                                <dd class="col-7">{{ $alumnus->education_level }}</dd>

                                <dt class="col-5">Program / Grade</dt>
                                <dd class="col-7">{{ $alumnus->course }}</dd>

                                <dt class="col-5">Batch Year</dt>
                                <dd class="col-7">{{ $alumnus->year_label !== '' ? $alumnus->year_label : 'Not specified' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="page-card dashboard-section h-100">
                            <h2 class="h5 mb-3">Quick Actions</h2>
                            <div class="dashboard-action-list">
                                <a href="{{ route('portal.requests.index') }}" class="dashboard-action-link">
                                    <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h5"/></svg></span>
                                    <span><strong>Request a school record</strong><span>Submit a request or follow its progress.</span></span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                                <a href="{{ route('profile.edit') }}" class="dashboard-action-link">
                                    <span class="dashboard-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a7 7 0 0 0-14 0v2M17 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0"/></svg></span>
                                    <span><strong>Keep your details current</strong><span>Update your profile and contact information.</span></span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                            <div class="dashboard-help-note mt-3">
                                <strong class="small">Online requests, personal pickup</strong>
                                <p class="small text-secondary mb-0">The alumni office will update your request when it is ready to collect at school.</p>
                            </div>

                            <div class="page-card mt-4 p-3 bg-body-tertiary border-0">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="profile-avatar profile-avatar-sm">
                                        @if ($user->profile_photo_url)
                                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                        @else
                                            <div class="profile-avatar-placeholder">{{ $user->initials }}</div>
                                        @endif
                                    </div>
                                    <div class="connected-account-copy">
                                        <div class="small text-secondary mb-1">Connected account</div>
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <div class="text-secondary small connected-account-email">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeDashboardTab === 'requests' ? 'show active' : '' }}" id="portal-requests" role="tabpanel" aria-labelledby="portal-requests-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-xl-4">
                        <div class="page-card dashboard-section h-100">
                            <h2 class="h5 mb-3">Submit New Request</h2>
                            <form method="POST" action="{{ route('portal.requests.store') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="linked_alumni_record">Linked Alumni Record</label>
                                    <input id="linked_alumni_record" type="text" class="form-control" value="{{ $alumnus->full_name }} - {{ $alumnus->student_id_display }}" disabled>
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
                                                <label class="form-label" for="requester_year_graduate">Graduation year</label>
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
                                    Track the status and any school replies in your request history.
                                </div>
                                <button class="btn btn-success w-100" type="submit">Submit Request</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="page-card overflow-hidden">
                            <div class="dashboard-section-heading dashboard-table-heading"><div><h2 class="h5 mb-1">Recent requests</h2><p class="small text-secondary mb-0">Your latest submissions and replies from the school.</p></div><a href="{{ route('portal.requests.index') }}" class="btn btn-sm btn-outline-primary">View all</a></div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" data-mobile-card-table>
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Request</th>
                                            <th scope="col">Year</th>
                                            <th scope="col">Message</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Admin Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentRequests as $request)
                                            <tr>
                                                <td data-label="Request">{{ $request->request_type }}</td>
                                                <td data-label="Year">{{ $request->year_requested }}</td>
                                                <td data-label="Message">{!! $request->requester_note ? nl2br(e($request->requester_note)) : '-' !!}</td>
                                                <td data-label="Status"><span @class(['dashboard-status', 'is-ready' => in_array($request->status, ['ready_for_pickup', 'completed']), 'is-pending' => $request->status === 'pending', 'is-rejected' => $request->status === 'rejected'])>{{ $statusOptions[$request->status] ?? ucfirst(str_replace('_', ' ', $request->status)) }}</span></td>
                                                <td data-label="Admin Notes">{{ $request->admin_notes ?: 'No notes from the school yet.' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-secondary"><div class="dashboard-empty"><strong>No requests yet</strong><span>Use the form to send your first request to the alumni office.</span></div></td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeDashboardTab === 'updates' ? 'show active' : '' }}" id="portal-updates" role="tabpanel" aria-labelledby="portal-updates-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="page-card dashboard-section h-100">
                            <div class="dashboard-section-heading">
                                <h2 class="h5 mb-0">Announcements</h2>
                                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-dark">Profile Settings</a>
                            </div>

                            @forelse ($announcements as $announcement)
                                <div class="notice-item">
                                    @if (! empty($announcement['media_url']))
                                        @if (($announcement['media_type'] ?? null) === 'image')
                                            <img src="{{ $announcement['media_url'] }}" alt="{{ $announcement['title'] }}" class="activity-media mb-3" loading="lazy" decoding="async" width="640" height="360">
                                        @elseif (($announcement['media_type'] ?? null) === 'video')
                                            <video class="activity-media activity-media-video mb-3" controls preload="none">
                                                <source src="{{ $announcement['media_url'] }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @endif
                                    <div class="notice-label mb-2">{{ $announcement['label'] }}</div>
                                    <h3 class="h6 mb-2">{{ $announcement['title'] }}</h3>
                                    <p class="text-secondary mb-0">{{ $announcement['description'] }}</p>
                                </div>
                            @empty
                                <div class="dashboard-empty">
                                    <strong>No announcements yet</strong><span>School news and updates will appear here.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="page-card dashboard-section h-100">
                            <div class="dashboard-section-heading">
                                <h2 class="h5 mb-0">Upcoming Events</h2>
                                <span class="text-secondary small">Connect with your community</span>
                            </div>

                            @forelse ($upcomingEvents as $event)
                                @php
                                    $eventRegistration = $eventRegistrationsByEventId->get($event->id);
                                    $eventRegistrationStatus = $eventRegistration
                                        ? ($eventRegistrationStatuses[$eventRegistration->status] ?? $eventRegistration->status_label)
                                        : null;
                                @endphp
                                <div class="event-card p-3 mb-3">
                                    @if ($event->media_url)
                                        @if ($event->isImageMedia())
                                            <img src="{{ $event->media_url }}" alt="{{ $event->title }}" class="activity-media mb-3" loading="lazy" decoding="async" width="640" height="360">
                                        @elseif ($event->isVideoMedia())
                                            <video class="activity-media activity-media-video mb-3" controls preload="none">
                                                <source src="{{ $event->media_url }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @endif
                                    <div class="notice-label mb-2">{{ $event->event_date->format('F d, Y') }}</div>
                                    <h3 class="h6 mb-2">{{ $event->title }}</h3>
                                    <div class="text-secondary small mb-2">{{ $event->location ?: 'Location to be announced' }}</div>
                                    <p class="text-secondary mb-0">{{ \Illuminate\Support\Str::limit($event->description, 120) }}</p>

                                    @if ($eventRegistration)
                                        <div class="event-registration-box mt-3">
                                            <div class="d-flex flex-column flex-sm-row justify-content-between gap-1">
                                                <div class="fw-semibold">Registration: {{ $eventRegistrationStatus }}</div>
                                                <div class="small text-secondary">
                                                    {{ $eventRegistration->registered_at?->format('M d, Y h:i A') }}
                                                </div>
                                            </div>

                                            @if ($eventRegistration->admin_reply)
                                                <div class="event-registration-reply mt-2">{{ $eventRegistration->admin_reply }}</div>
                                            @else
                                                <div class="small text-secondary mt-1">Waiting for admin reply.</div>
                                            @endif
                                        </div>
                                    @else
                                        @php
                                            $registrationFormExpanded = (string) old('event_registration_event_id') === (string) $event->id;
                                            $registrationFieldsId = 'portal_event_registration_'.$event->id;
                                        @endphp
                                        <form method="POST" action="{{ route('portal.events.registrations.store', $event) }}" class="event-registration-actions mt-3" data-event-registration-form>
                                            @csrf
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success w-100"
                                                aria-expanded="{{ $registrationFormExpanded ? 'true' : 'false' }}"
                                                aria-controls="{{ $registrationFieldsId }}"
                                                data-event-registration-toggle>
                                                Register to Event
                                            </button>
                                            @include('events._registration_requirements', [
                                                'registrationFieldId' => $registrationFieldsId,
                                                'registrationEventId' => $event->id,
                                                'registrationAlumnus' => $alumnus,
                                                'registrationUser' => $user,
                                                'registrationExpanded' => $registrationFormExpanded,
                                                'registrationHasErrors' => $registrationFormExpanded,
                                                'registrationSubmitClass' => 'btn btn-success',
                                            ])
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="dashboard-empty">
                                    <strong>No upcoming events</strong><span>Check back for your next opportunity to reconnect.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeDashboardTab === 'activities' ? 'show active' : '' }}" id="portal-activities" role="tabpanel" aria-labelledby="portal-activities-tab" tabindex="0">
                <div class="page-card dashboard-section">
                    <div class="dashboard-section-heading">
                        <h2 class="h5 mb-0">Featured Activities</h2>
                        <span class="text-secondary small">Ways to stay involved</span>
                    </div>

                    <div class="row g-3">
                        @forelse ($activities as $activity)
                            <div class="col-md-6 col-xl-4">
                                <div class="activity-card p-4 h-100">
                                    @if (! empty($activity['media_url']))
                                        @if (($activity['media_type'] ?? null) === 'image')
                                            <img src="{{ $activity['media_url'] }}" alt="{{ $activity['title'] }}" class="activity-media mb-3" loading="lazy" decoding="async" width="640" height="360">
                                        @elseif (($activity['media_type'] ?? null) === 'video')
                                            <video class="activity-media activity-media-video mb-3" controls preload="none">
                                                <source src="{{ $activity['media_url'] }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @endif
                                    <div class="notice-label mb-2">{{ $activity['theme'] }}</div>
                                    <h3 class="h6 mb-2">{{ $activity['title'] }}</h3>
                                    @if (! empty($activity['activity_date']) || ! empty($activity['location']))
                                        <div class="text-secondary small mb-2">
                                            @if (! empty($activity['activity_date']))
                                                {{ \Illuminate\Support\Carbon::parse($activity['activity_date'])->format('F d, Y') }}
                                            @endif
                                            @if (! empty($activity['location']))
                                                @if (! empty($activity['activity_date']))
                                                    |
                                                @endif
                                                {{ $activity['location'] }}
                                            @endif
                                        </div>
                                    @endif
                                    <p class="text-secondary mb-0">{{ $activity['description'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="dashboard-empty">
                                    <strong>No activities yet</strong><span>New ways to get involved will appear here.</span>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}?v={{ filemtime(public_path('css/dashboards.css')) }}">
@endpush

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-event-registration-form]').forEach((form) => {
                const toggle = form.querySelector('[data-event-registration-toggle]');
                const fields = form.querySelector('[data-event-registration-fields]');
                const inputs = form.querySelectorAll('[data-event-registration-input]');
                const cancel = form.querySelector('[data-event-registration-cancel]');

                if (!toggle || !fields) {
                    return;
                }

                const setOpen = (isOpen) => {
                    fields.classList.toggle('d-none', !isOpen);
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                    inputs.forEach((input) => {
                        input.disabled = !isOpen;
                    });
                };

                toggle.addEventListener('click', () => {
                    const isOpen = fields.classList.contains('d-none');
                    setOpen(isOpen);
                    if (isOpen) {
                        fields.querySelector('[data-event-registration-input]:not([type="hidden"]):not(:disabled)')?.focus();
                    }
                });

                if (cancel) {
                    cancel.addEventListener('click', () => {
                        setOpen(false);
                        toggle.focus();
                    });
                }

                setOpen(!fields.classList.contains('d-none'));
            });
        })();

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

        // Keep media silent when its section is no longer visible.
        document.getElementById('portalDashboardTabs')?.addEventListener('hidden.bs.tab', (event) => {
            const panel = document.querySelector(event.target.getAttribute('data-bs-target'));
            panel?.querySelectorAll('video').forEach((video) => video.pause());
        });
    </script>
@endpush
