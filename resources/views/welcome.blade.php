@extends('layouts.app')

@section('title', 'St. Bridget College Batangas Alumni Link')
@section('full_guest', true)

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
        $heroBuildingSlide = null;

        foreach ($photoSlides as $slide) {
            if (($slide['type'] ?? 'photo') === 'photo' && ! empty($slide['url'])) {
                $heroBuildingSlide = $slide;
                break;
            }
        }

        $sbcLogoPath = null;
        foreach (['images/sbc-logo.png', 'images/sbc-logo.jpg', 'images/sbc-logo.jpeg', 'images/sbc-logo.webp', 'images/sbc-logo.svg'] as $candidate) {
            if (is_file(public_path($candidate))) {
                $sbcLogoPath = $candidate;
                break;
            }
        }
        $hasSbcLogo = is_string($sbcLogoPath);
    @endphp

    <div class="landing-page">
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
                        @auth
                            <a href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('portal.dashboard') }}" class="btn btn-outline-primary">Open Dashboard</a>
                        @else
                            <a href="{{ route('portal.login', ['switch' => 1]) }}" class="btn btn-outline-primary">Alumni Login</a>
                            <a href="{{ route('portal.register') }}" class="btn btn-outline-primary">Claim Alumni Account</a>
                        @endauth
                        </div>
                    </div>
                </div>
            </div>

            <div class="landing-nav">
                <div class="main-wrapper">
                    <div class="nav flex-nowrap flex-lg-wrap">
                        <a class="nav-link" href="#home">Home</a>
                        <a class="nav-link" href="#about">About</a>
                        <a class="nav-link" href="#campus-gallery">Campus Gallery</a>
                        <a class="nav-link" href="#alumni-feed">Alumni Feed</a>
                        <a class="nav-link" href="#updates">Announcements</a>
                        <a class="nav-link" href="#leadership">Board of Trustees</a>
                        <a class="nav-link" href="#alumni-office">Alumni Office</a>
                        <a class="nav-link" href="#contact">Contact</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="main-wrapper">
            <div class="event-card p-3 mt-3 landing-search-empty" hidden data-landing-search-empty>
                No landing page matches found. Try an alumni post, announcement, officer name, contact detail, or a campus keyword.
            </div>
        </div>

        <section class="landing-mobile-entry d-lg-none">
            <div class="main-wrapper">
                <div class="landing-mobile-hero p-3 mt-3">
                    @if ($heroBuildingSlide)
                        <img
                            src="{{ $heroBuildingSlide['url'] }}"
                            alt="{{ $heroBuildingSlide['title'] ?: 'St. Bridget College Batangas campus building' }}"
                            class="hero-campus-backdrop">
                    @else
                        <div class="hero-campus-building" aria-hidden="true"></div>
                    @endif
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="min-w-0">
                            <div class="hero-badge mb-2">{{ $hero['eyebrow'] }}</div>
                            <h2 class="h3 mb-2">{{ $hero['title'] }}</h2>
                            <p class="mb-0 text-white-50">{{ $hero['summary'] }}</p>
                        </div>
                        <div class="mobile-portal-badge">{{ $brand['school'] }}</div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ route('portal.login', ['switch' => 1]) }}" class="btn btn-light btn-lg">Open Alumni Portal</a>
                        <a href="{{ route('portal.register') }}" class="btn btn-outline-light">Claim Alumni Account</a>
                        <button type="button" class="btn btn-outline-light d-none" data-install-app>Install App</button>
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
                    <a href="{{ route('portal.login', ['switch' => 1]) }}" class="landing-chip">Login</a>
                    <a href="{{ route('portal.register') }}" class="landing-chip">Register</a>
                </div>
            </div>
        </section>

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

        <section id="home" class="landing-section hero-section pt-3 pt-lg-5">
                <div class="hero-stage reveal d-none d-lg-block">
                    @if ($heroBuildingSlide)
                        <img
                            src="{{ $heroBuildingSlide['url'] }}"
                            alt="{{ $heroBuildingSlide['title'] ?: 'St. Bridget College Batangas campus building' }}"
                            class="hero-campus-backdrop">
                    @else
                        <div class="hero-campus-building" aria-hidden="true"></div>
                    @endif
                    <div class="row gx-0 align-items-stretch position-relative hero-columns">
                        <div class="col-lg-12 hero-left-panel">
                            <div class="hero-badge">{{ $hero['eyebrow'] }}</div>
                            <h2 class="hero-heading">{{ $hero['title'] }}</h2>
                            <p class="hero-copy mb-4">{{ $hero['summary'] }}</p>
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <a href="{{ route('portal.login', ['switch' => 1]) }}" class="btn btn-light btn-lg">Open Alumni Dashboard</a>
                                <a href="{{ route('portal.register') }}" class="btn btn-outline-light btn-lg">Register Alumni Account</a>
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

                <div id="campus-gallery" class="campus-gallery-hero reveal mt-3 mt-lg-4" data-landing-search-group data-search-text="Campus Gallery St. Bridget College photos videos campus story">
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

        <section id="alumni-feed" class="landing-section pt-0" data-landing-search-group data-search-text="SBC Alumni Feed recent alumni stories campus moments Bridgetine updates">
            <div class="main-wrapper">
                <div class="row g-4 align-items-end mb-3">
                    <div class="col-lg-8 reveal">
                        <div class="section-eyebrow">SBC Alumni Feed</div>
                        <h2 class="section-title">Recent alumni stories, campus moments, and Bridgetine updates.</h2>
                        <p class="section-copy">Tap a post to read the full story, view the date, and see how many readers have opened it.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end reveal">
                        <div class="small text-secondary">{{ $alumniPostTotal }} alumni posts published</div>
                    </div>
                </div>

                <div class="row g-4">
                    @forelse ($activities as $activity)
                        <div class="col-md-6 col-xl-4 reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower(($activity['theme'] ?? '').' '.($activity['title'] ?? '').' '.($activity['description'] ?? '').' '.($activity['location'] ?? '').' '.(isset($activity['activity_date']) ? \Illuminate\Support\Carbon::parse($activity['activity_date'])->format('F d, Y') : '')) }}">
                            <a href="{{ $activity['show_url'] }}" class="alumni-post-card text-decoration-none">
                                <div class="alumni-post-media">
                                    @if (! empty($activity['media_url']))
                                        @if (($activity['media_type'] ?? null) === 'image')
                                            <img src="{{ $activity['media_url'] }}" alt="{{ $activity['title'] }}" class="alumni-post-image">
                                        @elseif (($activity['media_type'] ?? null) === 'video')
                                            <video class="alumni-post-image alumni-post-video" muted playsinline preload="metadata">
                                                <source src="{{ $activity['media_url'] }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @else
                                        <div class="alumni-post-placeholder">
                                            <div class="alumni-post-placeholder-kicker">St. Bridget College</div>
                                            <div class="alumni-post-placeholder-title">{{ $activity['theme'] ?: 'Alumni Story' }}</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="alumni-post-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="alumni-post-badge">{{ $activity['theme'] ?: 'SBC Alumni Post' }}</div>
                                        @php
                                            $activityViews = (int) ($activity['views_count'] ?? 0);
                                        @endphp
                                        <div class="alumni-post-meta">{{ number_format($activityViews) }} {{ $activityViews === 1 ? 'view' : 'views' }}</div>
                                    </div>
                                    <h3 class="alumni-post-title">{{ $activity['title'] }}</h3>
                                    <div class="alumni-post-meta mb-2">
                                        @if (! empty($activity['activity_date']))
                                            <span>{{ \Illuminate\Support\Carbon::parse($activity['activity_date'])->format('F d, Y') }}</span>
                                        @endif
                                        @if (! empty($activity['location']))
                                            <span>{{ $activity['location'] }}</span>
                                        @endif
                                    </div>
                                    <p class="alumni-post-excerpt mb-3">{{ \Illuminate\Support\Str::limit($activity['description'], 160) }}</p>
                                    <div class="alumni-post-link">Open post</div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="event-card p-4">
                                <h3 class="h5 mb-2">No alumni posts yet.</h3>
                                <p class="text-secondary mb-0">Administrators can publish SBC alumni stories from the activity manager, and they will appear here automatically.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="updates" class="landing-section pt-0" data-landing-search-group data-search-text="Announcements school notices official updates records community Bridgetine announcements">
            <div class="main-wrapper">
                <div class="row g-4 align-items-end mb-3">
                    <div class="col-lg-8 reveal">
                        <div class="section-eyebrow">Announcements</div>
                        <h2 class="section-title">Official school notices and alumni announcements.</h2>
                        <p class="section-copy">Published admin announcements appear here automatically for visitors and alumni to read.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end reveal">
                        <div class="small text-secondary">
                            {{ $announcementTotal }} {{ $announcementTotal === 1 ? 'announcement' : 'announcements' }} published
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    @forelse ($announcements as $announcement)
                        @php
                            $announcementLabel = $announcement['label'] ?? 'Announcement';
                            $announcementTitle = $announcement['title'] ?? '';
                            $announcementDescription = $announcement['description'] ?? '';
                            $announcementPublishedAt = $announcement['published_at'] ?? null;
                        @endphp
                        <div class="col-md-6 col-xl-4 reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($announcementLabel.' '.$announcementTitle.' '.$announcementDescription.' '.($announcementPublishedAt ? \Illuminate\Support\Carbon::parse($announcementPublishedAt)->format('F d, Y') : '')) }}">
                            <div class="alumni-post-card announcement-card h-100">
                                <div class="alumni-post-media">
                                    @if (! empty($announcement['media_url']))
                                        @if (($announcement['media_type'] ?? null) === 'image')
                                            <img src="{{ $announcement['media_url'] }}" alt="{{ $announcementTitle }}" class="alumni-post-image">
                                        @elseif (($announcement['media_type'] ?? null) === 'video')
                                            <video class="alumni-post-image alumni-post-video" controls playsinline preload="metadata">
                                                <source src="{{ $announcement['media_url'] }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    @else
                                        <div class="alumni-post-placeholder">
                                            <div class="alumni-post-placeholder-kicker">St. Bridget College</div>
                                            <div class="alumni-post-placeholder-title">{{ $announcementLabel ?: 'Official Update' }}</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="alumni-post-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="alumni-post-badge">{{ $announcementLabel ?: 'Announcement' }}</div>
                                        @if ($announcementPublishedAt)
                                            <div class="alumni-post-meta">{{ \Illuminate\Support\Carbon::parse($announcementPublishedAt)->format('M d, Y') }}</div>
                                        @endif
                                    </div>
                                    <h3 class="alumni-post-title">{{ $announcementTitle }}</h3>
                                    <p class="alumni-post-excerpt mb-0">{{ \Illuminate\Support\Str::limit($announcementDescription, 180) }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="event-card p-4">
                                <h3 class="h5 mb-2">No announcements yet.</h3>
                                <p class="text-secondary mb-0">Administrators can publish announcements from the admin workspace, and they will appear here automatically.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="leadership" class="landing-section" data-landing-search-group data-search-text="Board of Trustees school leadership St. Bridget College Batangas">
            <div class="main-wrapper">
                <div class="row g-4 align-items-end mb-3">
                    <div class="col-lg-8 reveal">
                        <div class="section-eyebrow">School Leadership</div>
                        <h2 class="section-title">Board of Trustees of St. Bridget College Batangas</h2>
                    </div>
                </div>

                <div class="row g-4">
                    @foreach ($boardMembers as $member)
                        <div class="col-md-6 col-xl-4 reveal" data-landing-search-item data-search-text="{{ \Illuminate\Support\Str::lower($member['name'].' '.$member['role']) }}">
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
                                <a href="{{ route('portal.register') }}">Claim Alumni Account</a>
                                <a href="{{ route('portal.login', ['switch' => 1]) }}">Open Alumni Dashboard</a>
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
                                    <a href="{{ route('portal.login', ['switch' => 1]) }}" class="btn btn-light">Alumni Login</a>
                                    <a href="{{ route('portal.register') }}" class="btn btn-outline-light">Create Alumni Account</a>
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
@endpush

@push('styles')
    <style>
        .landing-page .school-identity-banner {
            background: #fff;
            color: #07116f;
            border-bottom: 1px solid rgba(7, 17, 111, 0.12);
            box-shadow: 0 8px 18px rgba(7, 17, 111, 0.08);
        }

        .landing-page .school-identity-lockup {
            color: #07116f;
        }

        .landing-page .school-identity-title {
            color: #07116f;
            text-shadow: none;
            letter-spacing: 0.02em;
            font-size: clamp(2rem, 5.0vw, 4rem);
            white-space: nowrap;
        }

        .landing-page .school-identity-motto {
            color: #e5cd1d;
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

        .alumni-post-card:hover,
        .alumni-post-card:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(11, 69, 184, 0.28);
            box-shadow: 0 20px 32px rgba(7, 17, 111, 0.12);
            outline: none;
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
            flex-wrap: wrap;
            justify-content: center;
        }

        .landing-nav .nav-link {
            color: #07116f;
            border-radius: 0.7rem;
            padding: 0.7rem 1rem;
            transition: color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .landing-nav .nav-link:hover,
        .landing-nav .nav-link:focus-visible,
        .landing-nav .nav-link:active,
        .landing-nav .nav-link.active {
            color: #fff;
            background: #0b45b8;
            box-shadow: 0 0 0 0.18rem rgba(11, 69, 184, 0.18);
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
                min-height: auto;
                padding-top: 0.65rem;
                padding-bottom: 0.65rem;
                align-items: flex-start;
            }

            .landing-topbar-contact {
                flex: 1 1 auto;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.45rem;
            }

            .landing-topbar-actions {
                flex: 0 0 auto;
                gap: 0.5rem;
            }

            .landing-topbar-search-form {
                min-width: 0;
                width: min(100%, 17rem);
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .landing-topbar-search-input {
                width: min(100%, 11rem);
                flex: 1 1 11rem;
            }

            .landing-topbar-search-button span:last-child {
                display: none;
            }

            .school-identity-shell {
                gap: 0.75rem;
                min-height: 3.75rem;
            }

            .school-identity-actions {
                width: 100%;
            }

            .school-identity-actions .btn {
                padding: 0.48rem 0.8rem;
            }

            .school-identity-crest {
                width: 2.65rem;
                height: 2.65rem;
                font-size: 0.82rem;
            }

            .school-identity-title {
                font-size: clamp(1.35rem, 7.4vw, 2.1rem);
                white-space: normal;
            }

            .school-identity-motto {
                margin-top: 0.2rem;
                font-size: 0.68rem;
                color: #e5cd1d;
                letter-spacing: 0.24em;
            }

            .brand-lockup {
                align-items: flex-start;
            }

            .landing-nav .nav {
                gap: 0.5rem;
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding-bottom: 0.1rem;
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

            .campus-gallery-hero {
                border-radius: 1.35rem;
                min-height: 210px;
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
