<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="fcm-token-url" content="{{ route('fcm-token.store') }}">
    @endauth
    <meta name="firebase-messaging-sw-url" content="{{ asset('firebase-messaging-sw.js') }}">
    <meta name="notification-icon-url" content="{{ asset('icons/icon-192.png') }}">
    <meta name="theme-color" content="#07116f">
    <meta name="application-name" content="Alumni Link">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Alumni Link">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', 'Alumni Link')</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('images/favicon-32.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('images/pwa-icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}?v=5.3.3" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shell.css') }}?v={{ filemtime(public_path('css/shell.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/desktop.css') }}?v={{ filemtime(public_path('css/desktop.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/interface.css') }}?v={{ filemtime(public_path('css/interface.css')) }}">
    @stack('styles')
</head>
@php
    $layoutUser = auth()->user();
    $fullGuest = View::hasSection('full_guest');
    $workspacePreview = View::hasSection('workspace_preview');
    $centeredGuest = View::hasSection('centered_guest');
    $schoolGuestHeader = View::hasSection('school_guest_header');
    $showWorkspaceShell = $layoutUser && ! $fullGuest;
@endphp
<body class="{{ $showWorkspaceShell ? 'workspace-shell' : 'public-shell' }} {{ $showWorkspaceShell && $layoutUser?->isAdmin() ? 'admin-workspace' : '' }}">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    @if ($showWorkspaceShell)
        @php
            $pendingAccountSummary = $layoutUser->isAdmin()
                ? rescue(fn () => \App\Models\User::query()->where('role', 'alumni')->where('account_status', 'pending')->selectRaw('count(*) as total, max(id) as latest_id')->first(), null, false)
                : null;
            $pendingAccountCount = (int) ($pendingAccountSummary?->total ?? 0);
            $latestPendingAccountId = (int) ($pendingAccountSummary?->latest_id ?? 0);
            $pendingRecordRequestSummary = $layoutUser->isAdmin()
                ? rescue(fn () => \App\Models\RecordRequest::query()->where('status', 'pending')->selectRaw('count(*) as total, max(id) as latest_id')->first(), null, false)
                : null;
            $pendingRecordRequestCount = (int) ($pendingRecordRequestSummary?->total ?? 0);
            $latestPendingRecordRequestId = (int) ($pendingRecordRequestSummary?->latest_id ?? 0);
            $pendingEventRegistrationCount = $layoutUser->isAdmin()
                ? rescue(fn () => \App\Models\EventRegistration::query()->where('status', \App\Models\EventRegistration::STATUS_PENDING)->count(), 0, false)
                : 0;
            $eventsMenuOpen = request()->routeIs('events.*', 'event-registrations.*');
            $latestAlumniRequestUpdateTimestamp = $layoutUser->isAlumni()
                ? rescue(function () use ($layoutUser) {
                    $latestUpdate = \App\Models\RecordRequest::query()
                        ->where('alumni_id', $layoutUser->alumni_id)
                        ->whereNotNull('admin_replied_at')
                        ->max('admin_replied_at');

                    return $latestUpdate ? \Illuminate\Support\Carbon::parse($latestUpdate)->timestamp : 0;
                }, 0, false)
                : 0;
        @endphp
        @php
            $sbcLogoPath = null;
            $hasSbcLogo = false;

            foreach (['images/sbc-logo.png', 'images/sbc-logo.jpg', 'images/sbc-logo.jpeg', 'images/sbc-logo.webp', 'images/sbc-logo.svg'] as $candidate) {
                if (is_file(public_path($candidate))) {
                    $sbcLogoPath = $candidate;
                    break;
                }
            }

            $hasSbcLogo = is_string($sbcLogoPath);
        @endphp
        <div class="mobile-toolbar d-lg-none sticky-top">
            <div class="main-wrapper py-2 d-flex align-items-center justify-content-between gap-2">
                <button type="button" class="btn btn-outline-light mobile-menu-trigger" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation menu"><x-ui-icon name="menu" /></button>
                <a href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('portal.dashboard') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none min-w-0">
                    <div class="brand-crest school-system-brand-crest {{ ($hasSbcLogo ?? false) ? 'brand-crest-logo' : '' }}">
                        @if ($hasSbcLogo ?? false)
                            <img src="{{ asset($sbcLogoPath) }}" alt="St. Bridget College Batangas Logo">
                        @else
                            SBC
                        @endif
                    </div>
                    <div class="min-w-0">
                        @if (auth()->user()->isAdmin())
                            <div class="small text-uppercase text-white-50">Alumni Link</div>
                        @endif
                        <div class="fw-semibold text-truncate">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Alumni Portal' }}</div>
                    </div>
                </a>
                @if (auth()->user()->isAdmin())
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-light">Home</a>
                        <a href="{{ route('home', ['preview' => 1]) }}" class="btn btn-sm btn-outline-light">Landing</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="offcanvas offcanvas-start offcanvas-mobile-nav d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom border-white border-opacity-10">
                <div>
                    @if (auth()->user()->isAdmin())
                        <div class="brand-pill mb-2">Records Admin</div>
                    @endif
                    <h2 id="mobileSidebarLabel" class="h5 fw-bold mb-0">{{ auth()->user()->isAdmin() ? 'Admin Workspace' : 'Alumni Portal' }}</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column gap-4">
                @if (! auth()->user()->isAdmin())
                    <div class="page-card p-3 bg-transparent border border-white border-opacity-10">
                        <div class="small text-white-50 text-uppercase fw-semibold mb-2">Quick access</div>
                <div class="d-grid gap-2">
                    <a href="{{ route('portal.requests.index') }}" class="btn btn-light">My Requests</a>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-light">Update Profile</a>
                </div>
            </div>
        @endif

                <nav class="nav flex-column gap-2">
                    <a href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('portal.dashboard') }}" class="nav-link {{ request()->routeIs(auth()->user()->isAdmin() ? 'dashboard' : 'portal.dashboard') ? 'active' : '' }}">Dashboard</a>

                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('alumni.index') }}" class="nav-link {{ request()->routeIs('alumni.*') ? 'active' : '' }}">Alumni Records</a>
                        <a href="{{ route('users.pending') }}" class="nav-link {{ request()->routeIs('users.pending') ? 'active' : '' }}">
                            Pending Accounts
                            <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingAccountCount ?? 0) > 0 ? '' : 'd-none' }}" data-pending-account-badge>{{ $pendingAccountCount }}</span>
                        </a>
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index', 'users.edit', 'users.update', 'users.approve', 'users.reject') ? 'active' : '' }}">User Accounts</a>
                        <a href="{{ route('requests.index') }}" class="nav-link {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                            Record Requests
                            <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingRecordRequestCount ?? 0) > 0 ? '' : 'd-none' }}" data-pending-record-request-badge>{{ $pendingRecordRequestCount }}</span>
                        </a>
                        <div class="sidebar-nav-group">
                            <div class="nav-link sidebar-nav-toggle {{ $eventsMenuOpen ? 'active' : '' }}">
                                <span>Events</span>
                                <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingEventRegistrationCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $pendingEventRegistrationCount }}</span>
                            </div>
                            <div class="sidebar-subnav">
                                <a href="{{ route('events.index') }}" class="nav-link sidebar-subnav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">Event Posts</a>
                                <a href="{{ route('event-registrations.index') }}" class="nav-link sidebar-subnav-link {{ request()->routeIs('event-registrations.*') ? 'active' : '' }}">
                                    Registrants
                                    <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingEventRegistrationCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $pendingEventRegistrationCount }}</span>
                                </a>
                            </div>
                        </div>
                        <a href="{{ route('announcements.index') }}" class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">Announcements</a>
                        <a href="{{ route('activities.index') }}" class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}">Activities</a>
                        <a href="{{ route('admin.settings.landing-video.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.landing-video.*', 'admin.settings.landing-profiles.*') ? 'active' : '' }}">Alumni Administration</a>
                    @else
                        <a href="{{ route('portal.requests.index') }}" class="nav-link {{ request()->routeIs('portal.requests.*') ? 'active' : '' }}">My Requests</a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profile</a>
                </nav>

                <div class="page-card p-3 bg-transparent border border-white border-opacity-10">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="user-avatar">
                            @if (auth()->user()->profile_photo_url)
                                <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}">
                            @else
                                <div class="user-avatar-placeholder">{{ auth()->user()->initials }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="small text-white-50">Signed in as</div>
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                        </div>
                    </div>
                    <div class="small text-white-50 mb-3">{{ auth()->user()->email }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-light w-100">Log out</button>
                    </form>
                </div>
            </div>
        </div>

        @if (! auth()->user()->isAdmin())
            <div class="mobile-portal-dock d-lg-none">
                <div class="main-wrapper">
                    <div class="mobile-portal-dock-grid">
                        <a href="{{ route('home') }}" class="mobile-portal-dock-item {{ request()->routeIs('home') ? 'active' : '' }}">
                            <span class="mobile-portal-dock-icon"><x-ui-icon name="home" /></span>
                            <span class="mobile-portal-dock-label">Home</span>
                        </a>
                        <a href="{{ route('portal.requests.index') }}" class="mobile-portal-dock-item {{ request()->routeIs('portal.requests.*') ? 'active' : '' }}">
                            <span class="mobile-portal-dock-icon"><x-ui-icon name="file" /></span>
                            <span class="mobile-portal-dock-label">Requests</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="mobile-portal-dock-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <span class="mobile-portal-dock-icon"><x-ui-icon name="user" /></span>
                            <span class="mobile-portal-dock-label">Profile</span>
                        </a>
                        <button
                            type="button"
                            class="mobile-portal-dock-button"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#mobileSidebar"
                            aria-controls="mobileSidebar"
                        >
                            <span class="mobile-portal-dock-icon"><x-ui-icon name="menu" /></span>
                            <span class="mobile-portal-dock-label">Menu</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <div class="school-system-bar d-none d-lg-flex align-items-center">
            <div class="main-wrapper d-flex align-items-center justify-content-between gap-4">
                <a href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('portal.dashboard') }}" class="d-flex align-items-center gap-3 text-white text-decoration-none">
                    <div class="brand-crest school-system-brand-crest {{ ($hasSbcLogo ?? false) ? 'brand-crest-logo' : '' }}">
                        @if ($hasSbcLogo ?? false)
                            <img src="{{ asset($sbcLogoPath) }}" alt="St. Bridget College Batangas Logo">
                        @else
                            SBC
                        @endif
                    </div>
                    <div>
                        <div class="school-system-title">St. Bridget College</div>
                        <div class="school-system-subtitle">luceat lux vestra</div>
                    </div>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="h4 fw-bold mb-0">Alumni Link</div>
                        <div class="small text-white-50 text-uppercase">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Alumni Portal' }}</div>
                    </div>
                    <a href="{{ auth()->user()->isAdmin() ? route('home', ['preview' => 1]) : route('home') }}" class="btn btn-light">Home Page</a>
                </div>
            </div>
        </div>

        <div class="app-workspace-fluid">
            <div class="main-wrapper app-workspace-wrapper">
                <div class="row min-vh-100">
                <aside class="sidebar {{ auth()->user()->isAdmin() ? 'sidebar-admin' : 'sidebar-portal' }} d-none d-lg-flex col-lg-3 col-xl-2 p-4 flex-column gap-4">
                    <div>
                        @if (auth()->user()->isAdmin())
                            <div class="brand-pill mb-3">Records Admin</div>
                        @endif
                        <h2 class="h4 fw-bold mb-2">{{ auth()->user()->isAdmin() ? 'Admin Workspace' : 'Alumni Portal' }}</h2>
                        @if (auth()->user()->isAdmin())
                            <p class="mb-0 text-white-50 small">
                                Manage imports, requests, and event postings for alumni services.
                            </p>
                        @endif
                    </div>

                    <nav class="nav flex-column gap-2">
                        <a href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('portal.dashboard') }}" class="nav-link {{ request()->routeIs(auth()->user()->isAdmin() ? 'dashboard' : 'portal.dashboard') ? 'active' : '' }}">Dashboard</a>

                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('alumni.index') }}" class="nav-link {{ request()->routeIs('alumni.*') ? 'active' : '' }}">Alumni Records</a>
                        <a href="{{ route('users.pending') }}" class="nav-link {{ request()->routeIs('users.pending') ? 'active' : '' }}">
                            Pending Accounts
                            <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingAccountCount ?? 0) > 0 ? '' : 'd-none' }}" data-pending-account-badge>{{ $pendingAccountCount }}</span>
                        </a>
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index', 'users.edit', 'users.update', 'users.approve', 'users.reject') ? 'active' : '' }}">User Accounts</a>
                            <a href="{{ route('requests.index') }}" class="nav-link {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                                Record Requests
                                <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingRecordRequestCount ?? 0) > 0 ? '' : 'd-none' }}" data-pending-record-request-badge>{{ $pendingRecordRequestCount }}</span>
                            </a>
                            <div class="sidebar-nav-group">
                                <div class="nav-link sidebar-nav-toggle {{ $eventsMenuOpen ? 'active' : '' }}">
                                    <span>Events</span>
                                    <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingEventRegistrationCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $pendingEventRegistrationCount }}</span>
                                </div>
                                <div class="sidebar-subnav">
                                    <a href="{{ route('events.index') }}" class="nav-link sidebar-subnav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">Event Posts</a>
                                    <a href="{{ route('event-registrations.index') }}" class="nav-link sidebar-subnav-link {{ request()->routeIs('event-registrations.*') ? 'active' : '' }}">
                                        Registrants
                                        <span class="badge rounded-pill text-bg-light ms-2 {{ ($pendingEventRegistrationCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $pendingEventRegistrationCount }}</span>
                                    </a>
                                </div>
                            </div>
                            <a href="{{ route('announcements.index') }}" class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">Announcements</a>
                        <a href="{{ route('activities.index') }}" class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}">Activities</a>
                        <a href="{{ route('admin.settings.landing-video.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.landing-video.*', 'admin.settings.landing-profiles.*') ? 'active' : '' }}">Alumni Administration</a>
                    @else
                        <a href="{{ route('portal.requests.index') }}" class="nav-link {{ request()->routeIs('portal.requests.*') ? 'active' : '' }}">My Requests</a>
                    @endif

                        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profile</a>
                    </nav>

                    <div class="mt-auto page-card p-3 bg-transparent border border-white border-opacity-10">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="user-avatar">
                                @if (auth()->user()->profile_photo_url)
                                    <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}">
                                @else
                                    <div class="user-avatar-placeholder">{{ auth()->user()->initials }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="small text-white-50">Signed in as</div>
                                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                            </div>
                        </div>
                        <div class="small text-white-50 mb-3">{{ auth()->user()->email }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-light w-100">Log out</button>
                        </form>
                    </div>
                </aside>

                <main id="main-content" tabindex="-1" class="app-main col-12 {{ $workspacePreview ? 'p-0' : 'p-3 p-lg-4 p-xl-5' }}">
                    @if ($workspacePreview)
                        @if (session('success') || session('warning') || $errors->any())
                            <div class="main-wrapper py-3">
                                @if (session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif

                                @if (session('warning'))
                                    <div class="alert alert-warning">{{ session('warning') }}</div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <div class="fw-semibold mb-2">Please fix the following:</div>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @yield('content')
                    @else
                        <div class="content-panel p-3 p-lg-4">
                            @unless (request()->routeIs('dashboard', 'portal.dashboard'))
                                <header class="workspace-page-header">
                                    <div class="workspace-eyebrow">{{ $layoutUser->isAdmin() ? 'Alumni administration' : 'Your alumni portal' }}</div>
                                    <h1>@yield('title', 'Alumni Link')</h1>
                                    @hasSection('subtitle')
                                        <p>@yield('subtitle')</p>
                                    @endif
                                </header>
                            @endunless
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            @if (session('warning'))
                                <div class="alert alert-warning">{{ session('warning') }}</div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <div class="fw-semibold mb-2">Please fix the following:</div>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @yield('content')
                        </div>
                    @endif
                </main>
            </div>
            </div>
        </div>
    @else
        <div class="guest-shell d-flex flex-column">
            @unless ($fullGuest)
                <nav class="navbar navbar-expand-lg bg-white border-bottom guest-navbar {{ $schoolGuestHeader ? 'guest-navbar-school' : '' }}">
                    <div class="main-wrapper">
                        @if ($schoolGuestHeader)
                            <div class="guest-school-brand" aria-label="St. Bridget College">
                                <span class="guest-school-seal" aria-hidden="true">
                                    <img src="{{ asset('images/sbc-logo.svg') }}" alt="">
                                </span>
                                <span class="guest-school-copy">
                                    <span class="guest-school-title">ST. BRIDGET COLLEGE</span>
                                    <span class="guest-school-motto">luceat lux vestra</span>
                                </span>
                            </div>
                        @endif
                        <a class="navbar-brand fw-semibold guest-home-button" href="{{ route('home') }}">HOME</a>
                    </div>
                </nav>
            @endunless

            @if ($fullGuest)
                <main id="main-content" tabindex="-1" class="app-main flex-grow-1">
                    @if (session('success') || $errors->any())
                        <div class="main-wrapper py-3">
                            @if (session('success'))
                                <div class="alert alert-success mb-3">{{ session('success') }}</div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger mb-0">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    @yield('content')
                </main>
            @else
                <main id="main-content" tabindex="-1" class="app-main {{ $centeredGuest ? 'guest-centered-main' : 'main-wrapper' }} flex-grow-1 d-flex align-items-center justify-content-center py-5">
                    <div class="w-100">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')

                        @if (! View::hasSection('content'))
                            <div class="hero-card p-4 p-md-5">
                                <div class="row g-4 align-items-center">
                                    <div class="col-md-7">
                                        <div class="stat-pill mb-3">School Record Requests</div>
                                        <h1 class="display-6 fw-semibold mb-3">Digital request and school pickup workflow for alumni records.</h1>
                                        <p class="mb-4 text-white-50">Admins manage imported alumni data and process requests. Alumni log in separately, submit requests online, and track when records are ready to be claimed at school.</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('portal.login') }}" class="btn btn-light">Login</a>
                                            <a href="{{ route('portal.register') }}" class="btn btn-outline-light">Create Account</a>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="page-card p-4">
                                            <div class="text-secondary small mb-2">Access points</div>
                                            <div class="d-grid gap-2">
                                                <a href="{{ route('portal.login') }}" class="btn btn-outline-success">Portal Login</a>
                                                <a href="{{ route('portal.register') }}" class="btn btn-outline-secondary">Create Account</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </main>
            @endif
        </div>
    @endif

    @if ($showWorkspaceShell)
        <div class="toast-container position-fixed top-0 end-0 p-3" data-admin-approval-toast-container style="z-index: 1080;"></div>
    @endif

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}?v=5.3.3"></script>
    <script>
        (function () {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    var registerWorker = function () {
                        navigator.serviceWorker.register(@json(asset('firebase-messaging-sw.js'))).catch(function () {
                            return null;
                        });
                    };
                    if ('requestIdleCallback' in window) {
                        window.requestIdleCallback(registerWorker, { timeout: 5000 });
                    } else {
                        window.setTimeout(registerWorker, 2500);
                    }
                });
            }

            var deferredInstallPrompt = null;

            function toggleInstallButtons(visible) {
                document.querySelectorAll('[data-install-app]').forEach(function (button) {
                    button.classList.toggle('d-none', !visible);
                });
            }

            window.addEventListener('beforeinstallprompt', function (event) {
                event.preventDefault();
                deferredInstallPrompt = event;
                toggleInstallButtons(true);
            });

            window.addEventListener('appinstalled', function () {
                deferredInstallPrompt = null;
                toggleInstallButtons(false);
            });

            document.addEventListener('click', async function (event) {
                var button = event.target.closest('[data-install-app]');

                if (!button || !deferredInstallPrompt) {
                    return;
                }

                event.preventDefault();
                deferredInstallPrompt.prompt();

                try {
                    await deferredInstallPrompt.userChoice;
                } catch (error) {
                    // Ignore prompt errors and keep the page usable.
                }

                deferredInstallPrompt = null;
                toggleInstallButtons(false);
            });

            function formatStudentId(value) {
                return String(value || '').trim();
            }

            document.querySelectorAll('[data-student-id-format]').forEach(function (field) {
                var form = field.closest('form');

                field.value = formatStudentId(field.value);

                field.addEventListener('blur', function () {
                    field.value = formatStudentId(field.value);
                });

                if (form) {
                    form.addEventListener('submit', function () {
                        field.value = formatStudentId(field.value);
                    });
                }
            });
        })();
    </script>
    @if ($showWorkspaceShell && $layoutUser?->isAdmin())
        <script>
            (function () {
                var notificationUrl = @json(route('users.pending.notifications'));
                var pendingUrl = @json(route('users.pending'));
                var storageKey = 'adminPendingApprovalLatestId';
                var initialLatestId = Number(@json((int) ($latestPendingAccountId ?? 0))) || 0;
                var lastSeenId = initialLatestId;
                var isPolling = false;
                var pollDelay = 10000;
                var audioContext = null;
                var cleanTitle = document.title.replace(/^\(\d+\)\s+/, '');

                try {
                    lastSeenId = Math.max(lastSeenId, Number(window.localStorage.getItem(storageKey)) || 0);
                    window.localStorage.setItem(storageKey, String(lastSeenId));
                } catch (error) {
                    // Local storage can be disabled; notifications still work for the current page.
                }

                function updatePendingBadge(count) {
                    document.querySelectorAll('[data-pending-account-badge]').forEach(function (badge) {
                        badge.textContent = String(count);
                        badge.classList.toggle('d-none', Number(count) <= 0);
                    });

                    document.title = Number(count) > 0
                        ? '(' + count + ') ' + cleanTitle
                        : cleanTitle;
                }

                function requestBrowserNotificationPermission() {
                    if (!('Notification' in window) || Notification.permission !== 'default') {
                        return;
                    }

                    var permissionRequest = Notification.requestPermission();

                    if (permissionRequest && typeof permissionRequest.catch === 'function') {
                        permissionRequest.catch(function () {
                            return null;
                        });
                    }
                }

                function getAudioContext() {
                    var AudioConstructor = window.AudioContext || window.webkitAudioContext;

                    if (!AudioConstructor) {
                        return null;
                    }

                    if (!audioContext) {
                        audioContext = new AudioConstructor();
                    }

                    return audioContext;
                }

                function unlockAudio() {
                    var context = getAudioContext();

                    if (context && context.state === 'suspended') {
                        context.resume().catch(function () {
                            return null;
                        });
                    }
                }

                function playApprovalSound() {
                    var context = getAudioContext();

                    if (!context) {
                        return;
                    }

                    function ring() {
                        var start = context.currentTime;
                        var gain = context.createGain();
                        var firstTone = context.createOscillator();
                        var secondTone = context.createOscillator();

                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(0.16, start + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.62);

                        firstTone.type = 'sine';
                        firstTone.frequency.setValueAtTime(880, start);
                        firstTone.connect(gain);
                        firstTone.start(start);
                        firstTone.stop(start + 0.22);

                        secondTone.type = 'sine';
                        secondTone.frequency.setValueAtTime(660, start + 0.24);
                        secondTone.connect(gain);
                        secondTone.start(start + 0.24);
                        secondTone.stop(start + 0.62);

                        gain.connect(context.destination);
                    }

                    if (context.state === 'suspended') {
                        context.resume().then(function () {
                            if (context.state === 'running') {
                                ring();
                            }
                        }).catch(function () {
                            return null;
                        });
                        return;
                    }

                    if (context.state === 'running') {
                        ring();
                    }
                }

                function showSystemNotification(request) {
                    if (!('Notification' in window) || Notification.permission !== 'granted') {
                        return;
                    }

                    try {
                    var notification = new Notification('New account approval request', {
                        body: (request.name || 'New applicant') + ' submitted an alumni account request.',
                        tag: 'pending-account-' + request.id,
                    });

                    notification.onclick = function () {
                        window.focus();
                        window.location.href = request.review_url || pendingUrl;
                    };
                    } catch (error) {
                        // Mobile browsers may only allow worker notifications; keep in-app updates working.
                    }
                }

                function showToast(request) {
                    var container = document.querySelector('[data-admin-approval-toast-container]');

                    if (!container) {
                        return;
                    }

                    var toast = document.createElement('div');
                    toast.className = 'toast admin-approval-toast';
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');

                    var header = document.createElement('div');
                    header.className = 'toast-header';

                    var dot = document.createElement('span');
                    dot.className = 'admin-approval-toast-dot me-2';

                    var title = document.createElement('strong');
                    title.className = 'me-auto';
                    title.textContent = 'New account approval';

                    var time = document.createElement('small');
                    time.className = 'text-secondary';
                    time.textContent = 'now';

                    var closeButton = document.createElement('button');
                    closeButton.type = 'button';
                    closeButton.className = 'btn-close';
                    closeButton.setAttribute('data-bs-dismiss', 'toast');
                    closeButton.setAttribute('aria-label', 'Close');

                    header.appendChild(dot);
                    header.appendChild(title);
                    header.appendChild(time);
                    header.appendChild(closeButton);

                    var body = document.createElement('div');
                    body.className = 'toast-body';

                    var name = document.createElement('div');
                    name.className = 'fw-semibold text-primary';
                    name.textContent = request.name || 'New applicant';

                    var email = document.createElement('div');
                    email.className = 'small text-secondary mb-3';
                    email.textContent = request.email || 'Account request submitted';

                    var actions = document.createElement('div');
                    actions.className = 'd-flex flex-wrap gap-2';

                    var reviewLink = document.createElement('a');
                    reviewLink.className = 'btn btn-sm btn-primary';
                    reviewLink.href = request.review_url || pendingUrl;
                    reviewLink.textContent = 'Review';

                    var pendingLink = document.createElement('a');
                    pendingLink.className = 'btn btn-sm btn-outline-primary';
                    pendingLink.href = pendingUrl;
                    pendingLink.textContent = 'Pending Accounts';

                    actions.appendChild(reviewLink);
                    actions.appendChild(pendingLink);
                    body.appendChild(name);
                    body.appendChild(email);
                    body.appendChild(actions);

                    toast.appendChild(header);
                    toast.appendChild(body);
                    container.appendChild(toast);

                    if (window.bootstrap && window.bootstrap.Toast) {
                        var toastInstance = new bootstrap.Toast(toast, {
                            delay: 9000,
                        });

                        toast.addEventListener('hidden.bs.toast', function () {
                            toast.remove();
                        });

                        toastInstance.show();
                        return;
                    }

                    toast.classList.add('show');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 9000);
                }

                function rememberLatestId(latestId) {
                    if (latestId <= lastSeenId) {
                        return;
                    }

                    lastSeenId = latestId;

                    try {
                        window.localStorage.setItem(storageKey, String(lastSeenId));
                    } catch (error) {
                        // Keep going even when local storage is unavailable.
                    }
                }

                function handleNewRequests(requests, pendingCount, latestId) {
                    if (requests.length > 0) {
                        requests.forEach(function (request) {
                            showToast(request);
                            showSystemNotification(request);
                        });

                        playApprovalSound();
                        document.dispatchEvent(new CustomEvent('admin:pending-account-request', {
                            detail: {
                                requests: requests,
                                count: pendingCount,
                            },
                        }));
                    }

                    rememberLatestId(latestId);
                }

                function pollPendingAccounts() {
                    if (isPolling || document.hidden || !navigator.onLine) {
                        return;
                    }

                    isPolling = true;
                    var pollController = new AbortController();
                    var pollTimeout = window.setTimeout(function () { pollController.abort(); }, 12000);

                    fetch(notificationUrl + '?after=' + encodeURIComponent(lastSeenId), {
                        signal: pollController.signal,
                        cache: 'no-store',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Pending account notification check failed.');
                            }

                            return response.json();
                        })
                        .then(function (data) {
                            var pendingCount = Number(data.count) || 0;
                            var latestId = Number(data.latest_id) || lastSeenId;
                            var requests = Array.isArray(data.new) ? data.new : [];

                            updatePendingBadge(pendingCount);
                            handleNewRequests(requests, pendingCount, latestId);
                        })
                        .catch(function () {
                            return null;
                        })
                        .finally(function () {
                            window.clearTimeout(pollTimeout);
                            isPolling = false;
                        });
                }

                document.addEventListener('pointerdown', function () {
                    unlockAudio();
                    requestBrowserNotificationPermission();
                }, { once: true });

                document.addEventListener('keydown', function () {
                    unlockAudio();
                    requestBrowserNotificationPermission();
                }, { once: true });

                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        pollPendingAccounts();
                    }
                });

                window.setTimeout(pollPendingAccounts, 1500);
                window.setInterval(pollPendingAccounts, pollDelay);
            })();
        </script>
    @endif
    @if ($showWorkspaceShell && $layoutUser?->isAdmin())
        <script>
            (function () {
                var notificationUrl = @json(route('requests.notifications'));
                var requestsUrl = @json(route('requests.index'));
                var storageKey = 'adminPendingRecordRequestLatestId';
                var initialLatestId = Number(@json((int) ($latestPendingRecordRequestId ?? 0))) || 0;
                var lastSeenId = initialLatestId;
                var isPolling = false;
                var pollDelay = 10000;
                var audioContext = null;

                try {
                    lastSeenId = Math.max(lastSeenId, Number(window.localStorage.getItem(storageKey)) || 0);
                    window.localStorage.setItem(storageKey, String(lastSeenId));
                } catch (error) {
                    // Local storage can be disabled; notifications still work for the current page.
                }

                function updateRequestBadge(count) {
                    document.querySelectorAll('[data-pending-record-request-badge]').forEach(function (badge) {
                        badge.textContent = String(count);
                        badge.classList.toggle('d-none', Number(count) <= 0);
                    });
                }

                function requestBrowserNotificationPermission() {
                    if (!('Notification' in window) || Notification.permission !== 'default') {
                        return;
                    }

                    var permissionRequest = Notification.requestPermission();

                    if (permissionRequest && typeof permissionRequest.catch === 'function') {
                        permissionRequest.catch(function () {
                            return null;
                        });
                    }
                }

                function getAudioContext() {
                    var AudioConstructor = window.AudioContext || window.webkitAudioContext;

                    if (!AudioConstructor) {
                        return null;
                    }

                    if (!audioContext) {
                        audioContext = new AudioConstructor();
                    }

                    return audioContext;
                }

                function unlockAudio() {
                    var context = getAudioContext();

                    if (context && context.state === 'suspended') {
                        context.resume().catch(function () {
                            return null;
                        });
                    }
                }

                function playRequestSound() {
                    var context = getAudioContext();

                    if (!context) {
                        return;
                    }

                    function ring() {
                        var start = context.currentTime;
                        var gain = context.createGain();
                        var firstTone = context.createOscillator();
                        var secondTone = context.createOscillator();

                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(0.14, start + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.56);

                        firstTone.type = 'sine';
                        firstTone.frequency.setValueAtTime(760, start);
                        firstTone.connect(gain);
                        firstTone.start(start);
                        firstTone.stop(start + 0.2);

                        secondTone.type = 'sine';
                        secondTone.frequency.setValueAtTime(980, start + 0.22);
                        secondTone.connect(gain);
                        secondTone.start(start + 0.22);
                        secondTone.stop(start + 0.56);

                        gain.connect(context.destination);
                    }

                    if (context.state === 'suspended') {
                        context.resume().then(function () {
                            if (context.state === 'running') {
                                ring();
                            }
                        }).catch(function () {
                            return null;
                        });
                        return;
                    }

                    if (context.state === 'running') {
                        ring();
                    }
                }

                function showSystemNotification(recordRequest) {
                    if (!('Notification' in window) || Notification.permission !== 'granted') {
                        return;
                    }

                    try {
                    var notification = new Notification('New record request', {
                        body: (recordRequest.alumni_name || 'An alumni') + ' submitted ' + (recordRequest.request_type || 'a document request') + '.',
                        tag: 'record-request-' + recordRequest.id,
                    });

                    notification.onclick = function () {
                        window.focus();
                        window.location.href = recordRequest.review_url || requestsUrl;
                    };
                    } catch (error) {
                        // A system notification must never interrupt the request update batch.
                    }
                }

                function showToast(recordRequest) {
                    var container = document.querySelector('[data-admin-approval-toast-container]');

                    if (!container) {
                        return;
                    }

                    var toast = document.createElement('div');
                    toast.className = 'toast admin-approval-toast';
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');

                    var header = document.createElement('div');
                    header.className = 'toast-header';

                    var dot = document.createElement('span');
                    dot.className = 'admin-approval-toast-dot me-2';

                    var title = document.createElement('strong');
                    title.className = 'me-auto';
                    title.textContent = 'New record request';

                    var time = document.createElement('small');
                    time.className = 'text-secondary';
                    time.textContent = 'now';

                    var closeButton = document.createElement('button');
                    closeButton.type = 'button';
                    closeButton.className = 'btn-close';
                    closeButton.setAttribute('data-bs-dismiss', 'toast');
                    closeButton.setAttribute('aria-label', 'Close');

                    header.appendChild(dot);
                    header.appendChild(title);
                    header.appendChild(time);
                    header.appendChild(closeButton);

                    var body = document.createElement('div');
                    body.className = 'toast-body';

                    var alumniName = document.createElement('div');
                    alumniName.className = 'fw-semibold text-primary';
                    alumniName.textContent = recordRequest.alumni_name || 'Unknown alumni';

                    var requestLabel = document.createElement('div');
                    requestLabel.className = 'small text-secondary mb-3';
                    requestLabel.textContent = (recordRequest.request_type || 'Document request') + ' - ' + (recordRequest.year_requested || 'No year');

                    var actions = document.createElement('div');
                    actions.className = 'd-flex flex-wrap gap-2';

                    var reviewLink = document.createElement('a');
                    reviewLink.className = 'btn btn-sm btn-primary';
                    reviewLink.href = recordRequest.review_url || requestsUrl;
                    reviewLink.textContent = 'Open Requests';

                    actions.appendChild(reviewLink);
                    body.appendChild(alumniName);
                    body.appendChild(requestLabel);
                    body.appendChild(actions);

                    toast.appendChild(header);
                    toast.appendChild(body);
                    container.appendChild(toast);

                    if (window.bootstrap && window.bootstrap.Toast) {
                        var toastInstance = new bootstrap.Toast(toast, {
                            delay: 9000,
                        });

                        toast.addEventListener('hidden.bs.toast', function () {
                            toast.remove();
                        });

                        toastInstance.show();
                        return;
                    }

                    toast.classList.add('show');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 9000);
                }

                function rememberLatestId(latestId) {
                    if (latestId <= lastSeenId) {
                        return;
                    }

                    lastSeenId = latestId;

                    try {
                        window.localStorage.setItem(storageKey, String(lastSeenId));
                    } catch (error) {
                        // Keep going even when local storage is unavailable.
                    }
                }

                function handleNewRecordRequests(recordRequests, pendingCount, latestId) {
                    if (recordRequests.length > 0) {
                        recordRequests.forEach(function (recordRequest) {
                            showToast(recordRequest);
                            showSystemNotification(recordRequest);
                        });

                        playRequestSound();
                    }

                    rememberLatestId(latestId);
                }

                function pollPendingRecordRequests() {
                    if (isPolling || document.hidden || !navigator.onLine) {
                        return;
                    }

                    isPolling = true;
                    var pollController = new AbortController();
                    var pollTimeout = window.setTimeout(function () { pollController.abort(); }, 12000);

                    fetch(notificationUrl + '?after=' + encodeURIComponent(lastSeenId), {
                        signal: pollController.signal,
                        cache: 'no-store',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Pending record request notification check failed.');
                            }

                            return response.json();
                        })
                        .then(function (data) {
                            var pendingCount = Number(data.count) || 0;
                            var latestId = Number(data.latest_id) || lastSeenId;
                            var recordRequests = Array.isArray(data.new) ? data.new : [];

                            updateRequestBadge(pendingCount);
                            handleNewRecordRequests(recordRequests, pendingCount, latestId);
                        })
                        .catch(function () {
                            return null;
                        })
                        .finally(function () {
                            window.clearTimeout(pollTimeout);
                            isPolling = false;
                        });
                }

                document.addEventListener('pointerdown', function () {
                    unlockAudio();
                    requestBrowserNotificationPermission();
                }, { once: true });

                document.addEventListener('keydown', function () {
                    unlockAudio();
                    requestBrowserNotificationPermission();
                }, { once: true });

                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        pollPendingRecordRequests();
                    }
                });

                window.setTimeout(pollPendingRecordRequests, 1800);
                window.setInterval(pollPendingRecordRequests, pollDelay);
            })();
        </script>
    @endif
    @if ($showWorkspaceShell && $layoutUser?->isAlumni())
        <script>
            (function () {
                var notificationUrl = @json(route('portal.requests.notifications'));
                var dashboardUrl = @json(route('portal.dashboard'));
                var storageKey = 'alumniRequestUpdateLatestTimestamp';
                var initialLatestTimestamp = Number(@json((int) ($latestAlumniRequestUpdateTimestamp ?? 0))) || 0;
                var lastSeenTimestamp = initialLatestTimestamp;
                var isPolling = false;
                var pollDelay = 10000;
                var audioContext = null;

                try {
                    lastSeenTimestamp = Math.max(lastSeenTimestamp, Number(window.localStorage.getItem(storageKey)) || 0);
                    window.localStorage.setItem(storageKey, String(lastSeenTimestamp));
                } catch (error) {
                    // Local storage can be disabled; notifications still work for the current page.
                }

                function requestBrowserNotificationPermission() {
                    if (!('Notification' in window) || Notification.permission !== 'default') {
                        return;
                    }

                    var permissionRequest = Notification.requestPermission();

                    if (permissionRequest && typeof permissionRequest.catch === 'function') {
                        permissionRequest.catch(function () {
                            return null;
                        });
                    }
                }

                function getAudioContext() {
                    var AudioConstructor = window.AudioContext || window.webkitAudioContext;

                    if (!AudioConstructor) {
                        return null;
                    }

                    if (!audioContext) {
                        audioContext = new AudioConstructor();
                    }

                    return audioContext;
                }

                function unlockAudio() {
                    var context = getAudioContext();

                    if (context && context.state === 'suspended') {
                        context.resume().catch(function () {
                            return null;
                        });
                    }
                }

                function playUpdateSound() {
                    var context = getAudioContext();

                    if (!context) {
                        return;
                    }

                    function ring() {
                        var start = context.currentTime;
                        var gain = context.createGain();
                        var firstTone = context.createOscillator();
                        var secondTone = context.createOscillator();

                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(0.14, start + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.62);

                        firstTone.type = 'sine';
                        firstTone.frequency.setValueAtTime(660, start);
                        firstTone.connect(gain);
                        firstTone.start(start);
                        firstTone.stop(start + 0.22);

                        secondTone.type = 'sine';
                        secondTone.frequency.setValueAtTime(880, start + 0.24);
                        secondTone.connect(gain);
                        secondTone.start(start + 0.24);
                        secondTone.stop(start + 0.62);

                        gain.connect(context.destination);
                    }

                    if (context.state === 'suspended') {
                        context.resume().then(function () {
                            if (context.state === 'running') {
                                ring();
                            }
                        }).catch(function () {
                            return null;
                        });
                        return;
                    }

                    if (context.state === 'running') {
                        ring();
                    }
                }

                function showSystemNotification(update) {
                    if (!('Notification' in window) || Notification.permission !== 'granted') {
                        return;
                    }

                    try {
                    var notification = new Notification('Request update from admin', {
                        body: (update.request_type || 'Your request') + ' is now ' + (update.status || 'updated') + '.',
                        tag: 'alumni-request-update-' + update.id + '-' + update.updated_timestamp,
                    });

                    notification.onclick = function () {
                        window.focus();
                        window.location.href = update.review_url || dashboardUrl;
                    };
                    } catch (error) {
                        // Keep in-app updates available when browser notifications are unsupported.
                    }
                }

                function showToast(update) {
                    var container = document.querySelector('[data-admin-approval-toast-container]');

                    if (!container) {
                        return;
                    }

                    var toast = document.createElement('div');
                    toast.className = 'toast admin-approval-toast';
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');

                    var header = document.createElement('div');
                    header.className = 'toast-header';

                    var dot = document.createElement('span');
                    dot.className = 'admin-approval-toast-dot me-2';

                    var title = document.createElement('strong');
                    title.className = 'me-auto';
                    title.textContent = 'Request update';

                    var time = document.createElement('small');
                    time.className = 'text-secondary';
                    time.textContent = 'now';

                    var closeButton = document.createElement('button');
                    closeButton.type = 'button';
                    closeButton.className = 'btn-close';
                    closeButton.setAttribute('data-bs-dismiss', 'toast');
                    closeButton.setAttribute('aria-label', 'Close');

                    header.appendChild(dot);
                    header.appendChild(title);
                    header.appendChild(time);
                    header.appendChild(closeButton);

                    var body = document.createElement('div');
                    body.className = 'toast-body';

                    var requestTitle = document.createElement('div');
                    requestTitle.className = 'fw-semibold text-primary';
                    requestTitle.textContent = update.request_type || 'Record request';

                    var status = document.createElement('div');
                    status.className = 'small text-secondary mb-2';
                    status.textContent = (update.status || 'Updated') + ' - ' + (update.year_requested || 'No year');

                    body.appendChild(requestTitle);
                    body.appendChild(status);

                    if (update.admin_notes) {
                        var note = document.createElement('div');
                        note.className = 'small mb-3';
                        note.textContent = update.admin_notes;
                        body.appendChild(note);
                    }

                    var actions = document.createElement('div');
                    actions.className = 'd-flex flex-wrap gap-2';

                    var dashboardLink = document.createElement('a');
                    dashboardLink.className = 'btn btn-sm btn-primary';
                    dashboardLink.href = update.review_url || dashboardUrl;
                    dashboardLink.textContent = 'Open Dashboard';

                    actions.appendChild(dashboardLink);
                    body.appendChild(actions);

                    toast.appendChild(header);
                    toast.appendChild(body);
                    container.appendChild(toast);

                    if (window.bootstrap && window.bootstrap.Toast) {
                        var toastInstance = new bootstrap.Toast(toast, {
                            delay: 10000,
                        });

                        toast.addEventListener('hidden.bs.toast', function () {
                            toast.remove();
                        });

                        toastInstance.show();
                        return;
                    }

                    toast.classList.add('show');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 10000);
                }

                function rememberLatestTimestamp(latestTimestamp) {
                    if (latestTimestamp <= lastSeenTimestamp) {
                        return;
                    }

                    lastSeenTimestamp = latestTimestamp;

                    try {
                        window.localStorage.setItem(storageKey, String(lastSeenTimestamp));
                    } catch (error) {
                        // Keep going even when local storage is unavailable.
                    }
                }

                function handleUpdates(updates, latestTimestamp) {
                    if (updates.length > 0) {
                        updates.forEach(function (update) {
                            showToast(update);
                            showSystemNotification(update);
                        });

                        playUpdateSound();
                    }

                    rememberLatestTimestamp(latestTimestamp);
                }

                function pollRequestUpdates() {
                    if (isPolling || document.hidden || !navigator.onLine) {
                        return;
                    }

                    isPolling = true;
                    var pollController = new AbortController();
                    var pollTimeout = window.setTimeout(function () { pollController.abort(); }, 12000);

                    fetch(notificationUrl + '?after=' + encodeURIComponent(lastSeenTimestamp), {
                        signal: pollController.signal,
                        cache: 'no-store',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Request update notification check failed.');
                            }

                            return response.json();
                        })
                        .then(function (data) {
                            var latestTimestamp = Number(data.latest_timestamp) || lastSeenTimestamp;
                            var updates = Array.isArray(data.new) ? data.new : [];

                            handleUpdates(updates, latestTimestamp);
                        })
                        .catch(function () {
                            return null;
                        })
                        .finally(function () {
                            window.clearTimeout(pollTimeout);
                            isPolling = false;
                        });
                }

                document.addEventListener('pointerdown', function () {
                    unlockAudio();
                    requestBrowserNotificationPermission();
                }, { once: true });

                document.addEventListener('keydown', function () {
                    unlockAudio();
                    requestBrowserNotificationPermission();
                }, { once: true });

                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        pollRequestUpdates();
                    }
                });

                window.setTimeout(pollRequestUpdates, 1800);
                window.setInterval(pollRequestUpdates, pollDelay);
            })();
        </script>
    @endif
    @stack('scripts')
    <script src="{{ asset('js/interface.js') }}?v={{ filemtime(public_path('js/interface.js')) }}" defer></script>

</body>
</html>
