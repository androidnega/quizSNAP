    /* Support FAB — shared across marketing pages and student dashboard (desktop) */
    .qs-support-fab {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 80;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.625rem;
    }

    .qs-support-fab-wrap.qs-support-fab-wrap--above-nav .qs-support-fab {
        bottom: max(5.75rem, calc(4.75rem + env(safe-area-inset-bottom)));
    }

    .qs-support-menu {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
        pointer-events: none;
    }

    .qs-support-fab-wrap.is-open .qs-support-menu {
        pointer-events: auto;
    }

    .qs-support-action {
        --fab-i: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.5rem 0.875rem 0.5rem 0.5rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.98);
        color: #0f172a;
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 10px 28px -12px rgba(15, 23, 42, 0.28);
        border: 1px solid rgba(226, 232, 240, 0.92);
        white-space: nowrap;
        touch-action: manipulation;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translate3d(0, 1.125rem, 0) scale(0.88);
        transform-origin: right center;
        filter: blur(3px);
        transition:
            opacity 0.34s cubic-bezier(0.22, 1, 0.36, 1),
            transform 0.4s cubic-bezier(0.22, 1.14, 0.36, 1),
            visibility 0.34s,
            filter 0.3s ease,
            box-shadow 0.2s ease;
        transition-delay: calc((var(--fab-max, 1) - var(--fab-i)) * 28ms);
    }

    .qs-support-fab-wrap.is-open .qs-support-action {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translate3d(0, 0, 0) scale(1);
        filter: blur(0);
        transition-delay: calc(var(--fab-i) * 55ms);
    }

    .qs-support-action:hover {
        transform: translate3d(0, -2px, 0) scale(1);
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.05),
            0 14px 34px -12px rgba(15, 23, 42, 0.32);
    }

    .qs-support-action-icon {
        width: 2.375rem;
        height: 2.375rem;
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
        background: linear-gradient(145deg, #2ee06a 0%, #128c7e 100%);
    }

    .qs-support-action--call .qs-support-action-icon {
        background: linear-gradient(145deg, #3b82f6 0%, #1d4ed8 100%);
    }

    .qs-support-toggle {
        position: relative;
        width: 3.625rem;
        height: 3.625rem;
        border: none;
        border-radius: 9999px;
        cursor: pointer;
        color: #fff;
        background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.12),
            0 16px 36px -14px rgba(15, 23, 42, 0.62);
        display: grid;
        place-items: center;
        touch-action: manipulation;
        transition:
            transform 0.38s cubic-bezier(0.22, 1.14, 0.36, 1),
            box-shadow 0.28s ease,
            background 0.28s ease;
    }

    .qs-support-fab-wrap:not(.is-open) .qs-support-toggle {
        animation: qs-fab-glow 3s ease-in-out infinite;
    }

    @keyframes qs-fab-glow {
        0%, 100% {
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.1),
                0 14px 32px -14px rgba(15, 23, 42, 0.55);
        }
        50% {
            box-shadow:
                0 2px 4px rgba(15, 23, 42, 0.12),
                0 18px 40px -12px rgba(15, 23, 42, 0.68);
        }
    }

    .qs-support-toggle:hover {
        transform: translateY(-2px) scale(1.02);
    }

    .qs-support-toggle:active {
        transform: scale(0.94);
    }

    .qs-support-fab-wrap.is-open .qs-support-toggle {
        animation: none;
        transform: rotate(0deg);
    }

    .qs-support-toggle svg {
        width: 1.375rem;
        height: 1.375rem;
        transition: opacity 0.26s ease, transform 0.32s cubic-bezier(0.22, 1.14, 0.36, 1);
    }

    .qs-support-toggle .qs-support-icon-close {
        position: absolute;
        opacity: 0;
        transform: rotate(-72deg) scale(0.65);
    }

    .qs-support-toggle .qs-support-icon-open {
        transform: rotate(0deg) scale(1);
    }

    .qs-support-fab-wrap.is-open .qs-support-toggle .qs-support-icon-open {
        opacity: 0;
        transform: rotate(72deg) scale(0.65);
    }

    .qs-support-fab-wrap.is-open .qs-support-toggle .qs-support-icon-close {
        opacity: 1;
        transform: rotate(0deg) scale(1);
    }

    .qs-support-backdrop {
        position: fixed;
        inset: 0;
        z-index: 75;
        background: rgba(15, 23, 42, 0.22);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.32s ease, visibility 0.32s ease;
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

    @media (prefers-reduced-motion: reduce) {
        .qs-support-action,
        .qs-support-toggle,
        .qs-support-toggle svg,
        .qs-support-backdrop {
            animation: none !important;
            transition-duration: 0.01ms !important;
        }
    }
