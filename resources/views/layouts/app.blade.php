<!DOCTYPE html>
<html lang="en">
<head>
    <script>document.documentElement.classList.add('quizsnap-js');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $theme = $theme ?? app(\App\Services\ThemeService::class)->activePreset();
        $themePrimary = $theme['primary'] ?? [];
        $faviconThemeColor = \App\Support\Favicon::themeColor();
    @endphp
    <meta name="theme-color" content="{{ $faviconThemeColor }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="QuizSnap">
    <meta name="format-detection" content="telephone=no">
    <title>@yield('title', 'QuizSnap')</title>
    @include('partials.seo')
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $theme['fonts']['url'] ?? 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap' }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Tailwind via CDN, with project theme config (no local Tailwind build) --}}
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['{{ $theme['fonts']['sans'] ?? 'Inter' }}', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['{{ $theme['fonts']['display'] ?? 'Outfit' }}', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        offwhite: '{{ $theme['bg'] ?? '#fafaf9' }}',
                        homeBlue: '{{ $themePrimary[600] ?? '#2563eb' }}',
                        homeYellow: '{{ $theme['brand'] ?? '#FFD500' }}',
                        primary: {
                            50: '{{ $themePrimary[50] ?? '#eff6ff' }}',
                            100: '{{ $themePrimary[100] ?? '#dbeafe' }}',
                            200: '{{ $themePrimary[200] ?? '#bfdbfe' }}',
                            300: '{{ $themePrimary[300] ?? '#93c5fd' }}',
                            400: '{{ $themePrimary[400] ?? '#60a5fa' }}',
                            500: '{{ $themePrimary[500] ?? '#3b82f6' }}',
                            600: '{{ $themePrimary[600] ?? '#2563eb' }}',
                            700: '{{ $themePrimary[700] ?? '#1d4ed8' }}',
                            800: '{{ $themePrimary[800] ?? '#1e40af' }}',
                            900: '{{ $themePrimary[900] ?? '#1e3a8a' }}',
                        },
                        action: {
                            DEFAULT: '#eab308',
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                    },
                },
            },
        };
    </script>
    <script>
        (function () {
            var tailwindCdnProductionWarn = /cdn\.tailwindcss\.com should not be used in production/i;
            var originalWarn = console.warn.bind(console);
            console.warn = function () {
                var message = arguments[0];
                if (typeof message === 'string' && tailwindCdnProductionWarn.test(message)) {
                    return;
                }
                return originalWarn.apply(console, arguments);
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

    @include('partials.theme-styles')
    @include('partials.zoom-lock')

    {{-- Non-Tailwind custom styles --}}
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/forms.css') }}">

    {{-- Shared dashboard/examiner sidebar styles (active link highlighting) --}}
    <style>
        .examiner-wrap {
            background: #f4f5f7;
        }
        .examiner-sidebar {
            background: #ffffff;
            border-right: 1px solid #eef0f3;
            box-shadow: none;
            overflow-x: hidden;
        }
        .examiner-nav-text {
            display: inline-block;
            max-width: 12rem;
            overflow: hidden;
            white-space: nowrap;
            opacity: 1;
            transform: translateX(0);
            transition:
                opacity .28s cubic-bezier(.22, 1, .36, 1),
                max-width .36s cubic-bezier(.22, 1, .36, 1),
                transform .36s cubic-bezier(.22, 1, .36, 1);
        }
        .examiner-nav-link {
            color: #6b7280; /* gray-500 */
            background: transparent !important;
            border-radius: 0.85rem;
            font-weight: 500;
        }
        .examiner-nav-link:hover {
            background: transparent !important;
            color: #111827; /* gray-900 */
        }
        .examiner-nav-link svg,
        .examiner-nav-link > i {
            box-sizing: border-box;
            width: 1.85rem !important;
            height: 1.85rem !important;
            min-width: 1.85rem;
            padding: 0.4rem;
            border-radius: 9999px;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: inherit;
            transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
        }
        .examiner-nav-link > i {
            font-size: 0.75rem;
            line-height: 1;
            text-align: center;
            width: 1.85rem !important;
        }
        .examiner-nav-link:hover svg,
        .examiner-nav-link:hover > i {
            background-color: #f3f4f6;
        }
        .examiner-nav-link--active {
            background: transparent !important;
            color: #111827;
            font-weight: 700;
        }
        .examiner-nav-link--active .examiner-nav-text {
            color: #111827;
        }
        .examiner-nav-link--active svg,
        .examiner-nav-link--active > i {
            background-color: #111827 !important;
            color: #ffffff !important;
            box-shadow: none;
        }
        .examiner-nav-link--active:hover svg,
        .examiner-nav-link--active:hover > i {
            background-color: #111827 !important;
            color: #ffffff !important;
        }
        .examiner-sidebar-nav {
            padding-top: 0.25rem;
            padding-bottom: 0.5rem;
        }
        .examiner-sidebar-nav > ul {
            display: flex;
            flex-direction: column;
            gap: 0.05rem;
        }
        /* Internal list scroll (students, quizzes, etc.) — scrollbar hidden */
        .dashboard-list-scroll {
            overflow-y: auto;
            overscroll-behavior: contain;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .dashboard-list-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }
        .examiner-sidebar-header {
            height: 4.25rem;
        }
        .examiner-sidebar-brand .quizsnap-brand-mark,
        .examiner-sidebar-brand img {
            border-radius: 9999px;
        }
        .examiner-sidebar-brand .quizsnap-brand-mark--plain {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #111827;
            color: #fff;
            font-weight: 800;
        }
        .examiner-sidebar-brand .quizsnap-brand-logo__wordmark,
        .examiner-sidebar-brand-text {
            white-space: nowrap;
            opacity: 1;
            max-width: 9rem;
            transform: translateX(0);
            transition:
                opacity .28s cubic-bezier(.22, 1, .36, 1),
                max-width .36s cubic-bezier(.22, 1, .36, 1),
                transform .36s cubic-bezier(.22, 1, .36, 1);
        }
        .examiner-sidebar-brand-logo {
            gap: 0.55rem;
            transition: gap .32s cubic-bezier(.22, 1, .36, 1);
        }
        .examiner-sidebar-brand .quizsnap-wordmark {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .examiner-sidebar-brand .theme-wordmark-a { color: #0f172a; }
        .examiner-sidebar-brand .theme-wordmark-b { color: #ca8a04; }
        /* When sidebar is collapsed on desktop, show only icons (no text labels) */
        @media (min-width: 768px) {
            .examiner-sidebar {
                width: 16rem;
                min-width: 16rem;
                max-width: 16rem;
                transition:
                    width .42s cubic-bezier(.22, 1, .36, 1),
                    min-width .42s cubic-bezier(.22, 1, .36, 1),
                    max-width .42s cubic-bezier(.22, 1, .36, 1),
                    margin .42s cubic-bezier(.22, 1, .36, 1),
                    height .42s cubic-bezier(.22, 1, .36, 1),
                    border-radius .42s cubic-bezier(.22, 1, .36, 1),
                    border-color .35s ease,
                    box-shadow .35s cubic-bezier(.22, 1, .36, 1),
                    padding .35s cubic-bezier(.22, 1, .36, 1);
            }
            .examiner-sidebar-header,
            .examiner-sidebar-nav,
            .examiner-sidebar-footer,
            .examiner-nav-link {
                transition:
                    padding .36s cubic-bezier(.22, 1, .36, 1),
                    justify-content .36s cubic-bezier(.22, 1, .36, 1),
                    gap .32s cubic-bezier(.22, 1, .36, 1);
            }
            .examiner-sidebar--collapsed {
                width: 4.5rem !important;
                min-width: 4.5rem !important;
                max-width: 4.5rem !important;
                margin: 0.75rem 0 0.75rem 0.75rem;
                height: calc(100% - 1.5rem) !important;
                border-radius: 1.5rem;
                border: 1px solid #eef0f3;
                box-shadow: none;
            }
            .examiner-sidebar--collapsed .examiner-nav-text,
            .examiner-sidebar--collapsed .examiner-sidebar-chevron {
                opacity: 0 !important;
                max-width: 0 !important;
                transform: translateX(-6px);
                pointer-events: none;
                overflow: hidden;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-chevron {
                display: none !important;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-brand-text,
            .examiner-sidebar--collapsed .quizsnap-wordmark,
            .examiner-sidebar--collapsed .quizsnap-brand-logo__wordmark {
                opacity: 0 !important;
                max-width: 0 !important;
                transform: translateX(-4px);
                pointer-events: none;
                overflow: hidden;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-brand-logo {
                gap: 0;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-header {
                justify-content: center;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-brand {
                justify-content: center;
                width: 100%;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-nav {
                padding-left: 0.55rem;
                padding-right: 0.55rem;
            }
            .examiner-sidebar--collapsed .examiner-nav-link {
                justify-content: center;
                padding-left: 0.35rem;
                padding-right: 0.35rem;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-footer {
                padding-left: 0.55rem;
                padding-right: 0.55rem;
            }
            .examiner-sidebar--collapsed .examiner-sidebar-footer .examiner-nav-link {
                justify-content: center;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .examiner-sidebar,
            .examiner-sidebar-header,
            .examiner-sidebar-nav,
            .examiner-sidebar-footer,
            .examiner-nav-link,
            .examiner-nav-text,
            .examiner-sidebar-brand .quizsnap-brand-logo__wordmark,
            .examiner-sidebar-brand-text,
            .examiner-sidebar-brand-logo {
                transition-duration: 0.01ms !important;
            }
        }

        /* Header chrome: same horizontal width as body content */
        .dashboard-chrome-header {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            margin: 0;
            min-height: 3.75rem;
            box-shadow: none;
            width: 100%;
        }
        .dashboard-chrome-toggle {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            border: 1.5px solid #111827;
            background: #ffffff;
            color: #111827;
            box-shadow: none;
        }
        .dashboard-chrome-search {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 0;
            width: min(20rem, 38vw);
            background: #f3f4f6;
            border-radius: 0.85rem;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }
        .dashboard-chrome-search input {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0.6rem 3rem 0.6rem 1rem;
            font-size: 0.875rem;
            color: #111827;
            outline: none;
            border-radius: 0.85rem;
        }
        .dashboard-chrome-search button {
            position: absolute;
            right: 0.3rem;
            width: 2rem;
            height: 2rem;
            border-radius: 0.65rem;
            background: #111827;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }
        .dashboard-chrome-bell {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #374151;
            box-shadow: none;
        }
        .dashboard-chrome-search.is-disabled {
            opacity: 0.55;
            background: #e5e7eb;
            border-color: #d1d5db;
            pointer-events: none;
        }
        .dashboard-chrome-search.is-disabled input {
            color: #9ca3af;
            cursor: not-allowed;
        }
        .dashboard-chrome-search.is-disabled input::placeholder {
            color: #9ca3af;
        }
        .dashboard-chrome-search.is-disabled button {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .dashboard-chrome-profile {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.25rem 0.65rem 0.25rem 0.25rem;
            border-radius: 0.85rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            box-shadow: none;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .dashboard-chrome-profile:hover {
            background: #eef2f7;
            border-color: #dbe3ee;
        }
        .dashboard-chrome-profile[aria-expanded="true"] {
            background: #fff;
            border-color: #d1d5db;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.18);
        }
        .dashboard-chrome-profile .profile-chevron {
            display: inline-block;
            transition: transform .2s cubic-bezier(.22, 1, .36, 1);
        }
        .dashboard-chrome-profile[aria-expanded="true"] .profile-chevron {
            transform: rotate(180deg);
        }
        .profile-menu-panel {
            position: absolute;
            right: 0;
            top: calc(100% + 0.5rem);
            z-index: 200;
            width: 15.5rem;
            border-radius: 1rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: rgba(255, 255, 255, 0.98);
            box-shadow:
                0 18px 40px -18px rgba(15, 23, 42, 0.28),
                0 4px 12px -4px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-8px) scale(0.98);
            transform-origin: top right;
            transition:
                opacity .18s cubic-bezier(.22, 1, .36, 1),
                transform .18s cubic-bezier(.22, 1, .36, 1),
                visibility .18s;
        }
        .profile-menu-panel.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }
        .profile-menu-head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 0.95rem 0.85rem;
            background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
            border-bottom: 1px solid #f1f5f9;
        }
        .profile-menu-head-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            overflow: hidden;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .profile-menu-list {
            padding: 0.4rem;
            display: grid;
            gap: 0.15rem;
        }
        .profile-menu-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
            padding: 0.65rem 0.7rem;
            border-radius: 0.7rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #334155;
            text-decoration: none;
            white-space: nowrap;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: left;
            transition: background-color .15s ease, color .15s ease, transform .15s ease;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        @media (max-width: 1023px) {
            .profile-menu-item {
                min-height: 2.75rem;
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
            .profile-menu-panel {
                z-index: 250;
            }
        }
        .profile-menu-item:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .profile-menu-item:active {
            transform: scale(0.98);
        }
        .profile-menu-item-icon {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.5rem;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            background: #f1f5f9;
            color: #64748b;
            font-size: 0.7rem;
            transition: background-color .15s ease, color .15s ease;
        }
        .profile-menu-item:hover .profile-menu-item-icon {
            background: #e2e8f0;
            color: #334155;
        }
        .profile-menu-foot {
            padding: 0.35rem 0.4rem 0.45rem;
            border-top: 1px solid #f1f5f9;
        }
        .profile-menu-item--danger {
            color: #be123c;
        }
        .profile-menu-item--danger:hover {
            background: #fff1f2;
            color: #9f1239;
        }
        .profile-menu-item--danger .profile-menu-item-icon {
            background: #ffe4e6;
            color: #e11d48;
        }
        .profile-menu-item--danger:hover .profile-menu-item-icon {
            background: #fecdd3;
            color: #be123c;
        }
        @media (prefers-reduced-motion: reduce) {
            .profile-menu-panel,
            .dashboard-chrome-profile .profile-chevron,
            .profile-menu-item {
                transition: none;
            }
        }

        /* Hide vertical scrollbar on coordinator/examiner sidebar; content still scrolls */
        .examiner-sidebar,
        .examiner-sidebar-inner,
        .examiner-sidebar-nav {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .examiner-sidebar::-webkit-scrollbar,
        .examiner-sidebar-inner::-webkit-scrollbar,
        .examiner-sidebar-nav::-webkit-scrollbar {
            display: none;
        }

        /* Hide horizontal scrollbar for student dashboard chips while keeping scroll */
        .student-chip-scroll {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;     /* Firefox */
        }
        .student-chip-scroll::-webkit-scrollbar {
            display: none;             /* Chrome, Safari, Opera */
        }

        /* Mobile: prevent horizontal scroll, fluid app-like layout */
        @media (max-width: 767px) {
            html, body {
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }
            .examiner-wrap {
                overflow-x: hidden;
                min-height: 100vh;
                min-height: 100dvh;
            }
            /* Sidebar: fixed drawer, off-screen when collapsed */
            .examiner-sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 40;
                width: min(85vw, 18rem) !important;
                min-width: 0 !important;
                margin: 0 !important;
                height: 100% !important;
                border-radius: 0 !important;
                transition: transform 0.42s cubic-bezier(.22, 1, .36, 1), box-shadow 0.35s cubic-bezier(.22, 1, .36, 1);
                box-shadow: none;
            }
            .examiner-sidebar.examiner-sidebar--collapsed {
                transform: translateX(-100%);
                pointer-events: none;
            }
            .examiner-sidebar:not(.examiner-sidebar--collapsed) {
                transform: translateX(0);
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            }
            .examiner-overlay {
                transition: opacity 0.32s cubic-bezier(.22, 1, .36, 1);
            }
            .examiner-main,
            .examiner-main-content {
                overflow-x: hidden;
                min-width: 0;
            }
            .examiner-page {
                overflow-x: hidden;
                max-width: 100%;
            }
            .examiner-dashboard-content {
                overflow-x: hidden;
                max-width: 100%;
            }
            .examiner-overlay:not(.hidden) {
                display: block !important;
                pointer-events: auto;
            }
            .examiner-sidebar-nav a {
                min-height: 44px;
                -webkit-tap-highlight-color: transparent;
            }
            .dashboard-chrome-search {
                width: 100%;
                max-width: none;
            }
        }

        /* Quiz create/edit: single scrollbar (window only), no inline scroll, no grey gap at bottom */
        .examiner-wrap--doc-scroll {
            min-height: 100vh;
            height: auto;
            overflow: visible;
        }
        .examiner-wrap--doc-scroll .examiner-sidebar {
            position: sticky;
            top: 0;
            align-self: flex-start;
            max-height: 100vh;
        }
        .examiner-wrap--doc-scroll .examiner-main {
            min-height: 0;
            flex: 1 1 auto;
        }
        .examiner-main-content--doc-scroll {
            overflow: visible !important;
            min-height: auto;
            flex: none;
            background: #fff !important;
        }
    </style>
    <style>
        /* No flash: key off html.quizsnap-js (set by head script before body paints). When JS is off, head script does not run. */
        .quizsnap-noscript-msg { display: none !important; }
        html.quizsnap-js .quizsnap-noscript-msg { display: none !important; }
        html:not(.quizsnap-js) #quizsnap-app { display: none !important; }
        html:not(.quizsnap-js) .quizsnap-noscript-msg { display: flex !important; }
        .quizsnap-blocked #quizsnap-app { display: none !important; }
        .quizsnap-app--hidden { display: none !important; }
        .quizsnap-blocked html, .quizsnap-blocked body { overflow: hidden !important; position: fixed !important; width: 100% !important; height: 100% !important; }
        #quizsnap-block-overlay { display: none; position: fixed; inset: 0; z-index: 99999; background: #fafaf9; align-items: center; justify-content: center; padding: 1.5rem; box-sizing: border-box; overflow: hidden; }
        .quizsnap-blocked #quizsnap-block-overlay { display: flex !important; }
        #quizsnap-block-overlay .quizsnap-block-inner { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; max-width: 90vw; max-height: 90vh; }
        #quizsnap-block-overlay .quizsnap-block-icon { font-size: 5rem; line-height: 1; font-weight: 700; color: #dc2626; margin-bottom: 1rem; }
        #quizsnap-block-overlay #quizsnap-block-message { font-size: 1.125rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem; }
        #quizsnap-block-overlay .quizsnap-block-sub { font-size: 0.9375rem; color: #6b7280; margin-bottom: 1.5rem; }
        #quizsnap-block-overlay .quizsnap-block-footer { margin-top: 1.5rem; font-size: 0.875rem; color: #6b7280; }
        .quizsnap-select-none { -webkit-user-select: none; user-select: none; }
        .quizsnap-select-none input, .quizsnap-select-none textarea { -webkit-user-select: text; user-select: text; }
    </style>
    @stack('copy_restrict_styles')
    @stack('styles')
</head>
@php
    // Only enforce the quiz screen-size guard on actual quiz-taking pages.
    $shouldGuardQuiz = request()->routeIs('student.quiz.show') || request()->routeIs('student.quiz.ready');
    $skipGuard = ! $shouldGuardQuiz;
    $quizAllowsMobile = $quizAllowsMobile ?? false;
@endphp
<body class="font-sans text-gray-800 quizsnap-nojs @yield('body_extra_class') @yield('body_class', 'bg-offwhite')" data-skip-guard="{{ $skipGuard ? 'true' : 'false' }}" data-quiz-allows-mobile="{{ $quizAllowsMobile ? 'true' : 'false' }}">
    <noscript>
        <div class="fixed inset-0 z-[99999] flex items-center justify-center bg-offwhite p-6" role="alert">
            <div class="bg-white border border-gray-200 rounded-xl p-8 max-w-md text-center shadow-lg">
                <h1 class="text-xl font-bold text-gray-900 mb-2">JavaScript required</h1>
                <p class="text-gray-600 mb-4">Please enable JavaScript to use this website. Do not use extensions that disable JavaScript or allow copying—doing so may result in losing your quiz.</p>
                <p class="text-sm text-gray-500">Reload the page after enabling JavaScript.</p>
            </div>
        </div>
    </noscript>
    <!-- No-JS fallback (shown via CSS when body has quizsnap-nojs) -->
    <div class="quizsnap-noscript-msg hidden fixed inset-0 z-[99999] bg-offwhite items-center justify-center p-6" aria-live="polite">
        <div class="bg-white border border-gray-200 rounded-xl p-8 max-w-md text-center shadow-lg">
            <h1 class="text-xl font-bold text-gray-900 mb-2">JavaScript required</h1>
            <p class="text-gray-600 mb-4">Please enable JavaScript to use this website. Do not use extensions that disable JavaScript or allow copying—doing so may result in losing your quiz.</p>
            <p class="text-sm text-gray-500">Reload the page after enabling JavaScript.</p>
        </div>
    </div>
    {{-- Legacy mobile-only block overlay removed to allow full mobile support --}}
    @yield('copy_restriction_modal')
    <!-- Main content (staff/docu-mentor: always visible; quiz: shown when JS allows and device allowed) -->
    {{-- Always show main app container (no JS/viewport gating on students' pages) --}}
    <div id="quizsnap-app" class="quizsnap-app quizsnap-app--contain">
    {{-- Flash messages: one @if / @endif only to avoid PHP 8.4 parse error in compiled view --}}
    @php
        $hasFlash = session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info');
    @endphp
    @if($hasFlash)
    <div id="flash-container" class="fixed top-[4.25rem] md:top-4 right-3 left-3 sm:left-auto sm:max-w-sm z-[90] flex flex-col gap-3 pointer-events-none" role="status" aria-label="Notification">
        @php
            if (session('success')) { echo '<div class="toast toast-success flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-success-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('success')).'</span></div>'; }
            if (session('error')) { echo '<div class="toast toast-error flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-danger-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('error')).'</span></div>'; }
            if (session('warning')) { echo '<div class="toast toast-warning flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-warning-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('warning')).'</span></div>'; }
            if (session('info')) { echo '<div class="toast toast-info flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-primary-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('info')).'</span></div>'; }
        @endphp
    </div>
    @endif

    @yield('content')
    
    </div><!-- /#quizsnap-app -->
    <script src="{{ asset('js/quizsnap-guard.js') }}"></script>
    <script src="{{ asset('js/student-feedback.js') }}?v={{ filemtime(public_path('js/student-feedback.js')) }}"></script>
    <script src="{{ asset('js/quizsnap-presence.js') }}?v={{ filemtime(public_path('js/quizsnap-presence.js')) }}" defer></script>
    @yield('copy_restriction_script')
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/quizsnap-logout.js') }}?v={{ filemtime(public_path('js/quizsnap-logout.js')) }}" defer></script>
    <script>window.QuizSnapLive=window.QuizSnapLive||{refreshers:[],registerRefresher:function(fn){if(typeof fn==='function')this.refreshers.push(fn);}};</script>
    @stack('scripts')
    @include('partials.support-issue-modal')
    @if(\App\Support\LiveSupportAccess::isEnabled())
    @include('partials.support-live-chat')
    @php
        $liveSupportContext = [];
        if (session('student_id')) {
            $loggedInStudent = \App\Models\Student::find(session('student_id'));
            if ($loggedInStudent) {
                $liveSupportContext = array_filter([
                    'name' => $loggedInStudent->student_name,
                    'index_number' => $loggedInStudent->index_number,
                    'phone' => $loggedInStudent->phone_contact,
                    'email' => $loggedInStudent->email,
                ]);
            }
        }
    @endphp
    <script>window.QuizSnapSupportConfig = @json(\App\Support\SupportContact::clientConfig($liveSupportContext));</script>
    <script>window.LIVE_SUPPORT_PUBLIC = true;</script>
    <script src="{{ asset('js/support-contact.js') }}?v={{ filemtime(public_path('js/support-contact.js')) }}"></script>
    <script src="{{ asset('js/support-live-sounds.js') }}?v={{ filemtime(public_path('js/support-live-sounds.js')) }}"></script>
    <script src="{{ asset('js/support-live-media.js') }}?v={{ filemtime(public_path('js/support-live-media.js')) }}"></script>
    <script src="{{ asset('js/support-live-compose.js') }}?v={{ filemtime(public_path('js/support-live-compose.js')) }}"></script>
    <script src="{{ asset('js/support-live-chat.js') }}?v={{ filemtime(public_path('js/support-live-chat.js')) }}"></script>
    @else
    <script>window.LIVE_SUPPORT_PUBLIC = false;</script>
    @endif

    @if(($reverbClientConfig = \App\Services\ReverbClientConfig::clientConfig()) !== null)
    <!-- Real-time: Reverb WebSocket + partial live refresh (no full-page reload) -->
    <script>
    window.REVERB_CONFIG = {
        key: @json($reverbClientConfig['key']),
        host: @json($reverbClientConfig['host']),
        port: @json($reverbClientConfig['port']),
        scheme: @json($reverbClientConfig['scheme'])
    };
    </script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/quizsnap-reverb.js') }}?v={{ filemtime(public_path('js/quizsnap-reverb.js')) }}"></script>
    <script src="{{ asset('js/quizsnap-live.js') }}?v={{ filemtime(public_path('js/quizsnap-live.js')) }}" defer></script>
    @else
    <script src="{{ asset('js/quizsnap-live.js') }}" defer></script>
    @endif
    @stack('scripts-after-reverb')

    <!-- Auto-dismiss toast notifications after 4s -->
    <script>
    (function() {
        var container = document.getElementById('flash-container');
        if (!container) return;
        setTimeout(function() { container.remove(); }, 4000);
    })();
    </script>
</body>
</html>
