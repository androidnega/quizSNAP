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
</style>
