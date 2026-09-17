@extends('layouts.app')

@php
    $isAdminPreview = auth()->check() && auth()->user()?->isAdmin() && request()->boolean('preview');
@endphp

@section('title', 'St. Bridget College Batangas Alumni Link')
@if ($isAdminPreview)
    @section('workspace_preview', true)
@else
    @section('full_guest', true)
@endif

@section('content')
    @php
        $content = is_array($content ?? null) ? $content : config('portal_content', []);
        $brand = is_array($content['brand'] ?? null) ? $content['brand'] : [];
        $hero = is_array($content['hero'] ?? null) ? $content['hero'] : [];
        $schoolAd = is_array($schoolAd ?? null) ? $schoolAd : (is_array($content['school_ad'] ?? null) ? $content['school_ad'] : []);
        $process = is_array($content['process'] ?? null) ? $content['process'] : [];
        $boardMembers = is_array($boardMembers ?? null) ? $boardMembers : (is_array($content['board_members'] ?? null) ? $content['board_members'] : []);
        $alumniOfficeTeam = is_array($alumniOfficeTeam ?? null) ? $alumniOfficeTeam : (is_array($content['alumni_office_team'] ?? null) ? $content['alumni_office_team'] : []);
        $contactPanels = is_array($content['contact_panels'] ?? null) ? $content['contact_panels'] : [];
        $landingStats = is_array($landingStats ?? null) ? $landingStats : [];
        $announcements = collect($announcements ?? []);
        $announcementTotal = isset($announcementTotal) ? (int) $announcementTotal : $announcements->count();
        $upcomingEvents = collect($upcomingEvents ?? []);
        $upcomingEventTotal = isset($upcomingEventTotal) ? (int) $upcomingEventTotal : $upcomingEvents->count();
        $eventRegistrationsByEventId = $eventRegistrationsByEventId ?? collect();
        $eventRegistrationStatuses = $eventRegistrationStatuses ?? [];
        $alumniPostTotal = (int) data_get($landingStats, '0.value', 0);
        $boardMemberTotal = (int) data_get($landingStats, '1.value', 0);
        $alumniOfficerTotal = (int) data_get($landingStats, '2.value', 0);
        $photoSlides = is_array($schoolAd['photo_slides'] ?? null) ? $schoolAd['photo_slides'] : [];
        $topbarLocation = 'St. Bridget College, M.H. Del Pilar St., Batangas City';
        $topbarMapUrl = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($topbarLocation);
        $topbarPhoneLabel = '+63 43 723 3616';
        $topbarPhoneHref = 'tel:+63437233616';
        $topbarFacebookUrl = 'https://www.facebook.com/profile.php?id=61580277583049';
        $topbarSearchPlaceholder = 'Search Alumni Link';
        $sbcLogoPath = null;
        foreach (['images/sbc-logo.png', 'images/sbc-logo.jpg', 'images/sbc-logo.jpeg', 'images/sbc-logo.webp', 'images/sbc-logo.svg'] as $candidate) {
            if (is_file(public_path($candidate))) {
                $sbcLogoPath = $candidate;
                break;
            }
        }
        $hasSbcLogo = is_string($sbcLogoPath);
        $currentUser = auth()->user();
        $isLoggedInAdmin = auth()->check() && $currentUser?->isAdmin();
        $isLoggedInAlumni = auth()->check() && $currentUser?->isAlumni();
        $portalLoginUrl = route('portal.login', ['switch' => 1]);
        $portalRegisterUrl = route('portal.register');
        $portalDashboardUrl = route('portal.dashboard');
        $adminDashboardUrl = route('dashboard');
    @endphp

    <div class="landing-page">
        @unless ($isAdminPreview)
            <header class="landing-header">
            <div class="landing-topbar">
                <div class="main-wrapper landing-topbar-shell">
                    <div class="landing-topbar-contact">
                        <a href="{{ $topbarMapUrl }}" class="landing-topbar-link" target="_blank" rel="noopener" aria-label="Open St. Bridget College location in Google Maps">
                            <span class="landing-topbar-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/></svg>
                            </span>
                            <span>{{ $topbarLocation }}</span>
                        </a>
                        <a href="{{ $topbarPhoneHref }}" class="landing-topbar-link" aria-label="Call St. Bridget College at {{ $topbarPhoneLabel }}">
                            <span class="landing-topbar-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8a15.7 15.7 0 0 0 6.6 6.6l2.2-2.2a1.2 1.2 0 0 1 1.2-.3 12 12 0 0 0 3.8.6 1.2 1.2 0 0 1 1.2 1.2v3.5a1.2 1.2 0 0 1-1.2 1.2A18.4 18.4 0 0 1 2.6 3.6a1.2 1.2 0 0 1 1.2-1.2h3.5a1.2 1.2 0 0 1 1.2 1.2 12 12 0 0 0 .6 3.8 1.2 1.2 0 0 1-.3 1.2l-2.2 2.2Z"/></svg>
                            </span>
                            <span>{{ $topbarPhoneLabel }}</span>
                        </a>
                    </div>

                    <div class="landing-topbar-actions">
                        <form class="landing-topbar-search-form" data-landing-search-form role="search" action="{{ route('home') }}" method="get">
                            <label class="visually-hidden" for="landingSearchInput">Search the landing page</label>
                            <input
                                id="landingSearchInput"
                                type="search"
                                name="q"
                                class="landing-topbar-search-input"
                                value="{{ request('q', '') }}"
                                placeholder="{{ $topbarSearchPlaceholder }}"
                                autocomplete="off"
                                data-landing-search-input>
                            <button type="submit" class="landing-topbar-search-button" aria-label="Search the landing page">
                                <span class="landing-topbar-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false"><path d="m20.7 19.3-4.1-4.1a7.5 7.5 0 1 0-1.4 1.4l4.1 4.1 1.4-1.4ZM10.8 16a5.2 5.2 0 1 1 0-10.4 5.2 5.2 0 0 1 0 10.4Z"/></svg>
                                </span>
                                <span>Search</span>
                            </button>
                        </form>
                        <a href="{{ $topbarFacebookUrl }}" class="landing-topbar-social" target="_blank" rel="noopener" aria-label="Open St. Bridget College alumni Facebook page">
                            <span>FACEBOOK</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="school-identity-banner">
                <div class="main-wrapper">
                    <div class="school-identity-shell">
                        <a href="{{ route('home') }}" class="school-identity-lockup text-decoration-none" aria-label="St. Bridget College home">
                            <div class="school-identity-crest {{ $hasSbcLogo ? 'school-identity-crest-logo' : '' }}">
                                @if ($hasSbcLogo)
                                    <img src="{{ asset($sbcLogoPath) }}" alt="St. Bridget College Batangas Logo" width="64" height="64" decoding="async">
                                @else
                                    SBC
                                @endif
                            </div>
                            <div class="school-identity-copy">
                                <div class="school-identity-title">ST. BRIDGET COLLEGE</div>
                                <div class="school-identity-motto">Luceat Lux Vestra</div>
                            </div>
                        </a>

                        <div class="school-identity-actions">
                            @if ($isLoggedInAdmin)
                                <a href="{{ $adminDashboardUrl }}" class="btn btn-outline-primary">Dashboard</a>
                            @elseif ($isLoggedInAlumni)
                                <a href="{{ $portalDashboardUrl }}" class="btn btn-outline-primary">Dashboard</a>
                            @else
                                <a href="{{ $portalLoginUrl }}" class="btn btn-outline-primary">Login</a>
                                <a href="{{ $portalRegisterUrl }}" class="btn btn-outline-primary">Create Account</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="landing-nav" role="navigation" aria-label="Main navigation">
                <div class="main-wrapper">
                    <div class="nav flex-nowrap flex-lg-wrap">
                        <a class="nav-link active" href="#home" data-landing-nav-link aria-current="page">Home</a>
                        <a class="nav-link" href="#about" data-landing-nav-link>About</a>
                        <a class="nav-link" href="#campus-gallery" data-landing-nav-link>Campus Gallery</a>
                        <a class="nav-link" href="#alumni-feed" data-landing-nav-link>Alumni Feed</a>
                        <a class="nav-link" href="#updates" data-landing-nav-link>Announcements</a>
                        <a class="nav-link" href="#events" data-landing-nav-link>Events</a>
                        <a class="nav-link" href="#leadership" data-landing-nav-link>Board of Trustees</a>
                        <a class="nav-link" href="#alumni-office" data-landing-nav-link>Alumni Officers</a>
                        <a class="nav-link" href="#contact" data-landing-nav-link>Contact</a>
                    </div>
                </div>
            </div>
            </header>
        @endunless

        <div class="main-wrapper">
            <div class="event-card p-3 mt-3 landing-search-empty" hidden data-landing-search-empty role="status" aria-live="polite">
                No landing page matches found. Try an alumni post, announcement, event, officer name, contact detail, or a campus keyword.
            </div>
        </div>

        @unless ($isAdminPreview)
            <section class="landing-mobile-entry d-lg-none">
            <div class="main-wrapper">
                <div class="landing-mobile-hero p-3 mt-3">
                    <img src="{{ asset('images/alumni-header.jpg') }}" class="hero-campus-backdrop" alt="" aria-hidden="true" decoding="async" fetchpriority="high">
                    <div class="landing-mobile-hero-head d-flex align-items-start justify-content-between gap-3">
                        <div class="min-w-0">
                            <div class="hero-badge mb-2">{{ $hero['eyebrow'] }}</div>
                            <div class="mobile-portal-badge">{{ $brand['school'] }}</div>
                            <h1 class="h3 mb-2">{{ $hero['title'] }}</h1>
                            <p class="mb-0 text-white-50">{{ $hero['summary'] }}</p>
                        </div>
                    </div>

                    <div class="row row-cols-3 g-2 mt-3">
                        @foreach ($landingStats as $metric)
                            <div class="col">
                                <div class="mobile-portal-stat text-center h-100">
                                    <span class="mobile-portal-stat-value">{{ $metric['value'] }}</span>
                                    <span class="mobile-portal-stat-label">{{ $metric['label'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="landing-mobile-chips mt-3 mb-2">
                    <a href="#alumni-feed" class="landing-chip">Alumni Feed</a>
                    <a href="#updates" class="landing-chip">Announcements</a>
                    <a href="#about" class="landing-chip">About</a>
                    <a href="#contact" class="landing-chip">Contact</a>
                    @if ($isLoggedInAdmin)
                        <a href="{{ $adminDashboardUrl }}" class="landing-chip">Dashboard</a>
                    @elseif ($isLoggedInAlumni)
                        <a href="{{ $portalDashboardUrl }}" class="landing-chip">Dashboard</a>
                    @else
                        <a href="{{ $portalLoginUrl }}" class="landing-chip">Login</a>
                        <a href="{{ $portalRegisterUrl }}" class="landing-chip">Create Account</a>
                    @endif
                </div>
            </div>
            </section>

            @if (! $isLoggedInAdmin && ! $isLoggedInAlumni)
                <div class="landing-mobile-actions d-lg-none">
                    <div class="main-wrapper">
                        <div class="landing-mobile-actions-grid">
                            <a href="{{ route('portal.login', ['switch' => 1]) }}" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span>
                                <span class="landing-mobile-action-label">Portal</span>
                            </a>
                            <a href="{{ $portalRegisterUrl }}" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 12-5M19 12v8m-4-4h8"/></svg></span>
                                <span class="landing-mobile-action-label">Join</span>
                            </a>
                            <a href="tel:{{ preg_replace('/[^0-9]/', '', $brand['phone']) }}" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3H3v3c0 8.3 6.7 15 15 15h3v-3l-5-3-2 2a12 12 0 0 1-7-7l2-2Z"/></svg></span>
                                <span class="landing-mobile-action-label">Call</span>
                            </a>
                            <a href="#contact" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/></svg></span>
                                <span class="landing-mobile-action-label">Contact</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endunless

        <section id="home" class="landing-section hero-section pt-3 pt-lg-5">
                <div class="hero-stage reveal d-none d-lg-block">
                    <img src="{{ asset('images/alumni-header.jpg') }}" class="hero-campus-backdrop" alt="" aria-hidden="true" decoding="async" fetchpriority="high">
                    <div class="row gx-0 align-items-stretch position-relative hero-columns">
                        <div class="col-lg-12 hero-left-panel">
                            <div class="hero-badge">{{ $hero['eyebrow'] }}</div>
                            <h1 class="hero-heading">{{ $hero['title'] }}</h1>
                            <p class="hero-copy mb-4">{{ $hero['summary'] }}</p>
                            <div class="landing-hero-actions mb-4">
                                @if ($isLoggedInAdmin)
                                    <a href="{{ $adminDashboardUrl }}" class="btn btn-light">Go to dashboard <span aria-hidden="true">&rarr;</span></a>
                                @elseif ($isLoggedInAlumni)
                                    <a href="{{ $portalDashboardUrl }}" class="btn btn-light">Go to dashboard <span aria-hidden="true">&rarr;</span></a>
                                @else
                                    <a href="{{ $portalRegisterUrl }}" class="btn btn-light">Join the alumni community <span aria-hidden="true">&rarr;</span></a>
                                @endif
                                <a href="#events" class="btn btn-outline-light">Explore events</a>
                            </div>
                            <div class="row g-3">
                                @foreach ($landingStats as $metric)
                                    <div class="col-sm-4">
                                        <a href="{{ $metric['href'] ?? '#' }}" class="hero-metric hero-metric-link h-100 text-decoration-none">
                                            <div class="hero-metric-value">{{ $metric['value'] }}</div>
                                            <div class="small text-white-50">{{ $metric['label'] }}</div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                </div>
                </div>

                <div id="campus-gallery" class="campus-gallery-hero hero-campus-gallery reveal" data-landing-search-group data-search-text="Campus Gallery St. Bridget College photos videos campus story">
                    <div
                        id="campusGalleryCarousel"
                        class="carousel slide carousel-fade campus-gallery-carousel"
                        data-bs-interval="6500"
                        data-bs-pause="hover"
                        role="region"
                        aria-roledescription="carousel"
                        aria-label="Campus gallery">

                        @if (count($photoSlides) > 1)
                            <button type="button" class="campus-gallery-playback" data-gallery-playback aria-label="Pause campus slideshow" hidden>
                                Pause slideshow
                            </button>
                        @endif

                        @if (count($photoSlides) > 1)
                            <div class="carousel-indicators campus-carousel-indicators">
                                @foreach ($photoSlides as $slide)
                                    <button
                                        type="button"
                                        data-bs-target="#campusGalleryCarousel"
                                        data-bs-slide-to="{{ $loop->index }}"
                                        class="{{ $loop->first ? 'active' : '' }}"
                                        aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                        aria-label="Photo slide {{ $loop->iteration }}"></button>
                                @endforeach
                            </div>
                        @endif

                        <div class="carousel-inner">
                            @forelse ($photoSlides as $slide)
                                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                    @if (($slide['type'] ?? 'photo') === 'video')
                                        <video
                                            src="{{ $slide['url'] }}"
                                            class="campus-gallery-image campus-gallery-video"
                                            controls
                                            muted
                                            loop
                                            preload="none"
                                            playsinline
                                            poster="">
                                            Your browser does not support the video tag.
                                        </video>
                                    @else
                                        <img
                                            src="{{ $slide['url'] }}"
                                            class="campus-gallery-image"
                                            alt="{{ $slide['title'] ?: 'St. Bridget College Batangas campus photo' }}"
                                            loading="lazy"
                                            decoding="async">
                                    @endif
                                    <div class="campus-gallery-caption">
                                        <div class="campus-gallery-kicker">{{ $schoolAd['eyebrow'] }}</div>
                                        <h3 class="campus-gallery-title">{{ $slide['title'] ?: $schoolAd['title'] }}</h3>
                                        <p class="campus-gallery-detail mb-0">{{ $slide['detail'] ?: $schoolAd['summary'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="carousel-item active">
                                    <div class="campus-gallery-placeholder">
                                        <div class="campus-gallery-caption campus-gallery-caption-static">
                                            <div class="campus-gallery-kicker">{{ $schoolAd['eyebrow'] }}</div>
                                            <h3 class="campus-gallery-title">{{ $schoolAd['title'] }}</h3>
                                        </div>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        @if (count($photoSlides) > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#campusGalleryCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#campusGalleryCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        @endif
                    </div>
                        </div>
                </section>

        <section id="about" class="landing-section pt-0 about-link-section" data-landing-search-group data-search-text="About Alumni Link claim alumni access submit requests stay involved">
            <div class="main-wrapper">
                <div class="about-link-head mb-3 reveal">
                    <div class="section-eyebrow">About Alumni Link</div>
                </div>

                <div class="row g-4 about-process-grid">
                    @foreach ($process as $item)
                        <div class="col-md-4 reveal about-process-item" style="--about-delay: {{ $loop->index * 90 }}ms;" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($item['step'].' '.$item['title'].' '.$item['description']) }}">
                            <div class="process-card about-process-card p-4 h-100">
                                <div class="about-process-step-row">
                                    <span class="about-process-step">{{ $item['step'] }}</span>
                                    <span class="about-process-line" aria-hidden="true"></span>
                                </div>
                                <h3 class="about-process-title mb-3">{{ $item['title'] }}</h3>
                                <p class="about-process-copy mb-0">{{ $item['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section pt-0 landing-board-section" data-landing-search-group data-search-text="Events announcements activities community calendar school notices alumni stories Bridgetine updates">
            <div class="main-wrapper">
                <div class="mb-4">
                    <div class="section-eyebrow">Stay connected</div>
                    <h2 class="h3 mt-2 mb-2">SBC Alumni Feed</h2>
                    <p class="text-secondary mb-0">The latest events, school announcements, and stories from your alumni community.</p>
                </div>
                <div class="landing-board reveal">
                    <div id="events" class="landing-board-column landing-board-column-events">
                        <div class="landing-board-header">
                            <span class="landing-board-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M7 2h2v3h6V2h2v3h3a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3V2Zm13 9H4v9h16v-9ZM4 9h16V7H4v2Z"/></svg>
                            </span>
                            <div>
                                <div class="landing-board-title">Events</div>
                                <div class="landing-board-count">{{ $upcomingEventTotal }} {{ $upcomingEventTotal === 1 ? 'event' : 'events' }}</div>
                            </div>
                        </div>
                        <div class="landing-board-list">
                            @forelse ($upcomingEvents as $event)
                                @php
                                    $eventModalId = 'event-detail-'.$event->id;
                                    $eventViews = (int) ($event->views_count ?? 0);
                                @endphp
                                <div class="landing-board-item reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($event->title.' '.$event->description.' '.($event->location ?? '').' '.$event->event_date?->format('F d, Y')) }}">
                                    <article class="landing-board-card"
                                        role="button"
                                        tabindex="0"
                                        aria-haspopup="dialog"
                                        aria-controls="{{ $eventModalId }}"
                                        aria-label="Read full event: {{ $event->title }}"
                                        data-event-card
                                        data-event-target="#{{ $eventModalId }}"
                                        data-event-view-url="{{ route('events.view', $event) }}">
                                        <h3 class="landing-board-card-title">{{ $event->title }}</h3>
                                        <div class="landing-board-card-meta">
                                            By St. Bridget College <span>|</span> {{ $event->event_date->format('F d, Y') }}
                                        </div>
                                        <div class="landing-board-card-meta">
                                            Views: <span data-event-views-count>{{ number_format($eventViews) }}</span>
                                        </div>
                                    </article>
                                </div>
                            @empty
                                <div class="landing-board-empty">No upcoming events yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div id="updates" class="landing-board-column landing-board-column-announcements">
                        <div class="landing-board-header">
                            <span class="landing-board-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M4 4h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-6.6L8 21.6V18H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm0 2v10h6v1.9l2.8-1.9H20V6H4Zm3 3h10v2H7V9Zm0 4h7v2H7v-2Z"/></svg>
                            </span>
                            <div>
                                <div class="landing-board-title">Announcement</div>
                                <div class="landing-board-count">{{ $announcementTotal }} {{ $announcementTotal === 1 ? 'notice' : 'notices' }}</div>
                            </div>
                        </div>
                        <div class="landing-board-list">
                            @forelse ($announcements as $announcement)
                                @php
                                    $announcementLabel = $announcement['label'] ?? 'Announcement';
                                    $announcementTitle = $announcement['title'] ?? '';
                                    $announcementDescription = $announcement['description'] ?? '';
                                    $announcementPublishedAt = $announcement['published_at'] ?? null;
                                    $announcementModalId = 'announcement-detail-'.($announcement['id'] ?? $loop->index);
                                    $announcementViews = (int) ($announcement['views_count'] ?? 0);
                                @endphp
                                <div class="landing-board-item reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($announcementLabel.' '.$announcementTitle.' '.$announcementDescription.' '.($announcementPublishedAt ? \Illuminate\Support\Carbon::parse($announcementPublishedAt)->format('F d, Y') : '')) }}">
                                    <article class="landing-board-card"
                                        role="button"
                                        tabindex="0"
                                        aria-haspopup="dialog"
                                        aria-controls="{{ $announcementModalId }}"
                                        aria-label="Read full announcement: {{ $announcementTitle }}"
                                        data-announcement-view-url="{{ isset($announcement['id']) ? route('announcements.view', $announcement['id']) : '' }}"
                                        data-announcement-card
                                        data-announcement-target="#{{ $announcementModalId }}">
                                        <h3 class="landing-board-card-title">{{ $announcementTitle }}</h3>
                                        <div class="landing-board-card-meta">
                                            By St. Bridget College
                                            @if ($announcementPublishedAt)
                                                <span>|</span> {{ \Illuminate\Support\Carbon::parse($announcementPublishedAt)->format('F d, Y') }}
                                            @endif
                                        </div>
                                        <div class="landing-board-card-meta">
                                            Views: <span data-announcement-views-count>{{ number_format($announcementViews) }}</span>
                                        </div>
                                    </article>
                                </div>
                            @empty
                                <div class="landing-board-empty">No announcements yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div id="alumni-feed" class="landing-board-column landing-board-column-activities">
                        <div class="landing-board-header">
                            <span class="landing-board-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 2 9.2 8.4 2 9.1l5.4 4.7-1.6 7L12 17.1l6.2 3.7-1.6-7L22 9.1l-7.2-.7L12 2Zm0 5 1.4 3.2 3.6.4-2.7 2.3.8 3.5-3.1-1.9-3.1 1.9.8-3.5L7 10.6l3.6-.4L12 7Z"/></svg>
                            </span>
                            <div>
                                <div class="landing-board-title">Activities</div>
                                <div class="landing-board-count">{{ $alumniPostTotal }} alumni {{ $alumniPostTotal === 1 ? 'post' : 'posts' }} published</div>
                            </div>
                        </div>
                        <div class="landing-board-list">
                            @forelse ($activities as $activity)
                                @php
                                    $activityViews = (int) ($activity['views_count'] ?? 0);
                                    $activityModalId = 'activity-detail-'.($activity['id'] ?? $loop->index);
                                @endphp
                                <div class="landing-board-item reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower(($activity['theme'] ?? '').' '.($activity['title'] ?? '').' '.($activity['description'] ?? '').' '.($activity['location'] ?? '').' '.(isset($activity['activity_date']) ? \Illuminate\Support\Carbon::parse($activity['activity_date'])->format('F d, Y') : '')) }}">
                                    <article class="landing-board-card"
                                        role="button"
                                        tabindex="0"
                                        aria-haspopup="dialog"
                                        aria-controls="{{ $activityModalId }}"
                                        aria-label="Read full activity: {{ $activity['title'] }}"
                                        data-activity-view-url="{{ isset($activity['id']) ? route('activities.view', $activity['id']) : '' }}"
                                        data-activity-card
                                        data-activity-target="#{{ $activityModalId }}">
                                        <h3 class="landing-board-card-title">{{ $activity['title'] }}</h3>
                                        <div class="landing-board-card-meta">
                                            By St. Bridget College
                                            @if (! empty($activity['activity_date']))
                                                <span>|</span> {{ \Illuminate\Support\Carbon::parse($activity['activity_date'])->format('F d, Y') }}
                                            @endif
                                        </div>
                                        <div class="landing-board-card-meta">
                                            Views: <span data-activity-views-count>{{ number_format($activityViews) }}</span>
                                        </div>
                                    </article>
                                </div>
                            @empty
                                <div class="landing-board-empty">No alumni posts yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @foreach ($announcements as $announcement)
                    @php
                        $announcementLabel = $announcement['label'] ?? 'Announcement';
                        $announcementTitle = $announcement['title'] ?? '';
                        $announcementDescription = $announcement['description'] ?? '';
                        $announcementPublishedAt = $announcement['published_at'] ?? null;
                        $announcementHasMedia = ! empty($announcement['media_url']);
                        $announcementModalId = 'announcement-detail-'.($announcement['id'] ?? $loop->index);
                    @endphp
                    <div class="modal fade announcement-detail-modal" id="{{ $announcementModalId }}" tabindex="-1" aria-labelledby="{{ $announcementModalId }}-title" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <div class="alumni-post-badge mb-2">{{ $announcementLabel ?: 'Announcement' }}</div>
                                        <h3 class="modal-title" id="{{ $announcementModalId }}-title">{{ $announcementTitle }}</h3>
                                        @if ($announcementPublishedAt)
                                            <div class="alumni-post-meta mt-1">{{ \Illuminate\Support\Carbon::parse($announcementPublishedAt)->format('F d, Y') }}</div>
                                        @endif
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @if ($announcementHasMedia)
                                        <div class="announcement-detail-media mb-4">
                                            @if (($announcement['media_type'] ?? null) === 'image')
                                                <img src="{{ $announcement['media_url'] }}" alt="{{ $announcementTitle }}" loading="lazy" decoding="async">
                                            @elseif (($announcement['media_type'] ?? null) === 'video')
                                                <video controls playsinline preload="none">
                                                    <source src="{{ $announcement['media_url'] }}">
                                                    Your browser does not support the video tag.
                                                </video>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="announcement-detail-copy">{!! nl2br(e($announcementDescription)) !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @foreach ($upcomingEvents as $event)
                    @php
                        $eventModalId = 'event-detail-'.$event->id;
                        $eventHasMedia = (bool) $event->media_url;
                        $eventRegistration = $eventRegistrationsByEventId->get($event->id);
                        $eventRegistrationStatus = $eventRegistration
                            ? ($eventRegistrationStatuses[$eventRegistration->status] ?? $eventRegistration->status_label)
                            : null;
                    @endphp
                    <div class="modal fade event-detail-modal" id="{{ $eventModalId }}" tabindex="-1" aria-labelledby="{{ $eventModalId }}-title" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <div class="alumni-post-badge mb-2">Event</div>
                                        <h3 class="modal-title" id="{{ $eventModalId }}-title">{{ $event->title }}</h3>
                                        <div class="alumni-post-meta mt-1">
                                            {{ $event->event_date->format('F d, Y') }}
                                            @if ($event->location)
                                                <span>| {{ $event->location }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @if ($isLoggedInAlumni)
                                        @if ($eventRegistration)
                                            <div class="event-detail-registration mb-4">
                                                <div class="d-flex flex-column flex-sm-row justify-content-between gap-1">
                                                    <div class="fw-semibold">Registration: {{ $eventRegistrationStatus }}</div>
                                                    <div class="small text-secondary">
                                                        {{ $eventRegistration->registered_at?->format('M d, Y h:i A') }}
                                                    </div>
                                                </div>

                                                @if ($eventRegistration->admin_reply)
                                                    <div class="event-detail-registration-reply mt-2">{{ $eventRegistration->admin_reply }}</div>
                                                @else
                                                    <div class="small text-secondary mt-1">Waiting for admin reply.</div>
                                                @endif
                                            </div>
                                        @else
                                            @php
                                                $registrationFormExpanded = (string) old('event_registration_event_id') === (string) $event->id;
                                                $registrationFieldsId = 'landing_event_registration_'.$event->id;
                                            @endphp
                                            <form method="POST" action="{{ route('portal.events.registrations.store', $event) }}" class="event-detail-register mb-4" data-event-registration-form>
                                                @csrf
                                                <button
                                                    type="button"
                                                    class="btn btn-primary w-100"
                                                    aria-expanded="{{ $registrationFormExpanded ? 'true' : 'false' }}"
                                                    aria-controls="{{ $registrationFieldsId }}"
                                                    data-event-registration-toggle>
                                                    Register to Event
                                                </button>
                                                @include('events._registration_requirements', [
                                                    'registrationFieldId' => $registrationFieldsId,
                                                    'registrationEventId' => $event->id,
                                                    'registrationAlumnus' => $currentUser?->alumni,
                                                    'registrationUser' => $currentUser,
                                                    'registrationExpanded' => $registrationFormExpanded,
                                                    'registrationHasErrors' => $registrationFormExpanded,
                                                    'registrationSubmitClass' => 'btn btn-primary',
                                                ])
                                            </form>
                                        @endif
                                    @elseif (! $isLoggedInAdmin)
                                        <div class="event-detail-register mb-4" data-event-registration-denied>
                                            <button
                                                type="button"
                                                class="btn btn-primary w-100"
                                                aria-expanded="false"
                                                data-event-registration-denied-toggle>
                                                Register to Event
                                            </button>
                                            <div class="event-registration-failed mt-3" hidden data-event-registration-denied-message>
                                                <div class="event-registration-failed-title">Failed to register</div>
                                                <div class="event-registration-failed-copy">Please log in or create an alumni account before registering for this event.</div>
                                                <div class="event-registration-failed-actions">
                                                    <a href="{{ $portalLoginUrl }}" class="btn btn-sm btn-primary">Login</a>
                                                    <a href="{{ $portalRegisterUrl }}" class="btn btn-sm btn-outline-primary">Create Account</a>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($eventHasMedia)
                                        <div class="event-detail-media mb-4">
                                            @if ($event->isImageMedia())
                                                <img src="{{ $event->media_url }}" alt="{{ $event->title }}" loading="lazy" decoding="async">
                                            @elseif ($event->isVideoMedia())
                                                <video controls playsinline preload="none">
                                                    <source src="{{ $event->media_url }}">
                                                    Your browser does not support the video tag.
                                                </video>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="event-detail-copy">{!! nl2br(e($event->description)) !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @foreach ($activities as $activity)
                    @php
                        $activityModalId = 'activity-detail-'.($activity['id'] ?? $loop->index);
                        $activityTitle = $activity['title'] ?? '';
                        $activityTheme = $activity['theme'] ?? 'Activity';
                        $activityDescription = $activity['description'] ?? '';
                        $activityDate = $activity['activity_date'] ?? null;
                        $activityLocation = $activity['location'] ?? null;
                        $activityHasMedia = ! empty($activity['media_url']);
                    @endphp
                    <div class="modal fade activity-detail-modal" id="{{ $activityModalId }}" tabindex="-1" aria-labelledby="{{ $activityModalId }}-title" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <div class="alumni-post-badge mb-2">{{ $activityTheme ?: 'Activity' }}</div>
                                        <h3 class="modal-title" id="{{ $activityModalId }}-title">{{ $activityTitle }}</h3>
                                        @if ($activityDate || $activityLocation)
                                            <div class="alumni-post-meta mt-1">
                                                @if ($activityDate)
                                                    <span>{{ \Illuminate\Support\Carbon::parse($activityDate)->format('F d, Y') }}</span>
                                                @endif
                                                @if ($activityLocation)
                                                    <span>{{ $activityLocation }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @if ($activityHasMedia)
                                        <div class="activity-detail-media mb-4">
                                            @if (($activity['media_type'] ?? null) === 'image')
                                                <img src="{{ $activity['media_url'] }}" alt="{{ $activityTitle }}" loading="lazy" decoding="async">
                                            @elseif (($activity['media_type'] ?? null) === 'video')
                                                <video controls playsinline preload="none">
                                                    <source src="{{ $activity['media_url'] }}">
                                                    Your browser does not support the video tag.
                                                </video>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="activity-detail-copy">{!! nl2br(e($activityDescription)) !!}</div>
                                    @if (isset($activity['id']))
                                        <a href="{{ route('activities.show', $activity['id']) }}" class="btn btn-outline-primary mt-4">View full post <span aria-hidden="true">&rarr;</span></a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="leadership" class="landing-section" data-landing-search-group data-search-text="Board of Trustees school leadership St. Bridget College Batangas">
            <div class="main-wrapper">
                <div class="row g-4 align-items-end mb-3">
                    <div class="col-lg-8 reveal leadership-scroll-item" style="--leadership-delay: 0ms;">
                        <div class="section-eyebrow">School Leadership</div>
                        <h2 class="section-title">Board of Trustees of St. Bridget College Batangas</h2>
                    </div>
                </div>

                <div class="row g-4">
                    @foreach ($boardMembers as $member)
                        <div class="col-md-6 col-xl-4 reveal leadership-scroll-item" style="--leadership-delay: {{ 120 + ($loop->index * 90) }}ms;" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($member['name'].' '.$member['role']) }}">
                            <div class="trustee-member text-center py-4">
                                @if (! empty($member['photo_path']))
                                    <div class="trustee-avatar mx-auto mb-3">
                                        <img src="{{ $member['photo_url'] }}" alt="{{ $member['name'] }}" width="96" height="96" loading="lazy" decoding="async">
                                    </div>
                                @endif
                                <h3 class="trustee-name mb-2">{{ $member['name'] }}</h3>
                                <p class="trustee-role mb-0">{{ $member['role'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="alumni-office" class="landing-section pt-0" data-landing-search-group data-search-text="Alumni Officers St. Bridget College Batangas alumni office team contact">
            <div class="main-wrapper">
                <div class="mb-4 reveal officers-hero">
                    <div class="section-eyebrow">Alumni Officers</div>
                    <h2 class="section-title">Alumni Officers</h2>
                    <p class="section-copy">Meet the officers and committee leads supporting alumni engagement at St. Bridget College Batangas.</p>
                </div>

                <div class="row g-4 officers-grid">
                    @forelse ($alumniOfficeTeam as $member)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($member['name'].' '.$member['role'].' '.($member['department'] ?? '').' '.($member['details'] ?? '')) }}">
                            <div class="officer-card p-4 h-100 d-flex flex-column">
                                <div class="officer-media text-center mb-3">
                                    @if (! empty($member['photo_path']))
                                        <div class="officer-avatar mx-auto">
                                            <img src="{{ $member['photo_url'] }}" alt="{{ $member['name'] }}" width="96" height="96" loading="lazy" decoding="async">
                                        </div>
                                    @else
                                        <div class="officer-avatar officer-avatar-placeholder mx-auto">{{ isset($member['initials']) ? $member['initials'] : strtoupper(substr(trim($member['name'] ?? ''),0,2)) }}</div>
                                    @endif
                                </div>

                                <div class="officer-body text-center mt-auto">
                                    <div class="fw-semibold officer-name">{{ $member['name'] }}</div>
                                    <div class="text-secondary officer-role mb-2">{{ $member['role'] }}</div>
                                    @if (! empty($member['department'] ?? null))
                                        <div class="small text-muted officer-dept">{{ $member['department'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="event-card p-4 text-center">No officers defined yet.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>



        <section id="contact" class="landing-section pt-0" data-landing-search-group data-search-text="Contact and Access St. Bridget College Batangas address phone email alumni portal">
            <div class="main-wrapper">
                <div class="page-card p-4 reveal">
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-4">
                            <div class="section-eyebrow">Contact and Access</div>
                        </div>
                        <div class="col-lg-4" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($brand['school'].' '.$brand['address'].' '.$brand['phone'].' '.$brand['email']) }}">
                            <div class="event-card p-4 h-100">
                                <h3 class="h5 mb-3">{{ $brand['school'] }}</h3>
                                <p class="mb-2">{{ $brand['address'] }}</p>
                                <p class="mb-2">{{ $brand['phone'] }}</p>
                                <p class="mb-0">{{ $brand['email'] }}</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-grid gap-3">
                                @foreach ($contactPanels as $panel)
                                    <div class="event-card p-4" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($panel['title'].' '.$panel['description'].' '.$panel['action_label']) }}">
                                        <h3 class="h5 mb-2">{{ $panel['title'] }}</h3>
                                        <p class="text-secondary mb-3">{{ $panel['description'] }}</p>
                                        <a href="{{ route($panel['action_route']) }}" class="btn btn-outline-dark">{{ $panel['action_label'] }}</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="landing-footer">
            <div class="landing-footer-shell py-5">
                <div class="main-wrapper">
                    <div class="row g-4 g-xl-5">
                        <div class="col-lg-4">
                            <div class="footer-brand-panel h-100">
                                <div class="footer-kicker">St. Bridget College Batangas</div>
                                <h2 class="footer-title mb-3">{{ $brand['portal'] }}</h2>
                                <div class="footer-campus-list">
                                    <div class="footer-campus-item">
                                        <span class="footer-campus-label">Main Campus</span>
                                        <span>{{ $brand['address'] }}</span>
                                    </div>
                                    <div class="footer-campus-item">
                                        <span class="footer-campus-label">Alumni Helpdesk</span>
                                        <span>{{ $brand['phone'] }}</span>
                                    </div>
                                    <div class="footer-campus-item">
                                        <span class="footer-campus-label">Official Email</span>
                                        <span>{{ $brand['email'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-2">
                            <div class="footer-link-group">
                                <h3 class="footer-group-title">Quick Links</h3>
                                <a href="#home">Home</a>
                                <a href="#about">About</a>
                                <a href="#updates">Announcements</a>
                                <a href="#events">Events</a>
                                <a href="#leadership">Leadership</a>
                                <a href="#contact">Contact</a>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="footer-link-group">
                                <h3 class="footer-group-title">Bridgetine Highlights</h3>
                                <a href="#alumni-office">Alumni Office Team</a>
                                <a href="#campus-gallery">Campus Photo Gallery</a>
                                @if ($isLoggedInAdmin)
                                    <a href="{{ $adminDashboardUrl }}">Dashboard</a>
                                @elseif ($isLoggedInAlumni)
                                    <a href="{{ $portalDashboardUrl }}">Dashboard</a>
                                @endif
                                <a href="#updates">School Notices</a>
                                <a href="#events">Community Calendar</a>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="footer-cta-panel h-100">
                                <div class="footer-kicker">Alumni Services</div>
                                <h3 class="h5 mb-3">Stay connected with St. Bridget College Batangas</h3>
                                <p class="footer-copy mb-4">
                                    Access official updates, submit requests, and track alumni-related activities in one place.
                                </p>
                                <div class="d-grid gap-2">
                                    @if ($isLoggedInAdmin)
                                        <a href="{{ $adminDashboardUrl }}" class="btn btn-light">Dashboard</a>
                                    @elseif ($isLoggedInAlumni)
                                        <a href="{{ $portalDashboardUrl }}" class="btn btn-light">Dashboard</a>
                                    @else
                                        <a href="{{ $portalLoginUrl }}" class="btn btn-light">Login</a>
                                        <a href="{{ $portalRegisterUrl }}" class="btn btn-outline-light">Create Account</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="landing-footer-bottom py-3">
                <div class="main-wrapper d-flex flex-column flex-lg-row justify-content-between gap-2">
                    <div>&copy; {{ now()->year }} {{ $brand['school'] }}. All rights reserved.</div>
                </div>
            </div>
        </footer>
    </div>
@endsection

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
                    setOpen(fields.classList.contains('d-none'));
                });

                if (cancel) {
                    cancel.addEventListener('click', () => {
                        setOpen(false);
                    });
                }

                setOpen(!fields.classList.contains('d-none'));
            });

            document.querySelectorAll('[data-event-registration-denied]').forEach((box) => {
                const toggle = box.querySelector('[data-event-registration-denied-toggle]');
                const message = box.querySelector('[data-event-registration-denied-message]');

                if (!toggle || !message) {
                    return;
                }

                toggle.addEventListener('click', () => {
                    toggle.textContent = 'Failed to register';
                    toggle.setAttribute('aria-expanded', 'true');
                    message.hidden = false;
                    message.classList.remove('is-visible');

                    window.requestAnimationFrame(() => {
                        message.classList.add('is-visible');
                    });
                });
            });
        })();

        (function () {
            const carousel = document.getElementById('campusGalleryCarousel');

            if (!carousel) {
                return;
            }

            const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            const connection = navigator.connection;
            const playback = carousel.querySelector('[data-gallery-playback]');
            const instance = window.bootstrap?.Carousel.getOrCreateInstance(carousel, { ride: false });
            let isVisible = !('IntersectionObserver' in window);
            let userPaused = motionQuery.matches || Boolean(connection?.saveData);
            let isHovered = false;

            const pauseVideos = () => {
                carousel.querySelectorAll('video.campus-gallery-video').forEach((video) => {
                    try {
                        video.pause();
                    } catch (error) {
                        // Ignore pause failures from hidden slides.
                    }
                });
            };

            const playActiveVideo = () => {
                if (userPaused || !isVisible || document.hidden || motionQuery.matches || connection?.saveData) {
                    return;
                }

                const activeVideo = carousel.querySelector('.carousel-item.active video.campus-gallery-video');

                if (!activeVideo) {
                    return;
                }

                activeVideo.muted = true;
                activeVideo.loop = true;
                activeVideo.playsInline = true;

                const attempt = activeVideo.play();

                if (attempt && typeof attempt.catch === 'function') {
                    attempt.catch(() => {});
                }
            };

            const syncVideoState = () => {
                pauseVideos();
                instance?.pause();

                if (isVisible && !document.hidden && !userPaused && !isHovered && !carousel.contains(document.activeElement)) {
                    instance?.cycle();
                    playActiveVideo();
                }

                if (playback) {
                    playback.hidden = !instance;
                    playback.textContent = userPaused ? 'Play slideshow' : 'Pause slideshow';
                    playback.setAttribute('aria-label', userPaused ? 'Play campus slideshow' : 'Pause campus slideshow');
                }
            };

            carousel.addEventListener('slide.bs.carousel', pauseVideos);
            carousel.addEventListener('slid.bs.carousel', playActiveVideo);
            carousel.addEventListener('mouseenter', () => { isHovered = true; syncVideoState(); });
            carousel.addEventListener('mouseleave', () => { isHovered = false; syncVideoState(); });
            carousel.addEventListener('focusin', syncVideoState);
            carousel.addEventListener('focusout', () => window.setTimeout(syncVideoState, 0));
            playback?.addEventListener('click', () => { userPaused = !userPaused; syncVideoState(); });
            motionQuery.addEventListener('change', () => { userPaused = motionQuery.matches; syncVideoState(); });
            document.addEventListener('visibilitychange', syncVideoState);
            window.addEventListener('pagehide', () => { instance?.pause(); pauseVideos(); });
            window.addEventListener('pageshow', syncVideoState);

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(([entry]) => {
                    isVisible = entry.isIntersecting;
                    syncVideoState();
                }, { threshold: 0.1 });
                observer.observe(carousel);
            }

            syncVideoState();
        })();
    </script>
    <script>
        (function () {
            const links = Array.from(document.querySelectorAll('[data-landing-nav-link]'));

            if (!links.length) {
                return;
            }

            const sections = links
                .map((link) => document.getElementById((link.getAttribute('href') || '').replace('#', '')))
                .filter(Boolean);
            let activeSectionId = null;

            const setActiveLink = (sectionId) => {
                if (sectionId === activeSectionId) return;
                activeSectionId = sectionId;
                links.forEach((link) => {
                    const isActive = link.getAttribute('href') === `#${sectionId}`;

                    link.classList.toggle('active', isActive);

                    if (isActive) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            };

            const syncActiveLink = () => {
                const headerOffset = 140;
                let currentSectionId = 'home';

                sections.forEach((section) => {
                    if (section.getBoundingClientRect().top <= headerOffset) {
                        currentSectionId = section.id;
                    }
                });

                setActiveLink(currentSectionId);
            };

            links.forEach((link) => {
                link.addEventListener('click', () => {
                    const targetId = (link.getAttribute('href') || '').replace('#', '');

                    if (targetId) {
                        setActiveLink(targetId);
                    }
                });
            });

            let ticking = false;

            window.addEventListener('scroll', () => {
                if (ticking) {
                    return;
                }

                ticking = true;
                window.requestAnimationFrame(() => {
                    syncActiveLink();
                    ticking = false;
                });
            }, { passive: true });

            window.addEventListener('hashchange', () => {
                const targetId = window.location.hash.replace('#', '');

                if (targetId) {
                    setActiveLink(targetId);
                } else {
                    syncActiveLink();
                }
            });

            if (window.location.hash) {
                setActiveLink(window.location.hash.replace('#', ''));
            } else {
                syncActiveLink();
            }
        })();
    </script>
    <script>
        (function () {
            const form = document.querySelector('[data-landing-search-form]');
            const input = document.querySelector('[data-landing-search-input]');
            const emptyState = document.querySelector('[data-landing-search-empty]');
            const groups = Array.from(document.querySelectorAll('[data-landing-search-group]'));

            if (!input || !groups.length) {
                return;
            }

            const normalize = (value) => String(value || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();

            const getGroupText = (group) => group.getAttribute('data-search-text') || group.textContent || '';
            const getItemText = (item) => item.getAttribute('data-search-text') || item.textContent || '';
            const searchIndex = groups.map((group) => ({
                group,
                text: normalize(getGroupText(group)),
                items: Array.from(group.querySelectorAll('[data-landing-search-item]')).map((item) => ({
                    element: item,
                    text: normalize(getItemText(item)),
                })),
            }));
            let searchTimer;

            const applySearch = (query, scrollToMatch = false) => {
                const normalizedQuery = normalize(query);
                let firstMatch = null;
                let matchCount = 0;

                searchIndex.forEach(({ group, text, items }) => {
                    const groupMatches = normalizedQuery === '' || text.includes(normalizedQuery);

                    if (!normalizedQuery) {
                        group.hidden = false;
                        items.forEach(({ element }) => {
                            element.hidden = false;
                        });
                        return;
                    }

                    if (!items.length) {
                        group.hidden = !groupMatches;
                        if (groupMatches) {
                            matchCount += 1;
                            if (!firstMatch) {
                                firstMatch = group;
                            }
                        }
                        return;
                    }

                    let groupHasVisibleItems = false;

                    items.forEach(({ element: item, text }) => {
                        const itemMatches = text.includes(normalizedQuery);
                        const visible = groupMatches || itemMatches;

                        item.hidden = !visible;

                        if (visible) {
                            groupHasVisibleItems = true;
                            matchCount += 1;

                            if (!firstMatch) {
                                firstMatch = item;
                            }
                        }
                    });

                    group.hidden = !(groupMatches || groupHasVisibleItems);
                });

                if (emptyState) {
                    emptyState.hidden = normalizedQuery === '' || matchCount > 0;
                }

                if (scrollToMatch && firstMatch && typeof firstMatch.scrollIntoView === 'function') {
                    firstMatch.scrollIntoView({
                        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                        block: 'start',
                    });
                }
            };

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    window.clearTimeout(searchTimer);
                    applySearch(input.value, true);
                });
            }

            input.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => applySearch(input.value, false), 120);
            });

            applySearch(input.value, false);
        })();
    </script>
    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const formatViews = (value) => {
                const count = Number.parseInt(value, 10);

                return Number.isFinite(count) ? count.toLocaleString() : '0';
            };

            const recordAnnouncementView = (card) => {
                const url = card.getAttribute('data-announcement-view-url');

                if (!url || card.getAttribute('data-announcement-view-recorded') === 'true') {
                    return;
                }

                card.setAttribute('data-announcement-view-recorded', 'true');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        if (!data || typeof data.views_count === 'undefined') {
                            return;
                        }

                        const counter = card.querySelector('[data-announcement-views-count]');

                        if (counter) {
                            counter.textContent = formatViews(data.views_count);
                        }
                    })
                    .catch(() => {});
            };

            const recordEventView = (card) => {
                const url = card.getAttribute('data-event-view-url');

                if (!url || card.getAttribute('data-event-view-recorded') === 'true') {
                    return;
                }

                card.setAttribute('data-event-view-recorded', 'true');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        if (!data || typeof data.views_count === 'undefined') {
                            return;
                        }

                        const counter = card.querySelector('[data-event-views-count]');

                        if (counter) {
                            counter.textContent = formatViews(data.views_count);
                        }
                    })
                    .catch(() => {});
            };

            const recordActivityView = (card) => {
                const url = card.getAttribute('data-activity-view-url');

                if (!url || card.getAttribute('data-activity-view-recorded') === 'true') {
                    return;
                }

                card.setAttribute('data-activity-view-recorded', 'true');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        if (!data || typeof data.views_count === 'undefined') {
                            return;
                        }

                        const counter = card.querySelector('[data-activity-views-count]');

                        if (counter) {
                            counter.textContent = formatViews(data.views_count);
                        }
                    })
                    .catch(() => {});
            };

            const openAnnouncement = (card) => {
                const target = card.getAttribute('data-announcement-target');
                const modal = target ? document.querySelector(target) : null;

                if (!modal || !window.bootstrap || !window.bootstrap.Modal) {
                    return;
                }

                window.bootstrap.Modal.getOrCreateInstance(modal).show();
                recordAnnouncementView(card);
            };

            const openEvent = (card) => {
                const target = card.getAttribute('data-event-target');
                const modal = target ? document.querySelector(target) : null;

                if (!modal || !window.bootstrap || !window.bootstrap.Modal) {
                    return;
                }

                window.bootstrap.Modal.getOrCreateInstance(modal).show();
                recordEventView(card);
            };

            const openActivity = (card) => {
                const target = card.getAttribute('data-activity-target');
                const modal = target ? document.querySelector(target) : null;

                if (!modal || !window.bootstrap || !window.bootstrap.Modal) {
                    return;
                }

                window.bootstrap.Modal.getOrCreateInstance(modal).show();
                recordActivityView(card);
            };

            document.addEventListener('click', function (event) {
                const card = event.target.closest('[data-announcement-card]');

                if (!card) {
                    return;
                }

                if (event.target.closest('[data-announcement-media-control], a, button, input, select, textarea')) {
                    return;
                }

                openAnnouncement(card);
            });

            document.addEventListener('click', function (event) {
                const card = event.target.closest('[data-event-card]');

                if (!card) {
                    return;
                }

                if (event.target.closest('a, button, input, select, textarea')) {
                    return;
                }

                openEvent(card);
            });

            document.addEventListener('click', function (event) {
                const card = event.target.closest('[data-activity-card]');

                if (!card) {
                    return;
                }

                if (event.target.closest('a, button, input, select, textarea')) {
                    return;
                }

                openActivity(card);
            });

            document.addEventListener('keydown', function (event) {
                const card = event.target.closest('[data-announcement-card]');

                if (!card || event.target !== card || !['Enter', ' '].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                openAnnouncement(card);
            });

            document.addEventListener('keydown', function (event) {
                const card = event.target.closest('[data-event-card]');

                if (!card || event.target !== card || !['Enter', ' '].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                openEvent(card);
            });

            document.addEventListener('keydown', function (event) {
                const card = event.target.closest('[data-activity-card]');

                if (!card || event.target !== card || !['Enter', ' '].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                openActivity(card);
            });
        })();
    </script>

@endpush



@push('styles')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
@endpush
