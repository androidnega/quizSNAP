    /* Support FAB — shared across marketing pages and student dashboard (desktop) */
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

    .qs-support-fab-wrap.qs-support-fab-wrap--above-nav .qs-support-fab {
        bottom: max(5.75rem, calc(4.75rem + env(safe-area-inset-bottom)));
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
        touch-action: manipulation;
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
        touch-action: manipulation;
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
        pointer-events: none;
        transition: opacity 0.2s ease, visibility 0.2s;
    }

    .qs-support-fab-wrap.is-open .qs-support-backdrop {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
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
