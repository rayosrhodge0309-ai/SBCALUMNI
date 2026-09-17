@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'A clear view of your alumni community and the work ahead.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}?v={{ filemtime(public_path('css/dashboards.css')) }}">
@endpush

@section('content')
<div class="dashboard-page">
    <section class="dashboard-hero mb-4" aria-labelledby="office-overview-heading">
        <div class="dashboard-hero-copy">
            <span class="dashboard-eyebrow">Alumni office / Dashboard</span>
            <h1 id="office-overview-heading">Keep your community connected.</h1>
            <p>Manage alumni records, move requests forward, and share what is happening at school.</p>
            <div class="dashboard-hero-actions">
                <a href="{{ route('requests.index') }}" class="btn btn-primary">Process requests <span aria-hidden="true">&rarr;</span></a>
                <a href="{{ route('alumni.index') }}" class="btn btn-outline-primary">Browse alumni</a>
            </div>
        </div>
        <div class="dashboard-hero-aside">
            <span class="dashboard-eyebrow">Today</span>
            <time datetime="{{ now()->toDateString() }}" class="dashboard-today">{{ now()->format('F j, Y') }}</time>
            <span class="text-secondary small">{{ now()->format('l') }}</span>
        </div>
    </section>

    <div class="dashboard-metrics mb-4" aria-label="Alumni office totals">
        @foreach ([
            ['label' => 'Registered alumni', 'value' => $alumniCount, 'hint' => 'Your alumni community', 'route' => 'alumni.index', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0'],
            ['label' => 'Total requests', 'value' => $requestCount, 'hint' => 'All submitted requests', 'route' => 'requests.index', 'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h5'],
            ['label' => 'Active requests', 'value' => $pendingRequestCount, 'hint' => 'Pending or processing', 'route' => 'requests.index', 'icon' => 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0M12 6v6l4 2'],
            ['label' => 'Ready for pickup', 'value' => $readyForPickupCount, 'hint' => 'Awaiting school pickup', 'route' => 'requests.index', 'icon' => 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3'],
        ] as $metric)
            <a href="{{ route($metric['route']) }}" class="page-card dashboard-stat">
                <div class="dashboard-stat-top">
                    <span class="dashboard-stat-label">{{ $metric['label'] }}</span>
                    <span class="dashboard-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $metric['icon'] }}"/></svg></span>
                </div>
                <span class="dashboard-stat-value">{{ number_format($metric['value']) }}</span>
                <span class="dashboard-stat-hint">{{ $metric['hint'] }}</span>
            </a>
        @endforeach
    </div>

    <section class="page-card dashboard-workflow mb-4" aria-labelledby="request-lifecycle-heading">
        <div>
            <h2 id="request-lifecycle-heading" class="h6 mb-1">Request lifecycle</h2>
            <p class="small text-secondary mb-0">Process online requests through to personal pickup at school.</p>
        </div>
        <ol class="dashboard-process" aria-label="Request processing stages">
            <li><span>1</span> Submitted</li>
            <li><span>2</span> Processing</li>
            <li><span>3</span> Ready for pickup</li>
            <li><span>4</span> Claimed</li>
        </ol>
    </section>

    <div class="dashboard-content-links mb-4" aria-label="Manage school updates">
        <a href="{{ route('announcements.index') }}" class="page-card dashboard-content-link"><span><strong>Announcements</strong><span>Keep your community informed</span></span><span class="dashboard-link-total">{{ number_format($announcementCount) }} <span aria-hidden="true">&rarr;</span></span></a>
        <a href="{{ route('events.index') }}" class="page-card dashboard-content-link"><span><strong>Events</strong><span>Bring your alumni together</span></span><span class="dashboard-link-total">{{ number_format($eventCount) }} <span aria-hidden="true">&rarr;</span></span></a>
        <a href="{{ route('activities.index') }}" class="page-card dashboard-content-link"><span><strong>Activities</strong><span>Share ways to get involved</span></span><span class="dashboard-link-total">{{ number_format($activityCount) }} <span aria-hidden="true">&rarr;</span></span></a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="page-card dashboard-section h-100">
                <div class="dashboard-section-heading">
                    <h2 class="h5 mb-0">Recent Alumni</h2>
                    <a href="{{ route('alumni.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" data-mobile-card-table>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Level / Program</th>
                                <th scope="col">Year</th>
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
                                    <td colspan="3" class="text-center text-secondary"><div class="dashboard-empty"><strong>Your community starts here</strong><span>Alumni records will appear as they are added.</span></div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="page-card dashboard-section h-100">
                <div class="dashboard-section-heading">
                    <h2 class="h5 mb-0">Current Request Queue</h2>
                    <a href="{{ route('requests.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" data-mobile-card-table>
                        <thead>
                            <tr>
                                <th scope="col">Alumni</th>
                                <th scope="col">Request</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingRequests as $request)
                                <tr>
                                    <td data-label="Alumni">{{ $request->alumni?->full_name ?? 'Unknown alumni' }}</td>
                                    <td data-label="Request">{{ $request->request_type }}</td>
                                    <td data-label="Status"><span @class(['dashboard-status', 'is-ready' => $request->status === 'ready_for_pickup', 'is-pending' => $request->status === 'pending'])>{{ \App\Models\RecordRequest::workflowStatuses()[$request->status] ?? ucfirst(str_replace('_', ' ', $request->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary"><div class="dashboard-empty"><strong>No active requests</strong><span>New requests will appear here when alumni submit them.</span></div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="page-card dashboard-section">
                <div class="dashboard-section-heading">
                    <h2 class="h5 mb-0">Upcoming Events</h2>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-primary">Manage Events</a>
                </div>
                <div class="row g-3">
                    @forelse ($upcomingEvents as $event)
                        <div class="col-md-6 col-xl-4">
                            <div class="event-card p-3 h-100">
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
                                <div class="small text-secondary mb-2">{{ $event->event_date->format('F d, Y') }}</div>
                                <h3 class="h6 mb-2">{{ $event->title }}</h3>
                                <div class="text-secondary small mb-2">{{ $event->location ?: 'Location to be announced' }}</div>
                                <p class="mb-0 text-secondary">{{ \Illuminate\Support\Str::limit($event->description, 120) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="dashboard-empty"><strong>No upcoming events</strong><span>Your next community gathering will appear here once scheduled.</span></div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="page-card dashboard-section h-100">
                <div class="dashboard-section-heading">
                    <h2 class="h5 mb-0">Recent Announcements</h2>
                    <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                @forelse ($recentAnnouncements as $announcement)
                    <div class="notice-item">
                        @if ($announcement->media_url)
                            @if ($announcement->isImageMedia())
                                <img src="{{ $announcement->media_url }}" alt="{{ $announcement->title }}" class="activity-media mb-3" loading="lazy" decoding="async" width="640" height="360">
                            @elseif ($announcement->isVideoMedia())
                                <video class="activity-media activity-media-video mb-3" controls preload="none">
                                    <source src="{{ $announcement->media_url }}">
                                    Your browser does not support the video tag.
                                </video>
                            @endif
                        @endif
                        <div class="notice-label mb-2">{{ $announcement->label ?: 'Announcement' }}</div>
                        <h3 class="h6 mb-2">{{ $announcement->title }}</h3>
                        <p class="text-secondary mb-0">{{ \Illuminate\Support\Str::limit($announcement->content, 140) }}</p>
                    </div>
                @empty
                    <div class="dashboard-empty"><strong>No announcements yet</strong><span>Share school news to keep alumni up to date.</span></div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="page-card dashboard-section h-100">
                <div class="dashboard-section-heading">
                    <h2 class="h5 mb-0">Recent Activities</h2>
                    <a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                @forelse ($recentActivities as $activity)
                    <div class="event-card p-3 mb-3">
                        <div class="notice-label mb-2">{{ $activity->theme ?: 'Activity' }}</div>
                        <h3 class="h6 mb-2">{{ $activity->title }}</h3>
                        <div class="text-secondary small mb-2">
                            {{ $activity->activity_date?->format('F d, Y') ?: 'Date to be announced' }}
                            @if ($activity->location)
                                | {{ $activity->location }}
                            @endif
                        </div>
                        <p class="text-secondary mb-0">{{ \Illuminate\Support\Str::limit($activity->description, 120) }}</p>
                    </div>
                @empty
                    <div class="dashboard-empty"><strong>No activities yet</strong><span>Give your alumni a new way to take part.</span></div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
