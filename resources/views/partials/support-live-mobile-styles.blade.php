<style>
    @media (max-width: 640px) {
        .qs-live-support-panel.is-open {
            inset: 0;
            right: auto;
            bottom: auto;
            width: 100%;
            max-width: none;
            height: 100%;
            height: 100dvh;
            max-height: none;
            border-radius: 0;
            border-left: none;
            border-right: none;
        }
        .qs-live-support-header {
            padding-top: max(0.875rem, env(safe-area-inset-top));
        }
        .qs-live-support-compose {
            padding-bottom: max(0.625rem, env(safe-area-inset-bottom));
            gap: 0.3125rem;
        }
        .qs-live-support-compose textarea {
            font-size: 1rem;
        }
        .qs-live-support-icon-btn,
        .qs-live-support-compose button[type="button"]#qs-live-support-send {
            min-width: 2.75rem;
            min-height: 2.75rem;
        }
        .qs-live-msg { max-width: 88%; }
        body.qs-live-chat-open .qs-support-live-toggle,
        body.qs-live-chat-open #sd-nav-fab-wrap {
            visibility: hidden;
            pointer-events: none;
        }
        .live-support-layout--mobile-chat .live-support-panel--chat {
            min-height: calc(100dvh - 8rem);
        }
        .live-support-layout--mobile-chat .live-support-messages {
            min-height: 50dvh;
        }
        .staff-support-fab-wrap.is-open .staff-support-fab-panel {
            position: fixed;
            inset: 0;
            width: 100%;
            max-width: none;
            max-height: none;
            height: 100dvh;
            border-radius: 0;
            bottom: auto;
            right: auto;
        }
        .staff-support-fab-wrap.is-open .staff-support-fab-toggle {
            display: none;
        }
        #staff-fab-live-support-messages {
            max-height: none;
            min-height: 12rem;
        }
    }
    .qs-live-support-compose textarea {
        flex: 1;
        border: 1px solid var(--theme-border, #cbd5e1);
        border-radius: 1.125rem;
        padding: 0.5625rem 0.875rem;
        font-size: 0.8125rem;
        min-width: 0;
        max-height: 6rem;
        resize: none;
        line-height: 1.45;
        background: var(--theme-bg, #f8fafc);
        color: var(--theme-text, #0f172a);
        font-family: inherit;
    }
    .qs-live-support-compose textarea:focus {
        outline: none;
        border-color: var(--theme-brand, #2563eb);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-brand, #2563eb) 14%, transparent);
        background: var(--theme-surface, #fff);
    }
    .qs-live-emoji-bar,
    .live-support-emoji-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.375rem 0.75rem 0;
    }
    .qs-live-emoji-bar button,
    .live-support-emoji-bar button {
        border: none;
        background: transparent;
        font-size: 1.125rem;
        line-height: 1;
        padding: 0.25rem;
        cursor: pointer;
        border-radius: 0.375rem;
    }
    .qs-live-emoji-bar button:hover,
    .live-support-emoji-bar button:hover {
        background: rgba(15, 23, 42, 0.06);
    }
    .live-support-compose textarea {
        flex: 1;
        border: 1px solid var(--theme-border, #cbd5e1);
        border-radius: 1.125rem;
        padding: 0.5625rem 0.875rem;
        font-size: 0.8125rem;
        min-width: 0;
        max-height: 6rem;
        resize: none;
        line-height: 1.45;
        background: var(--theme-bg, #f8fafc);
        font-family: inherit;
    }
    .live-support-compose textarea:focus {
        outline: none;
        border-color: var(--theme-brand, #4f46e5);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-brand, #4f46e5) 14%, transparent);
        background: #fff;
    }
    .live-support-msg__time {
        display: block;
        margin-top: 0.1875rem;
        font-size: 0.625rem;
        color: #94a3b8;
    }
    .live-support-msg--admin .live-support-msg__time { text-align: right; }
    .live-support-typing[hidden],
    .qs-live-support-typing[hidden] {
        display: none !important;
    }
    .qs-live-recording-wave {
        display: none;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.5rem;
        background: #fef2f2;
        border-top: 1px solid #fecaca;
    }
    .qs-live-recording-wave.is-active { display: flex; }
    .qs-live-recording-wave__label {
        font-size: 0.625rem;
        font-weight: 600;
        color: #b91c1c;
        white-space: nowrap;
    }
    .qs-live-recording-wave__bars {
        flex: 1;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 2px;
        height: 1.5rem;
        min-width: 0;
    }
    .qs-live-recording-wave__bar {
        flex: 1;
        max-width: 0.3125rem;
        min-height: 0.125rem;
        border-radius: 9999px;
        background: linear-gradient(180deg, #f87171 0%, #dc2626 100%);
        transition: height 0.06s linear;
    }
</style>
