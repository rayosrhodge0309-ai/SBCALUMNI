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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --app-bg: #fff;
            /* avatar size variables (default desktop) */
            --avatar-size: 2.4rem;
            --profile-avatar-size: 4rem;
            --cream: #f7fbff;
            --panel: #edf8fd;
            --panel-strong: #d6eef9;
            --panel-border: rgba(7, 17, 111, 0.2);
            --wine: #07116f;
            --wine-deep: #04084d;
            --wine-soft: #0b45b8;
            --gold: #9c7a00;
            --action: #0b45b8;
            --action-dark: #07116f;
            --school-teal: #0a86b7;
            --school-sidebar: #0b45b8;
            --ink: #1f2330;
            --muted: #6c6f77;
            --portal-bg: #0b45b8;
            --admin-bg: #0b45b8;
            --landing-bg-image: url('{{ asset('images/landing-bg.jpg') }}');
        }

        body {
            min-height: 100vh;
            background: #fff;
            color: var(--ink);
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .brand-title,
        .hero-heading,
        .section-title {
            font-family: Georgia, "Times New Roman", serif;
            letter-spacing: 0.01em;
        }

        a {
            color: inherit;
        }

        .main-wrapper,
        .site-container {
            width: 100%;
            max-width: 1800px;
            margin-left: auto;
            margin-right: auto;
            padding-left: clamp(3rem, 4vw, 50px);
            padding-right: clamp(3rem, 4vw, 50px);
            box-sizing: border-box;
        }

        .app-workspace-fluid {
            width: 100%;
        }

        .app-workspace-wrapper {
            max-width: 1800px;
        }

        .sidebar {
            color: #fff;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            align-self: flex-start;
            border-right: 1px solid rgba(4, 0, 120, 0.14);
        }

        .school-system-bar {
            min-height: 5rem;
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            color: #fff;
            border-bottom: 4px solid var(--gold);
            box-shadow: 0 10px 24px rgba(7, 17, 111, 0.22);
        }

        .school-system-title {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.75rem, 4.4vw, 3.55rem);
            color: #fff;
            letter-spacing: 0.24em;
            line-height: 1;
            text-transform: uppercase;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .school-system-subtitle {
            color: #fff;
            font-size: 0.98rem;
            letter-spacing: 0.7em;
            text-transform: lowercase;
        }

        .sidebar-admin {
            background: linear-gradient(180deg, #0b45b8 0%, #07116f 100%);
        }

        .sidebar-portal {
            background: linear-gradient(180deg, #0b45b8 0%, #07116f 100%);
            --gold: #0b45b8;
            --wine: #fff;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.82);
            border-radius: 1rem;
            padding: 0.85rem 1rem;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--wine);
            background: var(--gold);
            transform: translateX(4px);
        }

        .brand-pill,
        .stat-pill,
        .utility-pill,
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .brand-pill,
        .stat-pill {
            background: var(--gold);
            color: var(--wine);
        }

        .content-panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 0.95rem;
            box-shadow: 0 16px 34px rgba(4, 0, 120, 0.18);
        }

        .page-card {
            background: var(--panel-strong);
            border: 1px solid var(--panel-border);
            border-radius: 0.9rem;
            box-shadow: 0 8px 18px rgba(4, 0, 120, 0.1);
        }

        .btn-primary,
        .btn-success {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--action);
            --bs-btn-border-color: var(--action);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--action-dark);
            --bs-btn-hover-border-color: var(--action-dark);
            --bs-btn-focus-shadow-rgb: 11, 69, 184;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--action-dark);
            --bs-btn-active-border-color: var(--action-dark);
            --bs-btn-disabled-color: #fff;
            --bs-btn-disabled-bg: rgba(11, 69, 184, 0.62);
            --bs-btn-disabled-border-color: rgba(11, 69, 184, 0.62);
        }

        .btn-outline-primary,
        .btn-outline-success {
            --bs-btn-color: var(--action);
            --bs-btn-border-color: var(--action);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--action);
            --bs-btn-hover-border-color: var(--action);
            --bs-btn-focus-shadow-rgb: 11, 69, 184;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--action);
            --bs-btn-active-border-color: var(--action);
            --bs-btn-disabled-color: rgba(11, 69, 184, 0.62);
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: rgba(11, 69, 184, 0.42);
        }

        .btn-outline-secondary,
        .btn-outline-dark {
            --bs-btn-color: var(--wine);
            --bs-btn-border-color: var(--wine);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--wine);
            --bs-btn-hover-border-color: var(--wine);
            --bs-btn-focus-shadow-rgb: 4, 0, 120;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--wine);
            --bs-btn-active-border-color: var(--wine);
            --bs-btn-disabled-color: rgba(4, 0, 120, 0.5);
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: rgba(4, 0, 120, 0.32);
        }

        .text-primary {
            color: var(--wine) !important;
        }

        .bg-primary-subtle {
            background-color: rgba(156, 122, 0, 0.28) !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--gold);
            background-color: #fffef0;
            box-shadow: 0 0 0 0.2rem rgba(156, 122, 0, 0.28);
        }

        .form-control,
        .form-select {
            background-color: #fff;
            border-color: rgba(4, 0, 120, 0.22);
        }

        .table {
            --bs-table-bg: rgba(255, 255, 255, 0.78);
            --bs-table-border-color: rgba(4, 0, 120, 0.14);
        }

        .table-light {
            --bs-table-bg: rgba(156, 122, 0, 0.22);
            --bs-table-color: var(--wine);
        }

        .admin-workspace {
            --gold: #9c7a00;
            --action: #0b45b8;
            --action-dark: #07116f;
        }

        .admin-workspace .content-panel,
        .admin-workspace .page-card {
            background: #fff !important;
            border-color: rgba(11, 69, 184, 0.32) !important;
            box-shadow: 0 10px 24px rgba(11, 69, 184, 0.08);
        }

        .admin-workspace .sidebar .page-card,
        .admin-workspace .offcanvas-mobile-nav .page-card {
            background: transparent !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            box-shadow: none;
            color: #fff;
        }

        .admin-workspace .page-card h1,
        .admin-workspace .page-card h2,
        .admin-workspace .page-card h3,
        .admin-workspace .page-card h4,
        .admin-workspace .page-card h5,
        .admin-workspace .page-card h6 {
            color: var(--action-dark);
        }

        .admin-workspace .table {
            --bs-table-bg: #fff;
            --bs-table-striped-bg: #fff;
            --bs-table-hover-bg: #fff;
            --bs-table-border-color: rgba(11, 69, 184, 0.18);
            --bs-table-color: var(--action-dark);
            color: var(--action-dark);
        }

        .admin-workspace .table > :not(caption) > * > * {
            background-color: #fff !important;
            border-color: rgba(11, 69, 184, 0.18);
            color: var(--action-dark);
        }

        .admin-workspace .sidebar .nav-link:hover,
        .admin-workspace .sidebar .nav-link.active {
            color: var(--wine);
            background: var(--gold);
        }

        .admin-workspace .brand-pill,
        .admin-workspace .stat-pill {
            background: var(--gold);
            color: var(--wine);
        }

        .admin-workspace .sidebar .nav-link:hover,
        .admin-workspace .sidebar .nav-link.active {
            color: #fff !important;
            background: var(--action) !important;
        }

        .admin-workspace .brand-pill,
        .admin-workspace .stat-pill {
            background: var(--action) !important;
            color: #fff !important;
        }

        .admin-workspace .text-primary {
            color: var(--action) !important;
        }

        .admin-workspace .school-system-title,
        .admin-workspace .school-system-subtitle {
            color: #fff !important;
        }

        .admin-workspace .bg-primary-subtle {
            background-color: rgba(11, 69, 184, 0.18) !important;
        }

        .admin-workspace .form-control:focus,
        .admin-workspace .form-select:focus {
            border-color: var(--action-dark);
            background-color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(11, 69, 184, 0.24);
        }

        .admin-workspace .table-light {
            --bs-table-bg: #fff;
            --bs-table-color: #07116f;
        }

        .admin-workspace .table-light th {
            background-color: #fff !important;
            color: #07116f !important;
        }

        .admin-approval-toast {
            --bs-toast-max-width: 24rem;
            border: 1px solid rgba(11, 69, 184, 0.24);
            border-radius: 0.75rem;
            box-shadow: 0 16px 36px rgba(7, 17, 111, 0.18);
        }

        .admin-approval-toast .toast-header {
            color: #07116f;
            background: #fff;
            border-bottom-color: rgba(11, 69, 184, 0.12);
        }

        .admin-approval-toast-dot {
            width: 0.62rem;
            height: 0.62rem;
            border-radius: 999px;
            background: #0b45b8;
            box-shadow: 0 0 0 0.22rem rgba(11, 69, 184, 0.14);
        }

        .hero-stage {
            width: min(100%, 1500px);
            max-width: 1500px;
            min-height: clamp(34rem, 68vh, 46rem);
            display: flex;
            align-items: center;
            margin: 2rem auto 0;
            padding: clamp(2rem, 3vw, 4rem);
            border-radius: 2rem;
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }
            font-size: 1.05rem;
            letter-spacing: 0.02em;
            font-variant-numeric: tabular-nums;
            color: #111827;
            text-align: center;
            white-space: nowrap;
        }

        .guest-shell {
            min-height: 100vh;
        }

        .user-avatar,
        .profile-avatar {
            /* Sizes driven by CSS variables so utility classes can override */
            width: var(--avatar-size);
            height: var(--avatar-size);
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-avatar img,
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .user-avatar-placeholder,
        .profile-avatar-placeholder,
        .community-avatar {
            width: 100%;
            height: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .profile-avatar {
            /* Larger profile avatar used on profile pages; driven by variable */
            width: var(--profile-avatar-size);
            height: var(--profile-avatar-size);
            border-radius: 50%;
            background: var(--wine);
            color: #fff;
            border: none;
            box-shadow: 0 12px 20px rgba(4, 0, 120, 0.14);
        }

        /* Utility classes to easily change avatar sizes */
        .avatar-sm { --avatar-size: 1.6rem; }
        .avatar-md { --avatar-size: 2.4rem; }
        .avatar-lg { --avatar-size: 3.2rem; }
        .profile-avatar-sm { --profile-avatar-size: 3rem; }
        .profile-avatar-md { --profile-avatar-size: 4rem; }
        .profile-avatar-lg { --profile-avatar-size: 5.6rem; }

        .profile-avatar-placeholder {
            font-size: 1.1rem;
        }

        .dashboard-banner {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 0.95rem;
        }

        .notice-item {
            border-bottom: 1px solid rgba(4, 0, 120, 0.12);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .notice-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .notice-label {
            color: var(--wine);
            background: rgba(156, 122, 0, 0.24);
            border-radius: 999px;
            display: inline-flex;
            padding: 0.25rem 0.65rem;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .activity-card,
        .event-card,
        .team-card,
        .process-card {
            border-radius: 1.2rem;
            border: 1px solid rgba(4, 0, 120, 0.08);
            background: #fff;
            box-shadow: 0 14px 36px rgba(4, 0, 120, 0.08);
        }

        .team-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .trustee-member {
            padding: 1.75rem 0;
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .trustee-name {
            color: #111111;
            font-size: clamp(1.1rem, 1.35vw, 1.35rem);
            font-weight: 700;
            line-height: 1.12;
        }

        .trustee-role {
            color: #6c6f77;
            font-size: 0.96rem;
            line-height: 1.6;
        }

        .trustee-role {
            color: #6c6f77;
            font-size: 0.96rem;
            line-height: 1.6;
        }

        .leadership-profile-photo img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .leadership-profile-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--wine-soft);
            background: #fff;
        }

        .leadership-profile-placeholder svg {
            width: 42%;
            height: 42%;
            min-width: 2.2rem;
            min-height: 2.2rem;
        }

        .activity-media {
            width: 100%;
            max-height: 220px;
            border-radius: 1rem;
            object-fit: cover;
            background: var(--panel);
        }

        .activity-media-video {
            display: block;
        }

        .landing-page {
            position: relative;
            overflow: hidden;
            background: var(--landing-bg-image) center top / cover no-repeat;
        }

        .landing-header {
            background: var(--panel);
            border-bottom: 1px solid rgba(4, 0, 120, 0.14);
        }

        .school-identity-banner {
            background: linear-gradient(90deg, #061069 0%, #123cad 46%, #0d75bb 100%);
            color: #fff;
            border-bottom: 3px solid #9c7a00;
        }

        .school-identity-shell {
            min-height: 4.9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 0.35rem 0;
        }

        .school-identity-lockup {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            color: #fff;
            min-width: 0;
        }

        .school-identity-crest {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            overflow: hidden;
            background: #07116f;
            color: #fff;
            font-weight: 800;
            border: 1px solid rgba(255, 255, 255, 0.22);
            box-shadow: 0 12px 24px rgba(1, 8, 68, 0.24);
        }

        .school-identity-crest-logo {
            background: #fff;
            padding: 0.12rem;
        }

        .school-identity-crest-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .school-identity-copy {
            min-width: 0;
        }

        .school-identity-title {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(2rem, 5vw, 4rem);
            line-height: 0.9;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.28);
        }

        .school-identity-motto {
            margin-top: 0.4rem;
            color: #fff;
            font-size: clamp(0.78rem, 1.4vw, 1.05rem);
            font-weight: 700;
            text-transform: uppercase;
        }

        .school-identity-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.6rem;
            flex: 0 0 auto;
        }

        .school-identity-actions .btn {
            border-radius: 999px;
            padding: 0.56rem 1rem;
            font-weight: 700;
            box-shadow: 0 10px 20px rgba(1, 8, 68, 0.18);
        }

        .brand-lockup {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-crest {
            width: 4.25rem;
            height: 4.25rem;
            border-radius: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: var(--wine);
            color: #fff;
            font-weight: 700;
            font-size: 1.15rem;
            border: 1px solid rgba(4, 0, 120, 0.28);
            box-shadow: 0 12px 26px rgba(4, 0, 120, 0.16);
        }

        .brand-crest-logo {
            background: #fff;
            border-radius: 50%;
            border-color: rgba(4, 0, 120, 0.24);
            padding: 0.12rem;
        }

        .brand-crest-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .school-system-brand-crest {
            width: 3.35rem;
            height: 3.35rem;
            flex-shrink: 0;
        }

        .brand-kicker {
            color: var(--wine);
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 700;
        }

        .brand-title {
            margin: 0;
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            color: var(--ink);
        }

        .brand-subtitle {
            margin: 0.2rem 0 0;
            color: var(--muted);
            max-width: 38rem;
        }

        .landing-nav {
            border-top: 1px solid rgba(4, 0, 120, 0.18);
            border-bottom: 1px solid rgba(4, 0, 120, 0.12);
        }

        .landing-nav .nav {
            gap: 0.35rem;
            padding: 0.65rem 0;
        }

        .landing-nav .nav-link {
            color: var(--ink);
            border-radius: 0.85rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .landing-nav .nav-link:hover {
            color: var(--wine);
            background: rgba(156, 122, 0, 0.24);
        }

        .landing-section {
            padding: 4.5rem 0;
        }

        .hero-stage {
            width: 100%;
            max-width: none;
            min-height: clamp(34rem, 68vh, 46rem);
            display: flex;
            align-items: center;
            margin: 2rem auto 0;
            padding: clamp(2rem, 3vw, 4rem);
            border-radius: 2rem;
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        .hero-stage::before {
            content: "";
            position: absolute;
            inset: 0;
            background: transparent;
            pointer-events: none;
        }

        .hero-stage > .row {
            width: 100%;
            min-height: clamp(34rem, 68vh, 46rem);
            margin: 0;
            max-width: none;
        }

        .hero-columns {
            margin-left: 0;
            margin-right: 0;
        }

        .hero-left-panel,
        .hero-right-panel {
            padding-left: 0;
            padding-right: 0;
        }

        .hero-left-panel {
            padding-right: clamp(2rem, 3vw, 3rem);
        }

        .hero-right-panel {
            padding-left: clamp(2rem, 3vw, 3rem);
        }

        .hero-badge {
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.94);
            margin-bottom: 1rem;
        }

        .hero-heading {
            font-size: clamp(2.4rem, 5vw, 4rem);
            line-height: 1.04;
            margin-bottom: 1rem;
            max-width: 16ch;
        }

        .hero-copy {
            max-width: 48rem;
            color: rgba(255, 255, 255, 0.86);
            font-size: 1.06rem;
        }

        .hero-metric {
            padding: 1rem 1.1rem;
            border-radius: 1.1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .hero-metric-link {
            display: block;
            color: #fff;
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .hero-metric-link:hover,
        .hero-metric-link:focus-visible {
            color: #fff;
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.24);
            box-shadow: 0 14px 24px rgba(6, 32, 79, 0.18);
            transform: translateY(-3px);
            outline: none;
        }

        .hero-metric-value {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .hero-visual {
            position: relative;
            min-height: 100%;
            padding: 2rem;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        .hero-ribbon {
            display: inline-flex;
            width: fit-content;
            padding: 0.7rem 1.5rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            color: rgba(255, 255, 255, 0.96);
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            margin-bottom: 1.25rem;
        }

        .community-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 1.35rem;
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            margin-bottom: 1rem;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .community-card:hover,
        .community-card:focus-within {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.14);
        }

        .community-avatar {
            width: 3.2rem;
            height: 3.2rem;
            border-radius: 1rem;
            background: var(--gold);
            color: var(--wine-deep);
            flex-shrink: 0;
        }

        .section-eyebrow {
            display: inline-flex;
            color: var(--wine);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 0.85rem;
        }

        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.75rem);
            margin-bottom: 1rem;
        }

        .section-copy {
            color: var(--muted);
            max-width: 46rem;
        }

        .ad-badge-card {
            border: 1px solid var(--panel-border);
            background: var(--panel);
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            box-shadow: 0 10px 18px rgba(4, 0, 120, 0.08);
        }

        .ad-badge-label {
            color: var(--wine);
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .ad-badge-value {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: #0b3f70;
        }

        .campus-gallery-hero {
            position: relative;
            margin: 0 auto 1.5rem;
            border-radius: 1.25rem;
            overflow: hidden;
            border: 1px solid rgba(4, 0, 120, 0.24);
            box-shadow: 0 22px 36px rgba(4, 0, 120, 0.12);
            background: var(--panel);
            aspect-ratio: 16 / 7;
            min-height: 420px;
        }

        .campus-gallery-carousel,
        .campus-gallery-carousel .carousel-inner,
        .campus-gallery-carousel .carousel-item {
            width: 100%;
            height: 100%;
        }

        .campus-gallery-carousel .carousel-item {
            position: relative;
            overflow: hidden;
        }

        .campus-gallery-carousel.carousel-fade .carousel-item {
            transition: opacity 1.1s ease-in-out;
        }

        .campus-carousel-indicators {
            margin-bottom: 0.85rem;
            z-index: 4;
        }

        .campus-carousel-indicators [data-bs-target] {
            width: 34px;
            height: 4px;
            border-radius: 999px;
        }

        .campus-gallery-carousel .carousel-control-prev,
        .campus-gallery-carousel .carousel-control-next {
            width: 9%;
            opacity: 0.82;
            z-index: 4;
        }

        .campus-gallery-carousel .carousel-control-prev:hover,
        .campus-gallery-carousel .carousel-control-next:hover {
            opacity: 1;
        }

        .campus-gallery-image {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            background: var(--panel);
            transform: scale(1.08);
            transition: transform 6s ease, filter 0.8s ease;
            filter: saturate(1.03);
        }

        .campus-gallery-carousel .carousel-item.active .campus-gallery-image {
            transform: scale(1);
        }

        .campus-gallery-carousel .carousel-item::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(5, 18, 34, 0.38);
            pointer-events: none;
        }

        .campus-gallery-caption {
            position: absolute;
            left: clamp(1rem, 3vw, 2.5rem);
            right: clamp(1rem, 4vw, 3rem);
            bottom: clamp(1rem, 4vw, 2.25rem);
            max-width: min(38rem, 88%);
            padding: 1.2rem 1.35rem;
            border-radius: 0.95rem;
            background: rgba(7, 24, 44, 0.74);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #fff;
            z-index: 3;
            box-shadow: 0 16px 30px rgba(5, 19, 35, 0.22);
        }

        .campus-gallery-caption-static {
            position: static;
            max-width: 42rem;
        }

        .campus-gallery-kicker {
            display: inline-flex;
            margin-bottom: 0.55rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgba(191, 236, 255, 0.94);
        }

        .campus-gallery-title {
            font-size: clamp(1.5rem, 3.1vw, 2.35rem);
            margin-bottom: 0.55rem;
            color: #fff;
        }

        .campus-gallery-detail {
            max-width: 34rem;
            color: rgba(255, 255, 255, 0.86);
            line-height: 1.65;
        }

        .campus-gallery-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 3vw, 2rem);
            background: var(--wine);
        }

        .campus-gallery-caption-static {
            margin: 0 auto;
            width: min(100%, 42rem);
        }

        .landing-feed-entry {
            position: relative;
        }

        .landing-story-media {
            position: relative;
            width: 100%;
            min-height: 230px;
            aspect-ratio: 16 / 9;
            margin-bottom: 1rem;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(4, 0, 120, 0.2);
            box-shadow: 0 18px 30px rgba(4, 0, 120, 0.12);
            background: var(--panel);
        }

        .landing-story-media::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(5, 18, 34, 0.16);
            pointer-events: none;
        }

        .landing-story-media-asset {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            background: var(--panel);
            transform: scale(1.01);
            transition: transform 0.35s ease, filter 0.35s ease;
            filter: saturate(1.04);
        }

        .landing-story-media:hover .landing-story-media-asset {
            transform: scale(1);
        }

        .landing-story-media-video {
            display: block;
        }

        .reveal {
            animation: fadeUp 0.8s ease both;
        }

        .landing-footer {
            color: rgba(255, 255, 255, 0.9);
            overflow: hidden;
        }

        .landing-footer-shell {
            position: relative;
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            isolation: isolate;
        }

        .landing-footer-shell::before,
        .landing-footer-shell::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .landing-footer-shell::before {
            background: transparent;
            opacity: 0;
        }

        .landing-footer-shell::after {
            background: transparent;
            mix-blend-mode: normal;
        }

        .landing-footer-shell .main-wrapper {
            position: relative;
            z-index: 1;
        }

        .footer-brand-panel,
        .footer-cta-panel {
            border-radius: 0.95rem;
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: rgba(6, 33, 60, 0.26);
            padding: 1.35rem;
            box-shadow: 0 16px 28px rgba(3, 17, 36, 0.26);
        }

        .footer-kicker {
            font-size: 0.74rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(191, 236, 255, 0.95);
            font-weight: 700;
            margin-bottom: 0.6rem;
        }

        .footer-title {
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            font-size: clamp(1.45rem, 3vw, 2.15rem);
            letter-spacing: 0.01em;
            color: #fff;
        }

        .footer-copy {
            color: rgba(239, 247, 255, 0.86);
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .footer-campus-list {
            display: grid;
            gap: 0.7rem;
        }

        .footer-campus-item {
            display: grid;
            gap: 0.15rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px dashed rgba(191, 236, 255, 0.28);
            font-size: 0.94rem;
        }

        .footer-campus-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .footer-campus-label {
            font-size: 0.73rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(191, 236, 255, 0.86);
            font-weight: 700;
        }

        .footer-link-group {
            display: grid;
            gap: 0.58rem;
        }

        .footer-group-title {
            font-size: 0.92rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: rgba(221, 243, 255, 0.95);
            margin-bottom: 0.65rem;
            font-weight: 700;
        }

        .footer-link-group a {
            color: rgba(255, 255, 255, 0.86);
            text-decoration: none;
            font-size: 0.95rem;
            border-radius: 0.65rem;
            padding: 0.22rem 0.45rem;
            width: fit-content;
            transition: background-color 0.2s ease, transform 0.2s ease, color 0.2s ease;
        }

        .footer-link-group a:hover,
        .footer-link-group a:focus-visible {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            transform: translateX(4px);
            outline: none;
        }

        .landing-footer-bottom {
            background: var(--wine);
            border-top: 1px solid rgba(176, 227, 250, 0.2);
            font-size: 0.88rem;
        }

        .mobile-toolbar {
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .mobile-toolbar .btn {
            white-space: nowrap;
        }

        .mobile-portal-dock {
            background: var(--wine);
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 -18px 40px rgba(2, 12, 28, 0.24);
        }

        .mobile-portal-dock-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
            padding: 0.55rem 0.15rem calc(0.55rem + env(safe-area-inset-bottom));
        }

        .mobile-portal-dock-item,
        .mobile-portal-dock-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 4.15rem;
            border-radius: 1rem;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.78);
            text-decoration: none;
            padding: 0.45rem 0.35rem;
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .mobile-portal-dock-item:hover,
        .mobile-portal-dock-item.active,
        .mobile-portal-dock-button:hover,
        .mobile-portal-dock-button:focus-visible {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
            outline: none;
        }

        .mobile-portal-dock-icon {
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

        .mobile-portal-dock-label {
            margin-top: 0.32rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .mobile-portal-hero {
            background: linear-gradient(90deg, #07116f 0%, #0b45b8 58%, #0a86b7 100%);
            color: #fff;
            border-radius: 0.95rem;
            box-shadow: 0 18px 34px rgba(4, 0, 120, 0.14);
        }

        .mobile-portal-hero .text-secondary,
        .mobile-portal-hero .text-white-50 {
            color: rgba(255, 255, 255, 0.76) !important;
        }

        .mobile-portal-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            color: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.14);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .mobile-portal-stat {
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.56);
            border: 1px solid rgba(7, 17, 111, 0.12);
            padding: 0.8rem 0.7rem;
            min-height: 100%;
        }

        .mobile-portal-stat-value {
            display: block;
            font-size: 1.12rem;
            font-weight: 700;
            line-height: 1.1;
            color: #111827;
        }

        .mobile-portal-stat-label {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #111827;
        }

        .offcanvas-mobile-nav {
            background: linear-gradient(180deg, #07116f 0%, #0b45b8 100%);
            color: #fff;
        }

        .offcanvas-mobile-nav .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .offcanvas-mobile-nav .nav-link {
            color: rgba(255, 255, 255, 0.84);
            border-radius: 1rem;
            padding: 0.85rem 1rem;
            transition: all 0.2s ease;
        }

        .offcanvas-mobile-nav .nav-link:hover,
        .offcanvas-mobile-nav .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.13);
            transform: translateX(4px);
        }

        .offcanvas-mobile-nav .page-card {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: none;
        }

        table[data-mobile-card-table] tbody tr.table-light th {
            background: rgba(156, 122, 0, 0.24);
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }

            .landing-page {
                background-attachment: scroll;
            }
        }

        @media (max-width: 991.98px) {
            .main-wrapper,
            .site-container {
                width: 100%;
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .sidebar {
                position: static;
                top: auto;
                height: auto;
                overflow-y: visible;
                align-self: stretch;
            }

            .content-panel {
                border-radius: 1rem;
            }

            .school-identity-shell {
                min-height: 4.25rem;
                align-items: flex-start;
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem 0;
            }

            .school-identity-title {
                font-size: clamp(1.65rem, 8vw, 3rem);
                line-height: 1;
            }

            .school-identity-actions {
                justify-content: flex-start;
            }

            .footer-link-group {
                padding-left: 0.2rem;
            }

            .app-main {
                padding-bottom: 6.75rem !important;
            }
        }

        @media (min-width: 992px) {
            .school-system-bar > .container-fluid,
            .navbar > .container-fluid {
                width: 95%;
                max-width: 1800px;
                margin-left: auto;
                margin-right: auto;
                padding-left: clamp(1.5rem, 3vw, 3.125rem) !important;
                padding-right: clamp(1.5rem, 3vw, 3.125rem) !important;
            }

            .app-workspace-fluid {
                padding-left: calc(270px + 1.5rem);
                padding-right: clamp(1.5rem, 2.5vw, 3.125rem);
            }

            .app-workspace-wrapper {
                width: 100%;
                padding-left: 0;
                padding-right: 0;
            }

            .app-main .content-panel {
                width: 100%;
                max-width: none;
            }

            .sidebar {
                top: 4.6rem;
                height: calc(100vh - 4.6rem);
            }

            .row.min-vh-100 {
                min-height: calc(100vh - 4.6rem) !important;
            }
        }

        @media (max-width: 767.98px) {
            .mobile-toolbar {
                position: sticky;
                top: 0;
                z-index: 1030;
            }

            .content-panel {
                padding: 0.85rem !important;
                border-radius: 0.95rem;
            }

            .page-card {
                border-radius: 1rem;
                box-shadow: 0 10px 20px rgba(4, 0, 120, 0.08);
            }

            .table > :not(caption) > * > * {
                padding: 0.7rem 0.75rem;
            }

            table[data-mobile-card-table] thead {
                display: none;
            }

            table[data-mobile-card-table],
            table[data-mobile-card-table] tbody,
            table[data-mobile-card-table] tr,
            table[data-mobile-card-table] td,
            table[data-mobile-card-table] th {
                display: block;
                width: 100%;
            }

            table[data-mobile-card-table] {
                border-collapse: separate;
                border-spacing: 0;
            }

            table[data-mobile-card-table] tbody tr:not(.table-light) {
                margin: 0.75rem 0.85rem;
                border: 1px solid rgba(4, 0, 120, 0.14);
                border-radius: 1rem;
                overflow: hidden;
                background: #fff;
                box-shadow: 0 12px 22px rgba(4, 0, 120, 0.08);
            }

            table[data-mobile-card-table] tbody tr.table-light {
                margin: 0.85rem 0.85rem 0.3rem;
                border: 0;
                border-radius: 0.9rem;
                overflow: hidden;
            }

            table[data-mobile-card-table] tbody tr.table-light th {
                border: 0;
                padding: 0.75rem 0.9rem;
            }

            table[data-mobile-card-table] td {
                border: 0;
                padding: 0.68rem 0.9rem;
                text-align: left !important;
            }

            table[data-mobile-card-table] td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 0.25rem;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--muted);
            }

            table[data-mobile-card-table] td .student-id-display {
                text-align: left;
            }

            table[data-mobile-card-table] td .d-inline-flex {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            table[data-mobile-card-table] td .d-inline-flex .btn,
            table[data-mobile-card-table] td .d-inline-flex form {
                width: 100%;
            }

            table[data-mobile-card-table] td .d-inline-flex form .btn {
                width: 100%;
            }

            .hero-stage {
                border-radius: 1.5rem;
                margin-top: 1rem;
            }

            .landing-section {
                padding: 3.5rem 0;
            }

            .landing-page {
                background-attachment: scroll;
            }

            .brand-crest {
                width: 3.6rem;
                height: 3.6rem;
            }

            .campus-gallery-hero {
                min-height: 220px;
                aspect-ratio: 16 / 9;
                border-radius: 1.4rem;
            }

            .campus-gallery-caption {
                max-width: 100%;
                left: 0.85rem;
                right: 0.85rem;
                bottom: 0.85rem;
                padding: 1rem;
            }

            .campus-gallery-detail {
                font-size: 0.95rem;
            }

            .landing-story-media {
                min-height: 190px;
                border-radius: 1.1rem;
            }

            .landing-footer-shell {
                padding-top: 2rem;
                padding-bottom: 2rem;
            }

            .footer-brand-panel,
            .footer-cta-panel {
                padding: 1.15rem;
            }

            .footer-link-group a {
                padding-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .mobile-toolbar .container-fluid {
                padding-left: 0.85rem;
                padding-right: 0.85rem;
            }

            .mobile-toolbar .btn {
                padding-inline: 0.7rem;
            }

            .content-panel {
                padding: 0.75rem !important;
            }

            .page-card {
                border-radius: 0.9rem;
            }

            .student-id-display {
                font-size: 0.95rem;
                letter-spacing: 0.01em;
            }

            .mobile-portal-dock-grid {
                gap: 0.42rem;
                padding-inline: 0;
            }

            .mobile-portal-dock-item,
            .mobile-portal-dock-button {
                min-height: 3.95rem;
                padding-inline: 0.25rem;
            }

            .mobile-portal-dock-label {
                font-size: 0.66rem;
            }
        }
    </style>
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/desktop.css') }}">
</head>
@php
    $layoutUser = auth()->user();
    $fullGuest = View::hasSection('full_guest');
    $workspacePreview = View::hasSection('workspace_preview');
    $centeredGuest = View::hasSection('centered_guest');
    $showWorkspaceShell = $layoutUser && ! $fullGuest;
@endphp
<body class="{{ $showWorkspaceShell && $layoutUser?->isAdmin() ? 'admin-workspace' : '' }}">
    @if ($showWorkspaceShell)
        @php
            $pendingAccountCount = $layoutUser->isAdmin()
                ? rescue(fn () => \App\Models\User::query()->where('role', 'alumni')->where('account_status', 'pending')->count(), 0, false)
                : 0;
            $latestPendingAccountId = $layoutUser->isAdmin()
                ? rescue(fn () => (int) \App\Models\User::query()->where('role', 'alumni')->where('account_status', 'pending')->max('id'), 0, false)
                : 0;
            $pendingRecordRequestCount = $layoutUser->isAdmin()
                ? rescue(fn () => \App\Models\RecordRequest::query()->where('status', 'pending')->count(), 0, false)
                : 0;
            $latestPendingRecordRequestId = $layoutUser->isAdmin()
                ? rescue(fn () => (int) \App\Models\RecordRequest::query()->where('status', 'pending')->max('id'), 0, false)
                : 0;
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
                    <h1 id="mobileSidebarLabel" class="h5 fw-bold mb-0">{{ auth()->user()->isAdmin() ? 'Admin Workspace' : 'Alumni Portal' }}</h1>
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
                        <a href="{{ route('events.index') }}" class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">Events</a>
                        <a href="{{ route('announcements.index') }}" class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">Announcements</a>
                        <a href="{{ route('activities.index') }}" class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}">Activities</a>
                        <a href="{{ route('admin.settings.landing-video.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.landing-video.*', 'admin.settings.landing-profiles.*') ? 'active' : '' }}">School Administration</a>
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
                        <a href="{{ route('portal.dashboard') }}" class="mobile-portal-dock-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                            <span class="mobile-portal-dock-icon">H</span>
                            <span class="mobile-portal-dock-label">Home</span>
                        </a>
                        <a href="{{ route('portal.requests.index') }}" class="mobile-portal-dock-item {{ request()->routeIs('portal.requests.*') ? 'active' : '' }}">
                            <span class="mobile-portal-dock-icon">R</span>
                            <span class="mobile-portal-dock-label">Requests</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="mobile-portal-dock-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <span class="mobile-portal-dock-icon">P</span>
                            <span class="mobile-portal-dock-label">Profile</span>
                        </a>
                        <button
                            type="button"
                            class="mobile-portal-dock-button"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#mobileSidebar"
                            aria-controls="mobileSidebar"
                        >
                            <span class="mobile-portal-dock-icon">M</span>
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
                        <h1 class="h4 fw-bold mb-2">{{ auth()->user()->isAdmin() ? 'Admin Workspace' : 'Alumni Portal' }}</h1>
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
                            <a href="{{ route('events.index') }}" class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">Events</a>
                            <a href="{{ route('announcements.index') }}" class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">Announcements</a>
                        <a href="{{ route('activities.index') }}" class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}">Activities</a>
                        <a href="{{ route('admin.settings.landing-video.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.landing-video.*', 'admin.settings.landing-profiles.*') ? 'active' : '' }}">School Administration</a>
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

                <main class="app-main col-12 {{ $workspacePreview ? 'p-0' : 'p-3 p-lg-4 p-xl-5' }}">
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
                <nav class="navbar navbar-expand-lg bg-white border-bottom">
                    <div class="main-wrapper">
                        <div class="main-wrapper d-flex align-items-center justify-content-between">
                            <a class="navbar-brand fw-semibold text-white" href="{{ route('home') }}">HOME</a>
                            <div class="d-flex gap-2">
                                <a href="{{ route('portal.login') }}" class="btn btn-primary">Alumni Portal</a>
                            </div>
                        </div>
                    </div>
                </nav>
            @endunless

            @if ($fullGuest)
                <main class="app-main flex-grow-1">
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
                <main class="app-main {{ $centeredGuest ? 'guest-centered-main' : 'main-wrapper' }} flex-grow-1 d-flex align-items-center justify-content-center py-5">
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
                                            <a href="{{ route('portal.login') }}" class="btn btn-light">Alumni Login</a>
                                            <a href="{{ route('portal.register') }}" class="btn btn-outline-light">Create Alumni Account</a>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="page-card p-4">
                                            <div class="text-secondary small mb-2">Access points</div>
                                            <div class="d-grid gap-2">
                                                <a href="{{ route('portal.login') }}" class="btn btn-outline-success">Portal Login</a>
                                                <a href="{{ route('portal.register') }}" class="btn btn-outline-secondary">Claim Alumni Account</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register(@json(asset('firebase-messaging-sw.js'))).catch(function () {
                        return null;
                    });
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

                    var notification = new Notification('New account approval request', {
                        body: (request.name || 'New applicant') + ' submitted an alumni account request.',
                        tag: 'pending-account-' + request.id,
                    });

                    notification.onclick = function () {
                        window.focus();
                        window.location.href = request.review_url || pendingUrl;
                    };
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
                    if (isPolling) {
                        return;
                    }

                    isPolling = true;

                    fetch(notificationUrl + '?after=' + encodeURIComponent(lastSeenId), {
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

                    var notification = new Notification('New record request', {
                        body: (recordRequest.alumni_name || 'An alumni') + ' submitted ' + (recordRequest.request_type || 'a document request') + '.',
                        tag: 'record-request-' + recordRequest.id,
                    });

                    notification.onclick = function () {
                        window.focus();
                        window.location.href = recordRequest.review_url || requestsUrl;
                    };
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
                    if (isPolling) {
                        return;
                    }

                    isPolling = true;

                    fetch(notificationUrl + '?after=' + encodeURIComponent(lastSeenId), {
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

                    var notification = new Notification('Request update from admin', {
                        body: (update.request_type || 'Your request') + ' is now ' + (update.status || 'updated') + '.',
                        tag: 'alumni-request-update-' + update.id + '-' + update.updated_timestamp,
                    });

                    notification.onclick = function () {
                        window.focus();
                        window.location.href = update.review_url || dashboardUrl;
                    };
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
                    if (isPolling) {
                        return;
                    }

                    isPolling = true;

                    fetch(notificationUrl + '?after=' + encodeURIComponent(lastSeenTimestamp), {
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

</body>
</html>
