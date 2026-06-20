<style>
    body.qs-marketing { background: #fafafa !important; }

    :root {
        --qs-brand: #2563eb;
        --qs-brand-dark: #1d4ed8;
        --qs-accent: #7c3aed;
        --qs-accent-soft: #ede9fe;
        --qs-snap: #fbbf24;
        --qs-text: #0f172a;
        --qs-muted: #64748b;
        --qs-border: #e2e8f0;
        --qs-surface: #ffffff;
        --qs-bg: #fafafa;
    }

    body.landing-page,
    body.qs-landing {
        background: var(--qs-bg) !important;
        overflow-x: hidden;
    }

    .qs-landing-shell {
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        color: var(--qs-text);
    }

    .qs-container {
        width: 100%;
        max-width: 72rem;
        margin: 0 auto;
        padding-left: max(1rem, env(safe-area-inset-left));
        padding-right: max(1rem, env(safe-area-inset-right));
    }

    /* Header — uses theme-header when marketing chrome is active */
    .qs-header:not(.theme-header) {
        flex-shrink: 0;
        background: var(--qs-bg);
        border-bottom: none;
    }

    .qs-header.theme-header {
        flex-shrink: 0;
    }

    .qs-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 4.5rem;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    @media (min-width: 768px) {
        .qs-header-inner {
            min-height: 5rem;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
    }

    .qs-logo {
        min-width: 0;
    }

    .qs-header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-shrink: 0;
    }

    .qs-btn-get-started {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.625rem 1.25rem;
        border-radius: 0.625rem;
        background: #0f172a;
        color: #fff !important;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: background 0.15s, transform 0.15s;
        white-space: nowrap;
    }

    .qs-btn-get-started:hover {
        background: #1e293b;
    }

    .qs-btn-get-started:active {
        transform: scale(0.98);
    }

    @media (min-width: 640px) {
        .qs-btn-get-started {
            padding: 0.75rem 1.5rem;
            font-size: 0.75rem;
        }
    }

    @include('partials.support-fab-styles')
</style>
