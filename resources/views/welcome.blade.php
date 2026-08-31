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
        $alumniPostTotal = (int) data_get($landingStats, '0.value', 0);
        $boardMemberTotal = (int) data_get($landingStats, '1.value', 0);
        $alumniOfficerTotal = (int) data_get($landingStats, '2.value', 0);
        $photoSlides = is_array($schoolAd['photo_slides'] ?? null) ? $schoolAd['photo_slides'] : [];
        $topbarLocation = 'St. Bridget College, M.H. Del Pilar St., Batangas City';
        $topbarMapUrl = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($topbarLocation);
        $topbarPhoneLabel = '+63 43 723 3616';
        $topbarPhoneHref = 'tel:+63437233616';
        $topbarFacebookUrl = 'https://www.facebook.com/stbridgetcollege';
        $topbarXUrl = 'https://x.com/search?q='.rawurlencode('St. Bridget College Batangas alumni');
        $topbarInstagramUrl = 'https://www.instagram.com/explore/search/keyword/?q='.rawurlencode('St. Bridget College Batangas alumni');
        $topbarSearchPlaceholder = 'Search alumni posts, announcements, officers, or contact info';
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
                            <span aria-hidden="true">f</span>
                        </a>
                        <a href="{{ $topbarXUrl }}" class="landing-topbar-social landing-topbar-social-x" target="_blank" rel="noopener" aria-label="Search for St. Bridget College alumni on X">
                            <span aria-hidden="true">X</span>
                        </a>
                        <a href="{{ $topbarInstagramUrl }}" class="landing-topbar-social" target="_blank" rel="noopener" aria-label="Search for St. Bridget College alumni on Instagram">
                            <span class="landing-topbar-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9Zm4.5 3.25A4.75 4.75 0 1 1 12 16.75a4.75 4.75 0 0 1 0-9.5Zm0 2A2.75 2.75 0 1 0 12 14.75a2.75 2.75 0 0 0 0-5.5Zm5.1-2.05a1.1 1.1 0 1 1-1.1 1.1 1.1 1.1 0 0 1 1.1-1.1Z"/></svg>
                            </span>
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
                                    <img src="{{ asset($sbcLogoPath) }}" alt="St. Bridget College Batangas Logo">
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
                                <a href="{{ $portalLoginUrl }}" class="btn btn-outline-primary">Alumni Login</a>
                                <a href="{{ $portalRegisterUrl }}" class="btn btn-outline-primary">Claim Alumni Account</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="landing-nav">
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
            <div class="event-card p-3 mt-3 landing-search-empty" hidden data-landing-search-empty>
                No landing page matches found. Try an alumni post, announcement, event, officer name, contact detail, or a campus keyword.
            </div>
        </div>

        @unless ($isAdminPreview)
            <section class="landing-mobile-entry d-lg-none">
            <div class="main-wrapper">
                <div class="landing-mobile-hero p-3 mt-3">
                    <div class="hero-campus-building" aria-hidden="true"></div>
                    <div class="landing-mobile-hero-head d-flex align-items-start justify-content-between gap-3">
                        <div class="min-w-0">
                            <div class="hero-badge mb-2">{{ $hero['eyebrow'] }}</div>
                            <div class="mobile-portal-badge">{{ $brand['school'] }}</div>
                            <h2 class="h3 mb-2">{{ $hero['title'] }}</h2>
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
                        <a href="{{ $portalRegisterUrl }}" class="landing-chip">Register</a>
                    @endif
                </div>
            </div>
            </section>

            @if (! $isLoggedInAdmin && ! $isLoggedInAlumni)
                <div class="landing-mobile-actions d-lg-none">
                    <div class="main-wrapper">
                        <div class="landing-mobile-actions-grid">
                            <a href="{{ route('portal.login', ['switch' => 1]) }}" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon">P</span>
                                <span class="landing-mobile-action-label">Portal</span>
                            </a>
                            <a href="{{ route('portal.register') }}" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon">R</span>
                                <span class="landing-mobile-action-label">Register</span>
                            </a>
                            <a href="tel:{{ preg_replace('/[^0-9]/', '', $brand['phone']) }}" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon">C</span>
                                <span class="landing-mobile-action-label">Call</span>
                            </a>
                            <a href="#contact" class="landing-mobile-action">
                                <span class="landing-mobile-action-icon">M</span>
                                <span class="landing-mobile-action-label">More</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endunless

        <section id="home" class="landing-section hero-section pt-3 pt-lg-5">
                <div class="hero-stage reveal d-none d-lg-block">
                    <div class="hero-campus-building" aria-hidden="true"></div>
                    <div class="row gx-0 align-items-stretch position-relative hero-columns">
                        <div class="col-lg-12 hero-left-panel">
                            <div class="hero-badge">{{ $hero['eyebrow'] }}</div>
                            <h2 class="hero-heading">{{ $hero['title'] }}</h2>
                            <p class="hero-copy mb-4">{{ $hero['summary'] }}</p>
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                @if (! $isLoggedInAdmin && ! $isLoggedInAlumni)
                                    <a href="{{ $portalLoginUrl }}" class="btn btn-light btn-lg">Open Alumni Dashboard</a>
                                    <a href="{{ $portalRegisterUrl }}" class="btn btn-outline-light btn-lg">Register Alumni Account</a>
                                @endif
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
                        data-bs-ride="carousel"
                        data-bs-interval="4800"
                        data-bs-pause="false">

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
                                            preload="metadata"
                                            playsinline
                                            poster="">
                                            Your browser does not support the video tag.
                                        </video>
                                    @else
                                        <img
                                            src="{{ $slide['url'] }}"
                                            class="campus-gallery-image"
                                            alt="{{ $slide['title'] ?: 'St. Bridget College Batangas campus photo' }}">
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

        <section id="about" class="landing-section pt-0" data-landing-search-group data-search-text="About Alumni Link claim alumni access submit requests stay involved">
            <div class="main-wrapper">
                <div class="mb-3 reveal">
                    <div class="section-eyebrow">About Alumni Link</div>
                </div>

                <div class="row g-4">
                    @foreach ($process as $item)
                        <div class="col-md-4 reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($item['step'].' '.$item['title'].' '.$item['description']) }}">
                            <div class="process-card p-4 h-100">
                                <div class="section-eyebrow mb-2">{{ $item['step'] }}</div>
                                <h3 class="h4 mb-3">{{ $item['title'] }}</h3>
                                <p class="text-secondary mb-0">{{ $item['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section pt-0 landing-board-section" data-landing-search-group data-search-text="Events announcements activities community calendar school notices alumni stories Bridgetine updates">
            <div class="main-wrapper">
                <div class="landing-board reveal">
                    <div id="events" class="landing-board-column">
                        <div class="landing-board-header">
                            <div class="landing-board-title">Events</div>
                            <div class="landing-board-count">{{ $upcomingEventTotal }} {{ $upcomingEventTotal === 1 ? 'event' : 'events' }}</div>
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

                    <div id="updates" class="landing-board-column">
                        <div class="landing-board-header">
                            <div class="landing-board-title">Announcement</div>
                            <div class="landing-board-count">{{ $announcementTotal }} {{ $announcementTotal === 1 ? 'notice' : 'notices' }}</div>
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

                    <div id="alumni-feed" class="landing-board-column">
                        <div class="landing-board-header">
                            <div class="landing-board-title">Activities</div>
                            <div class="landing-board-count">{{ $alumniPostTotal }} {{ $alumniPostTotal === 1 ? 'activity' : 'activities' }}</div>
                        </div>
                        <div class="landing-board-list">
                            @forelse ($activities as $activity)
                                @php
                                    $activityViews = (int) ($activity['views_count'] ?? 0);
                                @endphp
                                <div class="landing-board-item reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower(($activity['theme'] ?? '').' '.($activity['title'] ?? '').' '.($activity['description'] ?? '').' '.($activity['location'] ?? '').' '.(isset($activity['activity_date']) ? \Illuminate\Support\Carbon::parse($activity['activity_date'])->format('F d, Y') : '')) }}">
                                    <a href="{{ $activity['show_url'] }}" class="landing-board-card landing-board-card-link text-decoration-none">
                                        <h3 class="landing-board-card-title">{{ $activity['title'] }}</h3>
                                        <div class="landing-board-card-meta">
                                            By St. Bridget College
                                            @if (! empty($activity['activity_date']))
                                                <span>|</span> {{ \Illuminate\Support\Carbon::parse($activity['activity_date'])->format('F d, Y') }}
                                            @endif
                                        </div>
                                        <div class="landing-board-card-meta">
                                            Views: {{ number_format($activityViews) }}
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <div class="landing-board-empty">No activities yet.</div>
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
                                                <img src="{{ $announcement['media_url'] }}" alt="{{ $announcementTitle }}">
                                            @elseif (($announcement['media_type'] ?? null) === 'video')
                                                <video controls playsinline preload="metadata">
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
                                    @if ($eventHasMedia)
                                        <div class="event-detail-media mb-4">
                                            @if ($event->isImageMedia())
                                                <img src="{{ $event->media_url }}" alt="{{ $event->title }}">
                                            @elseif ($event->isVideoMedia())
                                                <video controls playsinline preload="metadata">
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
            </div>
        </section>

        <section id="leadership" class="landing-section" data-landing-search-group data-search-text="Board of Trustees school leadership St. Bridget College Batangas">
            <div class="main-wrapper">
                <div class="row g-4 align-items-end mb-3">
                    <div class="col-lg-8 reveal leadership-scroll-item" data-leadership-animate style="--leadership-delay: 0ms;">
                        <div class="section-eyebrow">School Leadership</div>
                        <h2 class="section-title">Board of Trustees of St. Bridget College Batangas</h2>
                    </div>
                </div>

                <div class="row g-4">
                    @foreach ($boardMembers as $member)
                        <div class="col-md-6 col-xl-4 reveal leadership-scroll-item" data-leadership-animate style="--leadership-delay: {{ 120 + ($loop->index * 90) }}ms;" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($member['name'].' '.$member['role']) }}">
                            <div class="trustee-member text-center py-4">
                                @if (! empty($member['photo_path']))
                                    <div class="trustee-avatar mx-auto mb-3">
                                        <img src="{{ $member['photo_url'] }}" alt="{{ $member['name'] }}">
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
                                            <img src="{{ $member['photo_url'] }}" alt="{{ $member['name'] }}">
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

    @push('styles')
        <style>
            /* Center the Alumni Officers header and the officer cards on the landing page */
            #alumni-office .officers-hero {
                text-align: center;
            }

            #alumni-office .officers-hero .section-copy {
                margin-left: auto;
                margin-right: auto;
                max-width: 68ch;
            }

            /* Center the column children within the Bootstrap row */
            #alumni-office .officers-grid {
                justify-content: center;
            }

            /* Ensure each grid column centers its card so two members sit centered */
            #alumni-office .officers-grid > [class*="col-"] {
                display: flex;
                justify-content: center;
            }
            /* Make officer cards visually match the trustee (school leadership) style */
            #alumni-office .officer-card {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 1.75rem 0 !important;
                max-width: 28rem;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            #alumni-office .officer-name {
                color: #111111;
                font-size: clamp(1.1rem, 1.35vw, 1.35rem);
                font-weight: 700;
                line-height: 1.12;
                text-align: center;
            }

            #alumni-office .officer-role {
                color: #6c6f77;
                font-size: 0.96rem;
                line-height: 1.6;
                text-align: center;
            }

            #leadership .trustee-avatar,
            #alumni-office .officer-avatar {
                width: 96px;
                height: 96px;
                border-radius: 50%;
                overflow: hidden;
                background: var(--panel);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 2px solid rgba(4,0,120,0.06);
            }

            .leadership-motion-ready #leadership .leadership-scroll-item {
                opacity: 0;
                transform: translateY(34px) scale(0.96);
                filter: blur(6px);
                animation: none;
                transition:
                    opacity 0.7s ease,
                    transform 0.7s cubic-bezier(0.16, 1, 0.3, 1),
                    filter 0.7s ease;
                transition-delay: var(--leadership-delay, 0ms);
                will-change: opacity, transform, filter;
            }

            .leadership-motion-ready #leadership .leadership-scroll-item.is-visible {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }

            .leadership-motion-ready #leadership .leadership-scroll-item .trustee-avatar {
                transform: scale(0.86);
                transition: transform 0.65s cubic-bezier(0.16, 1, 0.3, 1);
                transition-delay: calc(var(--leadership-delay, 0ms) + 120ms);
            }

            .leadership-motion-ready #leadership .leadership-scroll-item.is-visible .trustee-avatar {
                transform: scale(1);
            }

            .leadership-motion-ready.leadership-is-scrolling #leadership .leadership-scroll-item.is-visible > * {
                animation: leadershipScrollFloat 1.05s ease-in-out infinite alternate;
                animation-delay: calc(var(--leadership-delay, 0ms) * 0.35);
            }

            .leadership-motion-ready.leadership-is-scrolling #leadership .leadership-scroll-item.is-visible .trustee-avatar {
                animation: leadershipAvatarPulse 0.95s ease-in-out infinite alternate;
                animation-delay: calc(var(--leadership-delay, 0ms) * 0.25);
            }

            @keyframes leadershipScrollFloat {
                from {
                    transform: translateY(0) scale(1);
                }

                to {
                    transform: translateY(-8px) scale(1.015);
                }
            }

            @keyframes leadershipAvatarPulse {
                from {
                    box-shadow: 0 0 0 rgba(11, 69, 184, 0);
                    transform: scale(1);
                }

                to {
                    box-shadow: 0 12px 24px rgba(11, 69, 184, 0.14);
                    transform: scale(1.04);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .leadership-motion-ready #leadership .leadership-scroll-item,
                .leadership-motion-ready #leadership .leadership-scroll-item .trustee-avatar,
                .leadership-motion-ready.leadership-is-scrolling #leadership .leadership-scroll-item.is-visible > * {
                    opacity: 1;
                    filter: none;
                    transform: none;
                    transition: none;
                    animation: none;
                }
            }
        </style>
    @endpush

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
                                @else
                                    <a href="{{ $portalRegisterUrl }}">Claim Alumni Account</a>
                                    <a href="{{ $portalLoginUrl }}">Open Alumni Dashboard</a>
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
                                        <a href="{{ $portalLoginUrl }}" class="btn btn-light">Alumni Login</a>
                                        <a href="{{ $portalRegisterUrl }}" class="btn btn-outline-light">Create Alumni Account</a>
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
            const carousel = document.getElementById('campusGalleryCarousel');

            if (!carousel) {
                return;
            }

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
                const activeVideo = carousel.querySelector('.carousel-item.active video.campus-gallery-video');

                if (!activeVideo) {
                    return;
                }

                activeVideo.muted = true;
                activeVideo.loop = true;
                activeVideo.playsInline = true;

                try {
                    activeVideo.currentTime = 0;
                } catch (error) {
                    // Ignore seeking before metadata is ready.
                }

                const attempt = activeVideo.play();

                if (attempt && typeof attempt.catch === 'function') {
                    attempt.catch(() => {});
                }
            };

            const syncVideoState = () => {
                pauseVideos();

                if (document.visibilityState === 'visible') {
                    playActiveVideo();
                }
            };

            carousel.addEventListener('slide.bs.carousel', pauseVideos);
            carousel.addEventListener('slid.bs.carousel', playActiveVideo);
            document.addEventListener('visibilitychange', syncVideoState);
            window.addEventListener('pagehide', pauseVideos);
            window.addEventListener('pageshow', syncVideoState);
            window.addEventListener('load', syncVideoState);
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

            const setActiveLink = (sectionId) => {
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

            const matches = (haystack, needle) => normalize(haystack).includes(normalize(needle));

            const getGroupText = (group) => group.getAttribute('data-search-text') || group.textContent || '';
            const getItemText = (item) => item.getAttribute('data-search-text') || item.textContent || '';

            const applySearch = (query, scrollToMatch = false) => {
                const normalizedQuery = normalize(query);
                let firstMatch = null;
                let matchCount = 0;

                groups.forEach((group) => {
                    const items = Array.from(group.querySelectorAll('[data-landing-search-item]'));
                    const groupMatches = normalizedQuery === '' || matches(getGroupText(group), normalizedQuery);

                    if (!normalizedQuery) {
                        group.hidden = false;
                        items.forEach((item) => {
                            item.hidden = false;
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

                    items.forEach((item) => {
                        const itemMatches = matches(getItemText(item), normalizedQuery);
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
                        behavior: 'smooth',
                        block: 'start',
                    });
                }
            };

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    applySearch(input.value, true);
                });
            }

            input.addEventListener('input', function () {
                applySearch(input.value, false);
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
        })();
    </script>
    <script>
        (function () {
            const items = Array.from(document.querySelectorAll('[data-leadership-animate]'));
            const leadership = document.getElementById('leadership');

            if (!items.length || !leadership) {
                return;
            }

            document.documentElement.classList.add('leadership-motion-ready');

            const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            let leadershipIsVisible = false;
            let scrollTimer = null;

            const stopScrollAnimation = () => {
                document.documentElement.classList.remove('leadership-is-scrolling');
            };

            const startScrollAnimation = () => {
                if (!leadershipIsVisible || motionQuery.matches) {
                    stopScrollAnimation();
                    return;
                }

                document.documentElement.classList.add('leadership-is-scrolling');
                window.clearTimeout(scrollTimer);
                scrollTimer = window.setTimeout(stopScrollAnimation, 170);
            };

            if (!('IntersectionObserver' in window)) {
                items.forEach((item) => item.classList.add('is-visible'));
                leadershipIsVisible = true;
                window.addEventListener('scroll', startScrollAnimation, { passive: true });
                return;
            }

            const itemObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    entry.target.classList.toggle('is-visible', entry.isIntersecting);
                });
            }, {
                rootMargin: '0px 0px -12% 0px',
                threshold: 0.18,
            });

            const sectionObserver = new IntersectionObserver((entries) => {
                leadershipIsVisible = entries.some((entry) => entry.isIntersecting);

                if (!leadershipIsVisible) {
                    stopScrollAnimation();
                }
            }, {
                rootMargin: '-12% 0px -12% 0px',
                threshold: 0.12,
            });

            items.forEach((item) => itemObserver.observe(item));
            sectionObserver.observe(leadership);
            window.addEventListener('scroll', startScrollAnimation, { passive: true });
        })();
    </script>
@endpush

@push('styles')
    <style>
        .landing-page .school-identity-banner {
            background: linear-gradient(90deg, #061069 0%, #123cad 46%, #0d75bb 100%);
            color: #fff;
            border-bottom: 3px solid #9c7a00;
            box-shadow: 0 8px 18px rgba(7, 17, 111, 0.16);
        }

        .landing-page .school-identity-lockup {
            color: #fff;
        }

        .landing-page .school-identity-title {
            color: #fff;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.28);
            letter-spacing: 0.02em;
            font-size: clamp(2rem, 5.0vw, 4rem);
            white-space: nowrap;
        }

        .landing-page .school-identity-motto {
            color: #fff;
            letter-spacing: 0.38em;
            text-transform: lowercase;
            font-size: clamp(0.74rem, 1.25vw, 0.95rem);
        }

        .landing-topbar {
            background: #f7fbff;
            color: #07116f;
            border-bottom: 1px solid rgba(7, 17, 111, 0.14);
            font-size: 0.94rem;
            font-weight: 700;
        }

        .landing-topbar-shell {
            min-height: 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .landing-topbar-contact,
        .landing-topbar-actions {
            display: flex;
            align-items: center;
            gap: clamp(0.85rem, 2.5vw, 2rem);
            min-width: 0;
        }

        .landing-topbar-search-form {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            min-width: min(27rem, 100%);
        }

        .landing-topbar-search-input {
            width: clamp(11rem, 18vw, 15rem);
            min-width: 0;
            height: 2.2rem;
            padding: 0.35rem 0.7rem;
            border: 1px solid rgba(7, 17, 111, 0.22);
            border-radius: 0.45rem;
            background: #fff;
            color: #07116f;
            font: inherit;
            font-weight: 700;
        }

        .landing-topbar-search-input::placeholder {
            color: rgba(7, 17, 111, 0.55);
            font-weight: 600;
        }

        .landing-topbar-search-input:focus {
            outline: none;
            border-color: #9c7a00;
            box-shadow: 0 0 0 0.18rem rgba(156, 122, 0, 0.22);
        }

        .landing-topbar-search-button {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            height: 2.2rem;
            padding: 0.35rem 0.8rem;
            border: 1px solid rgba(7, 17, 111, 0.22);
            border-radius: 0.45rem;
            background: #fff;
            color: #07116f;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .landing-topbar-search-button:hover,
        .landing-topbar-search-button:focus-visible {
            color: #fff;
            border-color: #0b45b8;
            background: #0b45b8;
            outline: none;
            box-shadow: 0 0 0 0.18rem rgba(11, 69, 184, 0.22);
        }

        .landing-topbar-search-button:active {
            color: #fff;
            border-color: #0b45b8;
            background: #0b45b8;
            box-shadow: 0 0 0 0.18rem rgba(11, 69, 184, 0.22);
        }

        .landing-topbar-link,
        .landing-topbar-social {
            color: #07116f;
            text-decoration: none;
        }

        .landing-topbar-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }

        .landing-topbar-link span:last-child {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .landing-topbar-icon {
            width: 1.25rem;
            height: 1.25rem;
            display: inline-flex;
            flex: 0 0 auto;
        }

        .landing-topbar-icon svg {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .landing-topbar-social {
            width: 1.75rem;
            height: 1.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-family: Arial, sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .landing-topbar-social .landing-topbar-icon {
            width: 1.15rem;
            height: 1.15rem;
        }

        .landing-topbar-social-x {
            font-size: 1rem;
        }

        .landing-search-matches [data-landing-search-item][hidden],
        [data-landing-search-group][hidden] {
            display: none !important;
        }

        .landing-topbar-link:hover,
        .landing-topbar-social:hover,
        .landing-topbar-link:focus-visible,
        .landing-topbar-social:focus-visible {
            color: #9c7a00;
            outline: none;
        }

        .landing-topbar-link:focus-visible,
        .landing-topbar-social:focus-visible {
            box-shadow: 0 0 0 0.18rem rgba(156, 122, 0, 0.24);
        }

        .landing-topbar .btn-outline-primary {
            color: #07116f;
            border-color: rgba(7, 17, 111, 0.35);
            background: transparent;
        }

        .landing-topbar .btn-outline-primary:hover,
        .landing-topbar .btn-outline-primary:focus-visible {
            color: #fff;
            background: #0b45b8;
            border-color: #0b45b8;
            box-shadow: 0 0 0 0.18rem rgba(11, 69, 184, 0.18);
        }

        .landing-page .school-identity-actions .btn-outline-primary:first-child {
            color: #07116f;
            background: #fff;
            border-color: rgba(7, 17, 111, 0.38);
        }

        .landing-page .school-identity-actions .btn-outline-primary:first-child:hover,
        .landing-page .school-identity-actions .btn-outline-primary:first-child:focus-visible {
            color: #fff;
            background: #0b45b8;
            border-color: #0b45b8;
        }

        .landing-page .school-identity-actions .btn-outline-primary:not(:first-child) {
            color: #07116f;
            background: #fff;
            border-color: rgba(7, 17, 111, 0.38);
        }

        .landing-page .school-identity-actions .btn-outline-primary:not(:first-child):hover,
        .landing-page .school-identity-actions .btn-outline-primary:not(:first-child):focus-visible {
            color: #fff;
            background: #0b45b8;
            border-color: #0b45b8;
        }

        .alumni-post-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(7, 17, 111, 0.12);
            background: #fff;
            box-shadow: 0 14px 28px rgba(7, 17, 111, 0.08);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .alumni-post-card[role="button"] {
            cursor: pointer;
        }

        .alumni-post-card:hover,
        .alumni-post-card:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(11, 69, 184, 0.28);
            box-shadow: 0 20px 32px rgba(7, 17, 111, 0.12);
        }

        .alumni-post-card:hover {
            outline: none;
        }

        .alumni-post-card:focus-visible {
            outline: 3px solid rgba(11, 69, 184, 0.12);
            outline-offset: 2px;
        }

        .announcement-card--summary {
            border: 0;
            border-radius: 0;
            background: #f4f4f4;
            box-shadow: none;
        }

        .announcement-card--summary:hover,
        .announcement-card--summary:focus-visible {
            border-color: transparent;
            box-shadow: 0 12px 24px rgba(11, 69, 184, 0.1);
        }

        .announcement-card--summary .alumni-post-body {
            min-height: 7.85rem;
            gap: 0.72rem;
            justify-content: flex-start;
            padding: 1.25rem 1.35rem;
        }

        .announcement-card--summary .alumni-post-title {
            color: #0b45b8;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            font-size: 1.02rem;
            line-height: 1.28;
            font-weight: 800;
        }

        .announcement-card-byline,
        .announcement-card-views {
            color: #8e949d;
            font-size: 0.82rem;
            line-height: 1.3;
        }

        .announcement-card-byline {
            color: #b5bac1;
        }

        .announcement-card-byline span {
            color: #d3d6db;
            margin: 0 0.25rem;
        }

        .alumni-post-media {
            position: relative;
            aspect-ratio: 16 / 10;
            background: linear-gradient(135deg, #eaf2ff 0%, #f8fbff 100%);
            overflow: hidden;
        }

        .alumni-post-image,
        .alumni-post-video {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .alumni-post-video {
            background: #02083f;
        }

        .alumni-post-placeholder {
            width: 100%;
            height: 100%;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.1), rgba(255,255,255,0.92)),
                linear-gradient(135deg, rgba(11, 69, 184, 0.12), rgba(7, 17, 111, 0.18));
        }

        .alumni-post-placeholder-kicker {
            color: #0b45b8;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .alumni-post-placeholder-title {
            color: #07116f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.35rem;
            line-height: 1.08;
            font-weight: 700;
            margin-top: 0.35rem;
        }

        .alumni-post-body {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            padding: 1rem 1rem 1.1rem;
        }

        .alumni-post-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.28rem 0.55rem;
            border-radius: 999px;
            background: rgba(11, 69, 184, 0.08);
            color: #0b45b8;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .alumni-post-title {
            margin: 0;
            color: #07116f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.28rem;
            line-height: 1.15;
            font-weight: 700;
        }

        .alumni-post-meta {
            color: #6c6f77;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .alumni-post-meta span + span::before {
            content: "•";
            margin: 0 0.4rem;
            color: rgba(7, 17, 111, 0.5);
        }

        .alumni-post-excerpt {
            color: #3d4150;
            font-size: 0.95rem;
            line-height: 1.55;
        }

        .announcement-detail-modal .modal-content,
        .event-detail-modal .modal-content {
            border: 0;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 24px 56px rgba(7, 17, 111, 0.2);
        }

        .announcement-detail-modal .modal-title,
        .event-detail-modal .modal-title {
            color: #07116f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.35rem, 2vw, 1.85rem);
            font-weight: 700;
            line-height: 1.12;
        }

        .announcement-detail-media img,
        .announcement-detail-media video,
        .event-detail-media img,
        .event-detail-media video {
            display: block;
            width: 100%;
            max-height: 60vh;
            border-radius: 0.85rem;
            object-fit: contain;
            background: #f7fbff;
        }

        .announcement-detail-copy {
            color: #2f3340;
            font-size: 1rem;
            line-height: 1.7;
            white-space: normal;
        }

        .event-detail-copy {
            color: #2f3340;
            font-size: 1rem;
            line-height: 1.7;
            white-space: normal;
        }

        .landing-event-summary-card {
            display: flex;
            min-height: 7.3rem;
            border: 0;
            border-radius: 0;
            background: #f4f4f4;
            box-shadow: none;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .landing-event-summary-card:hover,
        .landing-event-summary-card:focus-visible {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(11, 69, 184, 0.1);
            outline: none;
        }

        .landing-event-summary-card:focus-visible {
            outline: 3px solid rgba(11, 69, 184, 0.14);
            outline-offset: 2px;
        }

        .landing-event-summary-body {
            display: flex;
            flex-direction: column;
            gap: 0.72rem;
            justify-content: flex-start;
            width: 100%;
            padding: 1.25rem 1.35rem;
        }

        .landing-event-summary-title {
            margin: 0;
            color: #0b45b8;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            font-size: 1.02rem;
            line-height: 1.28;
            font-weight: 800;
        }

        .landing-event-summary-meta,
        .landing-event-summary-views {
            color: #8e949d;
            font-size: 0.82rem;
            line-height: 1.3;
        }

        .landing-event-summary-meta {
            color: #b5bac1;
        }

        .landing-event-summary-meta span {
            color: #d3d6db;
            margin: 0 0.25rem;
        }

        .landing-board-section {
            background: linear-gradient(180deg, #fff 0%, #f7fbff 100%);
        }

        .landing-board {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            min-height: clamp(24rem, 52vh, 36rem);
            overflow: hidden;
            border: 1px solid rgba(11, 69, 184, 0.18);
            border-radius: 0.5rem;
            background: #fff;
            box-shadow: 0 18px 42px rgba(7, 17, 111, 0.1);
        }

        .landing-board-column {
            min-width: 0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-left: 1px solid rgba(11, 69, 184, 0.16);
        }

        .landing-board-column:first-child {
            border-left: 0;
        }

        .landing-board-header {
            min-height: 3.45rem;
            padding: 0.85rem 1rem 0.75rem;
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 72%, #0a86b7 100%);
            color: #fff;
            text-align: center;
        }

        .landing-board-title {
            font-size: 0.82rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .landing-board-count {
            margin-top: 0.35rem;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.68rem;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
        }

        .landing-board-list {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
            padding: clamp(1rem, 2vw, 1.4rem);
            max-height: clamp(22rem, 48vh, 32rem);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(11, 69, 184, 0.38) transparent;
        }

        .landing-board-list::-webkit-scrollbar {
            width: 0.45rem;
        }

        .landing-board-list::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(11, 69, 184, 0.32);
        }

        .landing-board-item {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .landing-board-card {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            min-height: 5.45rem;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(11, 69, 184, 0.18);
            border-left: 4px solid #0b45b8;
            border-radius: 0.5rem;
            background: #fff;
            color: #07116f;
            text-align: left;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(7, 17, 111, 0.07);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .landing-board-card:hover,
        .landing-board-card:focus-visible {
            transform: translateY(-2px);
            border-color: #0b45b8;
            background: #f7fbff;
            box-shadow: 0 16px 28px rgba(11, 69, 184, 0.13);
            outline: none;
        }

        .landing-board-card:focus-visible {
            outline: 3px solid rgba(11, 69, 184, 0.16);
            outline-offset: 3px;
        }

        .landing-board-card-link {
            cursor: pointer;
        }

        .landing-board-card-title {
            margin: 0;
            color: #07116f;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            font-size: clamp(0.86rem, 1vw, 0.98rem);
            font-weight: 800;
            line-height: 1.22;
            overflow-wrap: anywhere;
            text-transform: uppercase;
        }

        .landing-board-card-meta {
            margin-top: 0.42rem;
            color: #5b6472;
            font-size: clamp(0.72rem, 0.88vw, 0.8rem);
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .landing-board-card-meta span {
            color: #0b45b8;
            margin: 0 0.18rem;
        }

        .landing-board-empty {
            width: 100%;
            padding: 1rem;
            border: 1px dashed rgba(11, 69, 184, 0.35);
            border-radius: 0.5rem;
            background: #fff;
            color: #07116f;
            font-size: 0.82rem;
            font-weight: 700;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .landing-board {
                grid-template-columns: 1fr;
                min-height: 0;
            }

            .landing-board-column,
            .landing-board-column:first-child {
                border-left: 0;
                border-top: 1px solid rgba(11, 69, 184, 0.16);
            }

            .landing-board-column:first-child {
                border-top: 0;
            }

            .landing-board-list {
                gap: 1.2rem;
                max-height: none;
                overflow: visible;
                padding: 1.25rem 1rem 1.75rem;
            }

            .landing-board-card,
            .landing-board-empty {
                width: 100%;
            }
        }

        .alumni-post-link {
            margin-top: auto;
            color: #0b45b8;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .landing-nav {
            background: #eaf4ff;
            border-top: 1px solid rgba(7, 17, 111, 0.1);
            border-bottom: 1px solid rgba(7, 17, 111, 0.1);
        }

        .landing-nav .nav {
            flex-wrap: nowrap !important;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(11, 69, 184, 0.35) transparent;
        }

        .landing-nav .nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.55rem;
            border: 1px solid rgba(7, 17, 111, 0.14);
            background: rgba(255, 255, 255, 0.9);
            color: #07116f;
            border-radius: 0.7rem;
            padding: 0.7rem 1rem;
            box-shadow: 0 8px 16px rgba(7, 17, 111, 0.06);
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
            white-space: nowrap;
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .landing-nav .nav-link:hover,
        .landing-nav .nav-link:focus-visible,
        .landing-nav .nav-link:active,
        .landing-nav .nav-link.active {
            color: #fff;
            background: #0b45b8;
            border-color: #0b45b8;
            box-shadow: 0 0 0 0.18rem rgba(11, 69, 184, 0.18);
            transform: translateY(-1px);
            outline: none;
        }

        .landing-mobile-entry {
            position: relative;
            z-index: 1;
        }

        .landing-mobile-hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-radius: 1.4rem;
            margin-top: 0.25rem;
            background: linear-gradient(112deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            color: #fff;
            box-shadow: 0 18px 34px rgba(4, 0, 120, 0.18);
        }

        .landing-mobile-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(105deg, rgba(7, 17, 111, 0.72) 0%, rgba(11, 69, 184, 0.58) 42%, rgba(10, 134, 183, 0.9) 100%),
                linear-gradient(180deg, rgba(1, 7, 38, 0.08), rgba(1, 7, 38, 0.4));
            pointer-events: none;
        }

        .landing-mobile-hero > :not(.hero-campus-backdrop) {
            position: relative;
            z-index: 2;
        }

        .hero-stage {
            isolation: isolate;
            min-height: auto;
            align-items: flex-start;
            border-radius: 0.45rem;
            background: linear-gradient(112deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            box-shadow: 0 32px 70px rgba(4, 0, 120, 0.24);
            padding: clamp(1.75rem, 3vw, 3rem);
        }

        .hero-stage::before {
            z-index: 1;
            background:
                linear-gradient(102deg, rgba(7, 17, 111, 0.82) 0%, rgba(11, 69, 184, 0.52) 38%, rgba(10, 134, 183, 0.28) 58%, rgba(10, 134, 183, 0.92) 100%),
                linear-gradient(180deg, rgba(1, 7, 38, 0.02), rgba(1, 7, 38, 0.38));
        }

        .hero-stage > .row {
            z-index: 2;
            min-height: auto;
        }

        .hero-campus-gallery {
            width: min(var(--page-width, 95%), var(--page-max-width, 1800px));
            max-width: var(--page-max-width, 1800px);
            margin-left: auto;
            margin-right: auto;
            margin-top: clamp(0.85rem, 1.7vw, 1.25rem) !important;
            min-height: clamp(12rem, 28vh, 17rem);
            aspect-ratio: 16 / 5;
            border-radius: 0.45rem;
            background: linear-gradient(112deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
        }

        .hero-campus-backdrop {
            position: absolute;
            inset: 0;
            z-index: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: left center;
            opacity: 0.95;
            filter: saturate(0.96) contrast(1.02);
            transform: scale(1.01);
        }

        .hero-visual {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(7px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), 0 18px 36px rgba(1, 7, 38, 0.18);
        }

        .community-card {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .landing-mobile-chips {
            display: flex;
            gap: 0.55rem;
            overflow-x: auto;
            padding-bottom: 0.2rem;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .landing-mobile-chips::-webkit-scrollbar {
            display: none;
        }

        .landing-mobile-actions {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1040;
            background: rgba(6, 33, 55, 0.96);
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 -18px 40px rgba(2, 12, 28, 0.24);
        }

        .landing-mobile-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
            padding: 0.55rem 0.15rem calc(0.55rem + env(safe-area-inset-bottom));
        }

        .landing-mobile-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 4.1rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.84);
            text-decoration: none;
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .landing-mobile-action:hover,
        .landing-mobile-action:focus-visible {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
            outline: none;
        }

        .landing-mobile-action-icon {
            width: 1.85rem;
            height: 1.85rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.14);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .landing-mobile-action-label {
            margin-top: 0.32rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .landing-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            padding: 0.6rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(4, 0, 120, 0.14);
            background: rgba(255, 255, 255, 0.84);
            color: var(--ink);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            box-shadow: 0 10px 18px rgba(4, 0, 120, 0.08);
        }

        .landing-chip:hover,
        .landing-chip:focus-visible {
            color: var(--wine);
            outline: none;
        }

        .landing-showcase-stack {
            display: grid;
            gap: clamp(2rem, 3.5vw, 3rem);
        }

        .landing-showcase-panel {
            position: relative;
        }

        .landing-showcase-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .landing-showcase-heading .text-secondary {
            flex: 0 0 auto;
        }

        .landing-showcase-feed {
            display: grid;
            gap: 1.25rem;
        }

        .landing-feed-entry {
            position: relative;
            display: flex;
            align-items: flex-end;
            min-height: clamp(22rem, 40vw, 30rem);
            padding: clamp(1.25rem, 3vw, 2.75rem);
            border-radius: 1.4rem;
            border: 1px solid rgba(4, 0, 120, 0.18);
            overflow: hidden;
            isolation: isolate;
            background: linear-gradient(135deg, #07116f 0%, #02083f 58%, #0b45b8 100%);
            box-shadow: 0 28px 56px rgba(4, 0, 120, 0.18);
        }

        .landing-feed-entry.notice-item,
        .landing-feed-entry.event-card,
        .landing-feed-entry.activity-card {
            border-color: rgba(4, 0, 120, 0.18);
            padding-bottom: clamp(1.25rem, 3vw, 2.75rem);
            margin-bottom: 0;
        }

        .landing-feed-entry::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(3, 11, 27, 0.74) 0%, rgba(3, 11, 27, 0.34) 50%, rgba(3, 11, 27, 0.68) 100%),
                radial-gradient(circle at top right, rgba(10, 134, 183, 0.42), transparent 42%),
            radial-gradient(circle at 20% 85%, rgba(156, 122, 0, 0.12), transparent 28%);
            opacity: 0.95;
            pointer-events: none;
        }

        .landing-feed-entry::after {
            content: '';
            position: absolute;
            inset: auto 0 0 0;
            height: 46%;
            background: linear-gradient(180deg, transparent, rgba(1, 7, 38, 0.48));
            pointer-events: none;
        }

        .landing-feed-entry-shell {
            position: relative;
            z-index: 1;
            width: min(100%, 45rem);
            margin-bottom: 1rem;
            padding: clamp(1rem, 2vw, 1.5rem);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(5, 14, 36, 0.72);
            backdrop-filter: blur(8px);
            box-shadow: 0 14px 30px rgba(2, 5, 20, 0.18);
        }

        .landing-feed-entry .notice-label {
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 0.08em;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .landing-feed-entry h2,
        .landing-feed-entry h3,
        .landing-feed-entry p,
        .landing-feed-entry .small {
            position: relative;
            z-index: 1;
            color: #fff;
        }

        .landing-feed-entry h3 {
            max-width: 16ch;
            font-size: clamp(1.8rem, 4vw, 3.15rem);
            line-height: 1.08;
        }

        .landing-feed-entry p {
            max-width: 38rem;
            font-size: clamp(0.98rem, 1.2vw, 1.08rem);
            line-height: 1.65;
        }

        .landing-feed-entry .text-secondary {
            color: rgba(255, 255, 255, 0.78) !important;
        }

        .landing-feed-entry .landing-story-media {
            position: absolute;
            inset: 0;
            margin: 0;
            border-radius: 0;
            overflow: hidden;
            border: 0;
            opacity: 0.72;
            z-index: 0;
            box-shadow: none;
        }

        .landing-feed-entry .landing-story-media-asset {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(0.98) contrast(0.96);
        }

        .landing-feed-entry .landing-story-media-video {
            pointer-events: auto;
        }

        .campus-gallery-video {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            background: #02083f;
        }

        @media (max-width: 767.98px) {
            .landing-header {
                position: sticky;
                top: 0;
                z-index: 1036;
            }

            .landing-topbar-shell {
                display: grid;
                grid-template-columns: 1fr;
                min-height: auto;
                padding-top: 0.65rem;
                padding-bottom: 0.65rem;
                align-items: stretch;
                gap: 0.55rem;
            }

            .landing-topbar-contact {
                width: 100%;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem 0.85rem;
                font-size: 0.78rem;
                line-height: 1.2;
            }

            .landing-topbar-actions {
                width: 100%;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto auto auto;
                align-items: center;
                gap: 0.4rem;
            }

            .landing-topbar-search-form {
                min-width: 0;
                width: 100%;
                display: grid;
                grid-template-columns: minmax(0, 1fr) 2.45rem;
                gap: 0.4rem;
            }

            .landing-topbar-search-input {
                width: 100%;
                height: 2.25rem;
                font-size: 0.78rem;
            }

            .landing-topbar-search-button span:last-child {
                display: none;
            }

            .landing-topbar-search-button {
                width: 2.45rem;
                min-width: 2.45rem;
                height: 2.25rem;
                justify-content: center;
                padding: 0;
            }

            .landing-topbar-social {
                width: 2rem;
                height: 2rem;
                font-size: 1rem;
            }

            .landing-page .school-identity-shell {
                gap: 0.75rem;
                min-height: 3.75rem;
                align-items: stretch;
            }

            .landing-page .school-identity-lockup {
                width: 100%;
                align-items: center;
                gap: 0.65rem;
            }

            .landing-page .school-identity-copy {
                flex: 1 1 auto;
                min-width: 0;
            }

            .landing-page .school-identity-actions {
                width: 100%;
            }

            .landing-page .school-identity-actions .btn {
                padding: 0.48rem 0.8rem;
            }

            .landing-page .school-identity-crest {
                width: 2.65rem;
                height: 2.65rem;
                font-size: 0.82rem;
            }

            .landing-page .school-identity-title {
                font-size: clamp(1.28rem, 6vw, 1.72rem);
                line-height: 1.02;
                max-width: 100%;
                white-space: normal;
                overflow-wrap: normal;
            }

            .landing-page .school-identity-motto {
                margin-top: 0.2rem;
                font-size: clamp(0.58rem, 2.4vw, 0.68rem);
                color: #fff;
                letter-spacing: 0.16em;
            }

            .brand-lockup {
                align-items: flex-start;
            }

            .landing-nav .nav {
                gap: 0.5rem;
                justify-content: flex-start;
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding: 0.7rem 0 0.2rem;
            }

            .landing-nav .nav::-webkit-scrollbar {
                display: none;
            }

            .landing-nav .nav-link {
                background: rgba(255, 255, 255, 0.72);
                border: 1px solid rgba(7, 17, 111, 0.12);
                color: #07116f;
            }

            .landing-nav .nav-link:hover,
            .landing-nav .nav-link:focus-visible,
            .landing-nav .nav-link:active,
            .landing-nav .nav-link.active {
                color: #fff;
                background: #0b45b8;
                border-color: #0b45b8;
            }

            .landing-page .campus-gallery-hero {
                width: 100%;
                min-height: 0;
                height: auto;
                aspect-ratio: 4 / 3;
                border-radius: 1rem;
            }

            .landing-page .hero-campus-gallery {
                margin-top: 1rem !important;
            }

            .landing-page .campus-gallery-carousel,
            .landing-page .campus-gallery-carousel .carousel-inner,
            .landing-page .campus-gallery-carousel .carousel-item {
                height: 100%;
                min-height: 0;
            }

            .landing-page .campus-gallery-image,
            .landing-page .campus-gallery-video {
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center center;
            }

            .landing-page .campus-gallery-caption {
                left: 0.65rem;
                right: 0.65rem;
                bottom: 0.65rem;
                max-width: calc(100% - 1.3rem);
                padding: 0.75rem 0.8rem;
                border-radius: 0.75rem;
            }

            .landing-page .campus-gallery-kicker {
                margin-bottom: 0.35rem;
                font-size: 0.58rem;
                letter-spacing: 0.08em;
            }

            .landing-page .campus-gallery-title {
                margin-bottom: 0.35rem;
                font-size: clamp(1rem, 4.8vw, 1.35rem);
                line-height: 1.08;
            }

            .landing-page .campus-gallery-detail {
                font-size: 0.78rem;
                line-height: 1.35;
            }

            .landing-page .campus-carousel-indicators {
                margin-bottom: 0.35rem;
            }

            .landing-page .campus-carousel-indicators [data-bs-target] {
                width: 1.35rem;
                height: 0.2rem;
            }

            .landing-page .landing-mobile-hero-head {
                display: grid !important;
                grid-template-columns: 1fr;
                gap: 0.55rem;
            }

            .landing-page .landing-mobile-hero .min-w-0 {
                width: 100%;
            }

            .landing-page .landing-mobile-hero .hero-badge {
                justify-content: center;
                width: min(100%, 25rem);
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
                box-sizing: border-box;
                min-height: 2.1rem;
                margin-bottom: 0.6rem !important;
                padding: 0.45rem 0.72rem;
                border-radius: 0.9rem;
                font-size: clamp(0.68rem, 2.8vw, 0.78rem);
                line-height: 1.15;
                letter-spacing: 0.04em;
                text-align: center;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .landing-page .landing-mobile-hero .mobile-portal-badge {
                justify-content: center;
                width: min(100%, 28rem);
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
                box-sizing: border-box;
                min-height: 2.35rem;
                margin-bottom: 0.75rem;
                padding: 0.5rem 0.75rem;
                border-radius: 0.85rem;
                font-size: clamp(0.64rem, 2.6vw, 0.76rem);
                line-height: 1.2;
                letter-spacing: 0.05em;
                text-align: center;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .landing-section {
                padding: 2.25rem 0;
            }

            .landing-page {
                padding-bottom: 6.75rem;
            }

            .page-card,
            .event-card,
            .activity-card,
            .team-card,
            .trustee-card,
            .process-card {
                border-radius: 1rem;
            }

            .landing-feed-entry {
                min-height: 20rem;
                padding: 1rem;
            }

            .landing-showcase-stack {
                gap: 2rem;
            }

            .landing-showcase-heading {
                display: block;
                margin-bottom: 0.85rem;
            }

            .landing-showcase-heading .text-secondary {
                margin-top: 0.35rem;
            }

            .landing-feed-entry-shell {
                width: min(100%, 100%);
                margin-bottom: 0.35rem;
                padding: 0.9rem 1rem;
            }

            .landing-feed-entry h3 {
                max-width: 100%;
                font-size: 1.35rem;
                line-height: 1.18;
            }

            .landing-feed-entry p {
                font-size: 0.92rem;
                line-height: 1.55;
            }

            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush
