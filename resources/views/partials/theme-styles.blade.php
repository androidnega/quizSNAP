@php
    $theme = $theme ?? app(\App\Services\ThemeService::class)->activePreset();
    $primary = $theme['primary'] ?? [];
    $headerText = $theme['header_text'] ?? $theme['text'] ?? '#0f172a';
@endphp
<style id="quizsnap-theme-vars">
    :root {
        --theme-brand: {{ $theme['brand'] }};
        --theme-brand-dark: {{ $theme['brand_dark'] }};
        --theme-brand-border: {{ $theme['brand_border'] }};
        --theme-brand-soft: {{ $theme['brand_soft'] }};
        --theme-brand-deep: {{ $theme['brand_deep'] }};
        --theme-brand-hover: {{ $theme['brand_hover'] }};
        --theme-wordmark-a: {{ $theme['wordmark_a'] }};
        --theme-wordmark-b: {{ $theme['wordmark_b'] }};
        --theme-header-text: {{ $headerText }};
        --theme-bg: {{ $theme['bg'] }};
        --theme-surface: {{ $theme['surface'] }};
        --theme-text: {{ $theme['text'] }};
        --theme-muted: {{ $theme['muted'] }};
        --theme-border: {{ $theme['border'] }};
        --theme-primary-50: {{ $primary[50] ?? '#eff6ff' }};
        --theme-primary-100: {{ $primary[100] ?? '#dbeafe' }};
        --theme-primary-200: {{ $primary[200] ?? '#bfdbfe' }};
        --theme-primary-300: {{ $primary[300] ?? '#93c5fd' }};
        --theme-primary-400: {{ $primary[400] ?? '#60a5fa' }};
        --theme-primary-500: {{ $primary[500] ?? '#3b82f6' }};
        --theme-primary-600: {{ $primary[600] ?? '#2563eb' }};
        --theme-primary-700: {{ $primary[700] ?? '#1d4ed8' }};
        --theme-primary-800: {{ $primary[800] ?? '#1e40af' }};
        --theme-primary-900: {{ $primary[900] ?? '#1e3a8a' }};
        --qs-brand: var(--theme-brand);
        --qs-brand-dark: var(--theme-brand-dark);
        --qs-brand-deep: var(--theme-brand-deep);
        --qs-accent: var(--theme-primary-600);
        --qs-accent-soft: var(--theme-brand-soft);
        --qs-snap: var(--theme-brand);
        --qs-text: var(--theme-text);
        --qs-muted: var(--theme-muted);
        --qs-border: var(--theme-border);
        --qs-surface: var(--theme-surface);
        --qs-bg: var(--theme-bg);
        --font-sans: '{{ $theme['fonts']['sans'] ?? 'Inter' }}', ui-sans-serif, system-ui, sans-serif;
        --font-display: '{{ $theme['fonts']['display'] ?? 'Outfit' }}', 'Inter', ui-sans-serif, system-ui, sans-serif;
    }

    html {
        font-family: var(--font-sans);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .font-display, .qs-logo-text, .qs-hero-title {
        font-family: var(--font-display);
    }

    /* QuizSnap brand logo — white, clean, readable on any surface */
    .quizsnap-brand-logo {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
        text-decoration: none;
        color: inherit;
        flex-shrink: 0;
    }

    a.quizsnap-brand-logo:hover {
        opacity: 0.92;
    }

    .quizsnap-brand-logo__wordmark {
        flex-shrink: 0;
    }

    .quizsnap-brand-mark {
        flex-shrink: 0;
        display: grid;
        place-items: center;
        border-radius: 0.5rem;
        color: rgba(255, 255, 255, 0.28);
    }

    .quizsnap-brand-mark--sm { width: 2rem; height: 2rem; border-radius: 0.4375rem; }
    .quizsnap-brand-mark--md { width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; }
    .quizsnap-brand-mark--lg { width: 2.625rem; height: 2.625rem; border-radius: 0.5625rem; }

    .quizsnap-brand-mark svg {
        width: 100%;
        height: 100%;
        display: block;
    }

    .quizsnap-brand-mark__bg {
        fill: currentColor;
        stroke: rgba(255, 255, 255, 0.42);
        stroke-width: 1;
    }

    .quizsnap-brand-mark__letter {
        fill: #ffffff;
    }

    .quizsnap-wordmark {
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1;
        white-space: nowrap;
    }

    .quizsnap-wordmark--sm { font-size: 1.125rem; }
    .quizsnap-wordmark--md { font-size: 1.25rem; }
    .quizsnap-wordmark--lg { font-size: 1.5rem; }

    @media (min-width: 1024px) {
        .quizsnap-wordmark--lg { font-size: 1.625rem; }
    }

    .quizsnap-wordmark--default .quizsnap-wordmark-a,
    .quizsnap-wordmark--default .quizsnap-wordmark-b,
    .quizsnap-wordmark--on-brand .quizsnap-wordmark-a,
    .quizsnap-wordmark--on-brand .quizsnap-wordmark-b {
        color: #ffffff;
    }

    /* Light surfaces — brand pill keeps the white logo visible */
    .quizsnap-brand-logo--surface {
        background: linear-gradient(145deg, var(--theme-brand-dark), var(--theme-brand-deep));
        padding: 0.4375rem 0.8125rem 0.4375rem 0.4375rem;
        border-radius: 0.8125rem;
        box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12);
    }

    .quizsnap-brand-logo--surface .quizsnap-brand-mark {
        color: rgba(255, 255, 255, 0.22);
    }

    .quizsnap-brand-logo--surface .quizsnap-brand-mark__bg {
        stroke: rgba(255, 255, 255, 0.38);
    }

    /* Colored headers — flat white logo on brand bar */
    .theme-header .quizsnap-brand-logo {
        background: transparent;
        box-shadow: none;
        padding: 0;
    }

    .theme-header .quizsnap-brand-mark {
        color: rgba(255, 255, 255, 0.2);
    }

    .theme-header .quizsnap-brand-mark__bg {
        stroke: rgba(255, 255, 255, 0.45);
    }

    .quizsnap-brand-logo .theme-wordmark-a,
    .quizsnap-brand-logo .theme-wordmark-b {
        color: #ffffff;
    }

    .theme-header {
        background-color: var(--theme-brand);
        border-bottom: 1px solid var(--theme-brand-border);
        color: var(--theme-header-text);
    }

    .theme-header-text { color: var(--theme-header-text); }
    .theme-header-hover:hover { background-color: var(--theme-brand-hover); }
    .theme-wordmark-a { color: var(--theme-wordmark-a); }
    .theme-wordmark-b { color: var(--theme-wordmark-b); }
    .theme-brand-bg { background-color: var(--theme-brand); }
    .theme-brand-bg-dark:hover { background-color: var(--theme-brand-dark); }
    .theme-bg { background-color: var(--theme-bg); }
    .theme-pill-active {
        background-color: var(--theme-brand);
        border-color: var(--theme-brand);
        color: var(--theme-header-text);
    }
    .theme-nav-active { background-color: var(--theme-surface); color: var(--theme-text); }
    .theme-nav-idle:hover { background-color: var(--theme-brand-hover); }

    .btn-primary,
    .qs-btn-primary {
        background-color: var(--theme-primary-600) !important;
        border-color: var(--theme-primary-600) !important;
    }
    .btn-primary:hover,
    .qs-btn-primary:hover {
        background-color: var(--theme-primary-700) !important;
        border-color: var(--theme-primary-700) !important;
    }
    .btn:focus-visible {
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-primary-500) 35%, transparent);
    }

    /* Student dashboard segment navigation */
    .sd-segment-nav__track {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.125rem;
        padding: 0.25rem;
        background: color-mix(in srgb, var(--theme-surface) 88%, var(--theme-bg));
        border: 1px solid color-mix(in srgb, var(--theme-border) 85%, transparent);
        border-radius: 9999px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .sd-segment-nav__track--compact {
        gap: 0.0625rem;
        padding: 0.1875rem;
    }

    .sd-segment-nav__item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        padding: 0.4375rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        color: var(--theme-muted);
        text-decoration: none;
        white-space: nowrap;
        transition: color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
    }

    .sd-segment-nav__track:not(.sd-segment-nav__track--compact) .sd-segment-nav__item {
        padding: 0.5rem 0.9375rem;
        font-size: 0.8125rem;
    }

    @media (min-width: 1280px) {
        .sd-segment-nav__track:not(.sd-segment-nav__track--compact) .sd-segment-nav__item {
            padding: 0.5625rem 1.0625rem;
            font-size: 0.875rem;
        }
    }

    .sd-segment-nav__item i {
        font-size: 0.6875em;
        opacity: 0.85;
        transition: opacity 0.18s ease;
    }

    .sd-segment-nav__item:hover {
        color: var(--theme-text);
        background: color-mix(in srgb, var(--theme-surface) 92%, var(--theme-brand-soft));
    }

    .sd-segment-nav__item.is-active {
        color: var(--theme-surface);
        background: var(--theme-text);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.14);
    }

    .sd-segment-nav__item.is-active i {
        opacity: 1;
    }

    .sd-sidebar-nav__item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0 0.375rem;
        padding: 0.625rem 0.875rem;
        border-radius: 0.625rem;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: -0.01em;
        color: var(--theme-muted);
        text-decoration: none;
        transition: color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
    }

    .sd-sidebar-nav__item i {
        width: 1.125rem;
        text-align: center;
        font-size: 0.8125rem;
        color: color-mix(in srgb, var(--theme-muted) 90%, var(--theme-text));
        transition: color 0.18s ease;
    }

    .sd-sidebar-nav__item:hover {
        color: var(--theme-text);
        background: color-mix(in srgb, var(--theme-surface) 70%, transparent);
    }

    .sd-sidebar-nav__item.is-active {
        color: var(--theme-text);
        background: var(--theme-surface);
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    .sd-sidebar-nav__item.is-active i {
        color: var(--theme-primary-600);
    }
</style>
