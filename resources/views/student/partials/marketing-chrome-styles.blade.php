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
    /* Support FAB */
    .qs-support-fab {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 80;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .qs-support-menu {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.625rem;
        opacity: 0;
        visibility: hidden;
        transform: translateY(0.5rem) scale(0.96);
        transform-origin: bottom right;
        transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s;
        pointer-events: none;
    }

    .qs-support-fab.is-open .qs-support-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .qs-support-action {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.625rem 0.875rem 0.625rem 0.625rem;
        border-radius: 9999px;
        background: #fff;
        color: #0f172a;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 10px 30px -12px rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(226, 232, 240, 0.95);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        white-space: nowrap;
    }

    .qs-support-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 34px -12px rgba(15, 23, 42, 0.4);
    }

    .qs-support-action-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 9999px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        color: #fff;
    }

    .qs-support-action-icon svg {
        width: 1.125rem;
        height: 1.125rem;
    }

    .qs-support-action--whatsapp .qs-support-action-icon {
        background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    }

    .qs-support-action--call .qs-support-action-icon {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .qs-support-toggle {
        position: relative;
        width: 3.625rem;
        height: 3.625rem;
        border: none;
        border-radius: 9999px;
        cursor: pointer;
        color: #fff;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        box-shadow: 0 16px 36px -14px rgba(15, 23, 42, 0.65);
        display: grid;
        place-items: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .qs-support-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 40px -14px rgba(15, 23, 42, 0.72);
    }

    .qs-support-toggle:active {
        transform: scale(0.96);
    }

    .qs-support-toggle svg {
        width: 1.375rem;
        height: 1.375rem;
        transition: transform 0.22s ease, opacity 0.22s ease;
    }

    .qs-support-toggle .qs-support-icon-close {
        position: absolute;
        opacity: 0;
        transform: rotate(-90deg) scale(0.8);
    }

    .qs-support-fab.is-open .qs-support-toggle .qs-support-icon-open {
        opacity: 0;
        transform: rotate(90deg) scale(0.8);
    }

    .qs-support-fab.is-open .qs-support-toggle .qs-support-icon-close {
        opacity: 1;
        transform: rotate(0) scale(1);
    }

    .qs-support-backdrop {
        position: fixed;
        inset: 0;
        z-index: 75;
        background: transparent;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s;
    }

    .qs-support-fab-wrap.is-open .qs-support-backdrop {
        opacity: 1;
        visibility: visible;
    }

    @media (min-width: 768px) {
        .qs-support-fab {
            right: max(1.5rem, env(safe-area-inset-right));
            bottom: max(1.5rem, env(safe-area-inset-bottom));
        }

        .qs-support-toggle {
            width: 4rem;
            height: 4rem;
        }
    }
</style>
