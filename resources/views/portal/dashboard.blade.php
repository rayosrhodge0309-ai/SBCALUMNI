@extends('layouts.app')

@section('title', 'Alumni Dashboard')
@section('subtitle', 'Track requests, read school updates, and stay visible to the alumni office.')

@section('content')
    @php
        $user = auth()->user();
        $overviewTabCount = 6;
        $requestTabCount = $requestCount;
        $updateTabCount = count($announcements) + count($upcomingEvents);
        $activityTabCount = count($activities);
    @endphp

    <div class="mobile-portal-hero p-3 p-sm-4 mb-3 d-lg-none">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing: 0.08em;">Alumni Portal</div>
                <h3 class="h4 mb-1">Hi, {{ $user->name }}</h3>
                <p class="small mb-0">
                    Check request status, send a new request, and stay updated from your phone.
                </p>
            </div>
            <div class="mobile-portal-badge">{{ $alumnus->student_id_display }}</div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="{{ route('portal.requests.index') }}" class="btn btn-light">Open My Requests</a>
            <a href="{{ route('profile.edit') }}" class="btn btn-outline-light">Update Profile</a>
        </div>

        <div class="row row-cols-3 g-2 mt-3">
            <div class="col">
                <div class="mobile-portal-stat text-center">
                    <span class="mobile-portal-stat-value">{{ $requestCount }}</span>
                    <span class="mobile-portal-stat-label">Requests</span>
                </div>
            </div>
            <div class="col">
                <div class="mobile-portal-stat text-center">
                    <span class="mobile-portal-stat-value">{{ $pendingCount }}</span>
                    <span class="mobile-portal-stat-label">Pending</span>
                </div>
            </div>
            <div class="col">
                <div class="mobile-portal-stat text-center">
                    <span class="mobile-portal-stat-value">{{ $readyCount }}</span>
                    <span class="mobile-portal-stat-label">Ready</span>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-banner p-4 p-lg-5 mb-4 d-none d-lg-block">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <div class="stat-pill text-bg-light text-dark mb-3">St. Bridget College Batangas</div>
                <h3 class="h2 mb-2">Welcome back, {{ $user->name }}.</h3>
                <p class="text-secondary mb-4">
                    Your dashboard now keeps alumni announcements, upcoming events, featured activities, and your request history in a single view.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('portal.requests.index') }}" class="btn btn-success">Open My Requests</a>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-dark">Update Profile</a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="page-card p-3 p-lg-4 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="profile-avatar">
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                            @else
                                <div class="profile-avatar-placeholder">{{ $user->initials }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="small text-secondary">Linked alumni record</div>
                            <div class="fw-semibold">{{ $alumnus->student_id_display }}</div>
                            <div class="text-secondary small">{{ $alumnus->full_name }}</div>
                            <div class="text-secondary small">{{ $alumnus->academic_label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 d-none d-md-flex">
        <div class="col-md-4">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">My Total Requests</div>
                <div class="display-6 fw-semibold">{{ $requestCount }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Pending or Processing</div>
                <div class="display-6 fw-semibold">{{ $pendingCount }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Ready for Pickup</div>
                <div class="display-6 fw-semibold">{{ $readyCount }}</div>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-4 d-md-none">
        <div class="col-4">
            <div class="page-card p-3 h-100 text-center">
                <div class="text-secondary small mb-1">Requests</div>
                <div class="h3 mb-0 fw-semibold">{{ $requestCount }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="page-card p-3 h-100 text-center">
                <div class="text-secondary small mb-1">Pending</div>
                <div class="h3 mb-0 fw-semibold">{{ $pendingCount }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="page-card p-3 h-100 text-center">
                <div class="text-secondary small mb-1">Ready</div>
                <div class="h3 mb-0 fw-semibold">{{ $readyCount }}</div>
            </div>
        </div>
    </div>

    <div class="dashboard-tabs-shell page-card p-2 p-md-3 mb-4">
        <div class="dashboard-tabs-header d-flex align-items-center justify-content-between gap-2 mb-2">
            <div>
                <div class="small text-secondary text-uppercase fw-semibold">Student Hub</div>
                <div class="fw-semibold">Switch sections like an app</div>
            </div>
            <span class="dashboard-tabs-note text-secondary small d-none d-md-inline">Swipe tabs on mobile</span>
        </div>

        <div class="dashboard-tabstrip">
            <ul class="nav nav-pills dashboard-tablist gap-2" id="portalDashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="portal-overview-tab" data-bs-toggle="pill" data-bs-target="#portal-overview" type="button" role="tab" aria-controls="portal-overview" aria-selected="true">
                        <span class="dashboard-tab-icon">O</span>
                        <span class="dashboard-tab-label">Overview</span>
                        <span class="dashboard-tab-count">{{ $overviewTabCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="portal-requests-tab" data-bs-toggle="pill" data-bs-target="#portal-requests" type="button" role="tab" aria-controls="portal-requests" aria-selected="false">
                        <span class="dashboard-tab-icon">R</span>
                        <span class="dashboard-tab-label">Requests</span>
                        <span class="dashboard-tab-count">{{ $requestTabCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="portal-updates-tab" data-bs-toggle="pill" data-bs-target="#portal-updates" type="button" role="tab" aria-controls="portal-updates" aria-selected="false">
                        <span class="dashboard-tab-icon">U</span>
                        <span class="dashboard-tab-label">Updates</span>
                        <span class="dashboard-tab-count">{{ $updateTabCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="portal-activities-tab" data-bs-toggle="pill" data-bs-target="#portal-activities" type="button" role="tab" aria-controls="portal-activities" aria-selected="false">
                        <span class="dashboard-tab-icon">A</span>
                        <span class="dashboard-tab-label">Activities</span>
                        <span class="dashboard-tab-count">{{ $activityTabCount }}</span>
                    </button>
                </li>
            </ul>
            <div class="dashboard-tab-indicator" aria-hidden="true">
                <span class="dashboard-tab-indicator-bar"></span>
            </div>
        </div>

        <div class="tab-content pt-3 pt-md-4">
            <div class="tab-pane fade show active" id="portal-overview" role="tabpanel" aria-labelledby="portal-overview-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="page-card p-4 h-100">
                            <h3 class="h5 mb-3">My Alumni Record</h3>
                            <dl class="row mb-0">
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
                        <div class="page-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h5 mb-0">Quick Actions</h3>
                                <span class="text-secondary small">Fast access for students</span>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="mobile-portal-stat text-center h-100">
                                        <span class="mobile-portal-stat-value">{{ $requestCount }}</span>
                                        <span class="mobile-portal-stat-label">Requests</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mobile-portal-stat text-center h-100">
                                        <span class="mobile-portal-stat-value">{{ $pendingCount }}</span>
                                        <span class="mobile-portal-stat-label">Pending</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mobile-portal-stat text-center h-100">
                                        <span class="mobile-portal-stat-value">{{ $readyCount }}</span>
                                        <span class="mobile-portal-stat-label">Ready</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-sm-flex">
                                <a href="{{ route('portal.requests.index') }}" class="btn btn-success">Open Requests</a>
                                <a href="{{ route('profile.edit') }}" class="btn btn-outline-dark">Update Profile</a>
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
                                    <div>
                                        <div class="small text-secondary mb-1">Connected account</div>
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <div class="text-secondary small">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="portal-requests" role="tabpanel" aria-labelledby="portal-requests-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-xl-4">
                        <div class="page-card p-4 h-100">
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
                                <div class="mb-3">
                                    <label class="form-label" for="year_requested">Graduation / Record Year</label>
                                    <input id="year_requested" type="number" min="1900" max="{{ now()->year + 1 }}" name="year_requested" class="form-control" value="{{ old('year_requested', $alumnus->year_graduated) }}" required>
                                </div>
                                <div class="small text-secondary mb-3">
                                    Submit from your phone and follow the status in the request history below.
                                </div>
                                <button class="btn btn-success w-100" type="submit">Submit Request</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="page-card p-0 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" data-mobile-card-table>
                                    <thead class="table-light">
                                        <tr>
                                            <th>Request</th>
                                            <th>Year</th>
                                            <th>Status</th>
                                            <th>Admin Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentRequests as $request)
                                            <tr>
                                                <td data-label="Request">{{ $request->request_type }}</td>
                                                <td data-label="Year">{{ $request->year_requested }}</td>
                                                <td class="fw-semibold" data-label="Status">{{ $statusOptions[$request->status] ?? ucfirst(str_replace('_', ' ', $request->status)) }}</td>
                                                <td data-label="Admin Notes">{{ $request->admin_notes ?: 'No notes from the school yet.' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-secondary py-5">You have not submitted any requests yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="portal-updates" role="tabpanel" aria-labelledby="portal-updates-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="page-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h5 mb-0">Announcements</h3>
                                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-dark">Profile Settings</a>
                            </div>

                            @forelse ($announcements as $announcement)
                                <div class="notice-item">
                                    @if (! empty($announcement['media_url']))
                                        @if (($announcement['media_type'] ?? null) === 'image')
                                            <img src="{{ $announcement['media_url'] }}" alt="{{ $announcement['title'] }}" class="activity-media mb-3">
                                        @elseif (($announcement['media_type'] ?? null) === 'video')
                                            <video class="activity-media activity-media-video mb-3" controls preload="metadata">
                                                <source src="{{ $announcement['media_url'] }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @endif
                                    <div class="notice-label mb-2">{{ $announcement['label'] }}</div>
                                    <h4 class="h6 mb-2">{{ $announcement['title'] }}</h4>
                                    <p class="text-secondary mb-0">{{ $announcement['description'] }}</p>
                                </div>
                            @empty
                                <div class="event-card p-4 text-secondary">
                                    No announcements have been posted by the administrator yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="page-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h5 mb-0">Upcoming Events</h3>
                                <span class="text-secondary small">Visible on the landing page too</span>
                            </div>

                            @forelse ($upcomingEvents as $event)
                                <div class="event-card p-3 mb-3">
                                    @if ($event->media_url)
                                        @if ($event->isImageMedia())
                                            <img src="{{ $event->media_url }}" alt="{{ $event->title }}" class="activity-media mb-3">
                                        @elseif ($event->isVideoMedia())
                                            <video class="activity-media activity-media-video mb-3" controls preload="metadata">
                                                <source src="{{ $event->media_url }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @endif
                                    <div class="notice-label mb-2">{{ $event->event_date->format('F d, Y') }}</div>
                                    <h4 class="h6 mb-2">{{ $event->title }}</h4>
                                    <div class="text-secondary small mb-2">{{ $event->location ?: 'Location to be announced' }}</div>
                                    <p class="text-secondary mb-0">{{ \Illuminate\Support\Str::limit($event->description, 120) }}</p>
                                </div>
                            @empty
                                <div class="event-card p-4 text-secondary">
                                    No upcoming events have been posted by the administrator yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="portal-activities" role="tabpanel" aria-labelledby="portal-activities-tab" tabindex="0">
                <div class="page-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">Featured Activities</h3>
                        <span class="text-secondary small">Suggested alumni engagement areas</span>
                    </div>

                    <div class="row g-3">
                        @forelse ($activities as $activity)
                            <div class="col-md-6 col-xl-4">
                                <div class="activity-card p-4 h-100">
                                    @if (! empty($activity['media_url']))
                                        @if (($activity['media_type'] ?? null) === 'image')
                                            <img src="{{ $activity['media_url'] }}" alt="{{ $activity['title'] }}" class="activity-media mb-3">
                                        @elseif (($activity['media_type'] ?? null) === 'video')
                                            <video class="activity-media activity-media-video mb-3" controls preload="metadata">
                                                <source src="{{ $activity['media_url'] }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @endif
                                    <div class="notice-label mb-2">{{ $activity['theme'] }}</div>
                                    <h4 class="h6 mb-2">{{ $activity['title'] }}</h4>
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
                                <div class="event-card p-4 text-secondary">
                                    No featured activities have been posted by the administrator yet.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-tabs-shell {
            border-radius: 1.35rem;
        }

        .dashboard-tabstrip {
            position: relative;
            padding-bottom: 0.85rem;
        }

        .dashboard-tabs-header {
            padding-inline: 0.35rem;
        }

        .dashboard-tabs-note {
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .dashboard-tablist {
            overflow-x: auto;
            flex-wrap: nowrap;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }

        .dashboard-tablist::-webkit-scrollbar {
            display: none;
        }

        .dashboard-tablist .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            white-space: nowrap;
            border-radius: 999px;
            border: 1px solid rgba(4, 0, 120, 0.12);
            color: var(--muted);
            background: rgba(255, 255, 255, 0.7);
            padding: 0.72rem 1rem;
            min-height: 2.85rem;
            box-shadow: 0 10px 16px rgba(4, 0, 120, 0.06);
            position: relative;
        }

        .dashboard-tab-icon {
            width: 1.7rem;
            height: 1.7rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(246, 211, 29, 0.18);
            color: var(--wine-deep);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            flex-shrink: 0;
        }

        .dashboard-tab-label {
            font-weight: 700;
        }

        .dashboard-tab-count {
            min-width: 1.55rem;
            height: 1.55rem;
            padding-inline: 0.35rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(246, 211, 29, 0.18);
            color: var(--wine-deep);
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1;
            flex-shrink: 0;
            margin-left: 0.05rem;
        }

        .dashboard-tablist .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            border-color: transparent;
            box-shadow:
                0 16px 28px rgba(4, 0, 120, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.14);
            transform: translateY(-1px);
        }

        .dashboard-tablist .nav-link.active::after {
            content: "";
            position: absolute;
            left: 12%;
            right: 12%;
            bottom: -0.45rem;
            height: 0.2rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
        }

        .dashboard-tablist .nav-link.active .dashboard-tab-icon {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .dashboard-tablist .nav-link.active .dashboard-tab-count {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        .dashboard-tab-indicator {
            position: absolute;
            left: 0;
            bottom: 0.1rem;
            height: 0.22rem;
            width: 0;
            opacity: 0;
            pointer-events: none;
            transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1), width 240ms cubic-bezier(0.22, 1, 0.36, 1), opacity 160ms ease;
        }

        .dashboard-tab-indicator-bar {
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 6px 14px rgba(4, 0, 120, 0.22);
        }

        .profile-avatar-sm {
            width: 4.35rem;
            height: 4.35rem;
            border-radius: 1.1rem;
        }

        .profile-avatar-sm .profile-avatar-placeholder {
            font-size: 1.05rem;
        }

        @media (max-width: 767.98px) {
            .dashboard-tabs-shell {
                padding: 0.65rem !important;
                border-radius: 1rem;
                position: sticky;
                top: 4rem;
                z-index: 1032;
                background: rgba(255, 255, 255, 0.94);
                box-shadow: 0 14px 26px rgba(4, 0, 120, 0.08);
            }

            .dashboard-tabs-header {
                padding-inline: 0.1rem;
                margin-bottom: 0.6rem !important;
            }

            .dashboard-tabstrip {
                padding-bottom: 0.9rem;
            }

            .dashboard-tablist .nav-link {
                padding-inline: 0.8rem;
                padding-block: 0.6rem;
                font-size: 0.84rem;
            }

            .dashboard-tablist .nav-link.active {
                box-shadow:
                    0 18px 26px rgba(4, 0, 120, 0.22),
                    inset 0 1px 0 rgba(255, 255, 255, 0.14);
            }

            .dashboard-tab-icon {
                width: 1.55rem;
                height: 1.55rem;
                font-size: 0.72rem;
            }

            .dashboard-tab-label {
                letter-spacing: 0.01em;
            }

            .dashboard-tab-count {
                min-width: 1.45rem;
                height: 1.45rem;
                font-size: 0.68rem;
            }

            .dashboard-tab-indicator {
                bottom: 0.05rem;
                height: 0.2rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const tabStrip = document.querySelector('.dashboard-tabstrip');
            const tabList = document.getElementById('portalDashboardTabs');
            const indicator = document.querySelector('.dashboard-tab-indicator');

            if (!tabStrip || !tabList || !indicator) {
                return;
            }

            const setIndicator = (tabButton, immediate = false) => {
                if (!tabButton) {
                    indicator.style.opacity = '0';
                    return;
                }

                const left = tabButton.offsetLeft - tabList.scrollLeft;
                const width = tabButton.offsetWidth;

                indicator.style.opacity = '1';
                indicator.style.width = `${width}px`;
                indicator.style.transform = `translateX(${left}px)`;

                if (immediate) {
                    indicator.style.transitionDuration = '0ms';
                    requestAnimationFrame(() => {
                        indicator.style.transitionDuration = '';
                    });
                }
            };

            const activeTab = () => tabList.querySelector('.nav-link.active');

            setIndicator(activeTab(), true);

            tabList.addEventListener('shown.bs.tab', (event) => {
                setIndicator(event.target);
            });

            tabList.addEventListener('scroll', () => {
                setIndicator(activeTab());
            }, { passive: true });

            window.addEventListener('resize', () => {
                setIndicator(activeTab(), true);
            }, { passive: true });
        })();
    </script>
@endpush
