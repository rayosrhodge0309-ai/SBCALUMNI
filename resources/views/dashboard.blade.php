@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Monitor digital requests, alumni records, and the school pickup workflow.')

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Registered Alumni</div>
                <div class="display-6 fw-semibold">{{ $alumniCount }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Total Requests</div>
                <div class="display-6 fw-semibold">{{ $requestCount }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Active Requests</div>
                <div class="display-6 fw-semibold">{{ $pendingRequestCount }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Ready for Pickup</div>
                <div class="display-6 fw-semibold">{{ $readyForPickupCount }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Announcements Posted</div>
                <div class="display-6 fw-semibold">{{ $announcementCount }}</div>
                <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-primary mt-3">Manage Announcements</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="page-card p-4 h-100">
                <div class="text-secondary small mb-2">Activities Posted</div>
                <div class="display-6 fw-semibold">{{ $activityCount }}</div>
                <a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-primary mt-3">Manage Activities</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="page-card p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                    <div>
                        <h3 class="h5 mb-2">Request lifecycle</h3>
                        <p class="text-secondary mb-0">Alumni request records online. Admins process, mark them ready, and the alumni personally retrieve them from the school.</p>
                    </div>
                    <a href="{{ route('requests.index') }}" class="btn btn-primary">Open Request Processing</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="page-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Recent Alumni</h3>
                    <a href="{{ route('alumni.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" data-mobile-card-table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Level / Program</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAlumni as $alumnus)
                                <tr>
                                    <td data-label="Name">{{ $alumnus->full_name }}</td>
                                    <td data-label="Level / Program">{{ $alumnus->academic_label }}</td>
                                    <td data-label="Year">{{ $alumnus->year_label !== '' ? $alumnus->year_label : 'Not specified' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary">No alumni records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="page-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Current Request Queue</h3>
                    <a href="{{ route('requests.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" data-mobile-card-table>
                        <thead>
                            <tr>
                                <th>Alumni</th>
                                <th>Request</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingRequests as $request)
                                <tr>
                                    <td data-label="Alumni">{{ $request->alumni?->full_name ?? 'Unknown alumni' }}</td>
                                    <td data-label="Request">{{ $request->request_type }}</td>
                                    <td data-label="Status">{{ \App\Models\RecordRequest::workflowStatuses()[$request->status] ?? ucfirst(str_replace('_', ' ', $request->status)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary">No request activity right now.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="page-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Upcoming Events</h3>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-primary">Manage Events</a>
                </div>
                <div class="row g-3">
                    @forelse ($upcomingEvents as $event)
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-4 p-3 h-100">
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
                                <div class="small text-secondary mb-2">{{ $event->event_date->format('F d, Y') }}</div>
                                <h4 class="h6 mb-2">{{ $event->title }}</h4>
                                <div class="text-secondary small mb-2">{{ $event->location ?: 'Location to be announced' }}</div>
                                <p class="mb-0 text-secondary">{{ \Illuminate\Support\Str::limit($event->description, 120) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="border rounded-4 p-4 text-center text-secondary">No upcoming events scheduled.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="page-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Recent Announcements</h3>
                    <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                @forelse ($recentAnnouncements as $announcement)
                    <div class="notice-item">
                        @if ($announcement->media_url)
                            @if ($announcement->isImageMedia())
                                <img src="{{ $announcement->media_url }}" alt="{{ $announcement->title }}" class="activity-media mb-3">
                            @elseif ($announcement->isVideoMedia())
                                <video class="activity-media activity-media-video mb-3" controls preload="metadata">
                                    <source src="{{ $announcement->media_url }}">
                                    Your browser does not support the video tag.
                                </video>
                            @endif
                        @endif
                        <div class="notice-label mb-2">{{ $announcement->label ?: 'Announcement' }}</div>
                        <h4 class="h6 mb-2">{{ $announcement->title }}</h4>
                        <p class="text-secondary mb-0">{{ \Illuminate\Support\Str::limit($announcement->content, 140) }}</p>
                    </div>
                @empty
                    <div class="text-secondary">No announcements posted yet.</div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="page-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Recent Activities</h3>
                    <a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                @forelse ($recentActivities as $activity)
                    <div class="event-card p-3 mb-3">
                        <div class="notice-label mb-2">{{ $activity->theme ?: 'Activity' }}</div>
                        <h4 class="h6 mb-2">{{ $activity->title }}</h4>
                        <div class="text-secondary small mb-2">
                            {{ $activity->activity_date?->format('F d, Y') ?: 'Date to be announced' }}
                            @if ($activity->location)
                                | {{ $activity->location }}
                            @endif
                        </div>
                        <p class="text-secondary mb-0">{{ \Illuminate\Support\Str::limit($activity->description, 120) }}</p>
                    </div>
                @empty
                    <div class="text-secondary">No activities posted yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
