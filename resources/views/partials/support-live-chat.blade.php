{{-- Live support chat widget — sleek, stable, theme-aware --}}
@include('partials.support-live-mobile-styles')
<style>
    .qs-typing-dots {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        height: 0.75rem;
    }
    .qs-typing-dots span {
        width: 5px;
        height: 5px;
        border-radius: 9999px;
        background: currentColor;
        opacity: 0.35;
        animation: qs-typing-dot 1.2s ease-in-out infinite;
    }
    .qs-typing-dots span:nth-child(2) { animation-delay: 0.15s; }
    .qs-typing-dots span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes qs-typing-dot {
        0%, 80%, 100% { opacity: 0.3; transform: translateY(0); }
        40% { opacity: 1; transform: translateY(-2px); }
    }

    .qs-live-support-panel {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 115;
        width: min(100vw - 2rem, 22rem);
        height: min(78vh, 32rem);
        display: flex;
        flex-direction: column;
        border-radius: 1rem;
        background: var(--theme-surface, #fff);
        border: 1px solid color-mix(in srgb, var(--theme-border, #e2e8f0) 88%, transparent);
        box-shadow:
            0 0 0 1px rgba(15, 23, 42, 0.03),
            0 20px 50px -12px rgba(15, 23, 42, 0.28);
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.18s ease, visibility 0.18s ease;
        color: var(--theme-text, #0f172a);
        font-family: var(--font-sans, system-ui, sans-serif);
    }
    .qs-live-support-panel.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .qs-live-support-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.625rem;
        padding: 0.875rem 1rem;
        background: var(--theme-brand, var(--theme-primary-600, #2563eb));
        color: #fff;
        flex-shrink: 0;
    }
    .qs-live-support-header__brand {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
    }
    .qs-live-support-header__avatar {
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.2);
        display: grid;
        place-items: center;
        flex-shrink: 0;
    }
    .qs-live-support-header__avatar svg { width: 1.125rem; height: 1.125rem; }
    .qs-support-avatar--emoji { font-size: 1.125rem; line-height: 1; }
    .qs-support-avatar--vector { display: grid; place-items: center; width: 100%; height: 100%; }
    .qs-live-support-header h3 {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 700;
        letter-spacing: -0.01em;
    }
    .qs-live-support-header p {
        margin: 0.125rem 0 0;
        font-size: 0.6875rem;
        opacity: 0.88;
    }
    .qs-live-support-close {
        border: none;
        background: rgba(255,255,255,0.15);
        color: #fff;
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        cursor: pointer;
        font-size: 1.125rem;
        line-height: 1;
        flex-shrink: 0;
        transition: background 0.15s ease;
    }
    .qs-live-support-close:hover { background: rgba(255,255,255,0.25); }
    .qs-live-support-agent__avatar {
        width: 1.375rem;
        height: 1.375rem;
        border-radius: 9999px;
        background: rgba(255,255,255,0.85);
        display: grid;
        place-items: center;
        flex-shrink: 0;
    }
    .qs-live-support-agent {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4375rem 1rem;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--theme-brand-deep, var(--theme-primary-800, #1e40af));
        background: var(--theme-brand-soft, var(--theme-primary-50, #eff6ff));
        border-bottom: 1px solid var(--theme-primary-100, #dbeafe);
        flex-shrink: 0;
    }
    .qs-live-msg__audio {
        display: block;
        width: min(100%, 14rem);
        height: 2.125rem;
    }
    .qs-live-support-icon-btn.is-recording {
        background: #fee2e2 !important;
        color: #b91c1c !important;
        box-shadow: 0 0 0 2px #fecaca;
    }
    .qs-live-support-typing {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4375rem 1rem;
        font-size: 0.6875rem;
        font-weight: 500;
        color: var(--theme-muted, #64748b);
        background: var(--theme-bg, #f8fafc);
        border-bottom: 1px solid var(--theme-border, #e2e8f0);
        flex-shrink: 0;
    }
    .qs-live-support-intake {
        flex: 1;
        overflow-y: auto;
        padding: 1.125rem 1rem;
        background: var(--theme-bg, #f8fafc);
        min-height: 0;
    }
    .qs-live-support-intake p {
        margin: 0 0 1rem;
        font-size: 0.8125rem;
        color: var(--theme-muted, #64748b);
        line-height: 1.5;
    }
    .qs-live-support-intake label {
        display: block;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.375rem;
        color: var(--theme-muted, #64748b);
    }
    .qs-live-support-intake input {
        width: 100%;
        border: 1px solid var(--theme-border, #cbd5e1);
        border-radius: 0.625rem;
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        margin-bottom: 0.875rem;
        background: var(--theme-surface, #fff);
        color: var(--theme-text, #0f172a);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .qs-live-support-intake input:focus {
        outline: none;
        border-color: var(--theme-brand, var(--theme-primary-600, #2563eb));
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-brand, #2563eb) 18%, transparent);
    }
    .qs-live-support-intake button {
        width: 100%;
        border: none;
        background: var(--theme-brand, var(--theme-primary-600, #2563eb));
        color: #fff;
        border-radius: 0.625rem;
        padding: 0.6875rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 0.25rem;
    }
    .qs-live-support-intake .qs-live-intake-error {
        color: #b91c1c;
        font-size: 0.75rem;
        margin-bottom: 0.625rem;
    }
    .qs-live-support-messages {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.875rem;
        background: var(--theme-bg, #f8fafc);
        min-height: 0;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }
    .qs-live-msg {
        margin-bottom: 0.625rem;
        max-width: 82%;
        animation: qs-live-msg-in 0.16s ease;
    }
    @keyframes qs-live-msg-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .qs-live-msg--student { margin-left: auto; }
    .qs-live-msg__bubble {
        padding: 0.5625rem 0.8125rem;
        border-radius: 1rem;
        font-size: 0.8125rem;
        line-height: 1.5;
        word-break: break-word;
    }
    .qs-live-msg--student .qs-live-msg__bubble {
        background: var(--theme-brand, var(--theme-primary-600, #2563eb));
        color: #fff;
        border-bottom-right-radius: 0.25rem;
    }
    .qs-live-msg--admin .qs-live-msg__bubble {
        background: var(--theme-surface, #fff);
        color: var(--theme-text, #0f172a);
        border: 1px solid var(--theme-border, #e2e8f0);
        border-bottom-left-radius: 0.25rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .qs-live-msg__time {
        display: block;
        margin-top: 0.1875rem;
        font-size: 0.625rem;
        color: var(--theme-muted, #94a3b8);
    }
    .qs-live-msg--student .qs-live-msg__time { text-align: right; }
    .qs-live-msg__image {
        display: block;
        max-width: 100%;
        max-height: 11rem;
        border-radius: 0.5rem;
        object-fit: cover;
    }
    .qs-live-support-compose {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.625rem 0.75rem;
        border-top: 1px solid var(--theme-border, #e2e8f0);
        background: var(--theme-surface, #fff);
        flex-shrink: 0;
    }
    .qs-live-support-compose input[type="text"] {
        flex: 1;
        border: 1px solid var(--theme-border, #cbd5e1);
        border-radius: 0.625rem;
        padding: 0.5625rem 0.75rem;
        font-size: 0.8125rem;
        min-width: 0;
        background: var(--theme-bg, #f8fafc);
        color: var(--theme-text, #0f172a);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .qs-live-support-compose input[type="text"]:focus {
        outline: none;
        border-color: var(--theme-brand, var(--theme-primary-600, #2563eb));
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-brand, #2563eb) 14%, transparent);
        background: var(--theme-surface, #fff);
    }
    .qs-live-support-icon-btn {
        border: none;
        background: var(--theme-bg, #f1f5f9);
        color: var(--theme-muted, #475569);
        border-radius: 0.625rem;
        width: 2.125rem;
        height: 2.125rem;
        flex-shrink: 0;
        cursor: pointer;
        display: grid;
        place-items: center;
    }
    .qs-live-support-icon-btn svg { width: 1rem; height: 1rem; }
    .qs-live-support-compose button[type="button"]#qs-live-support-send {
        border: none;
        background: var(--theme-brand, var(--theme-primary-600, #2563eb));
        color: #fff;
        border-radius: 0.625rem;
        padding: 0.5625rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
    }
    .qs-live-support-status {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.4375rem 1rem;
        font-size: 0.6875rem;
        color: var(--theme-muted, #64748b);
        background: var(--theme-surface, #fff);
        border-top: 1px solid var(--theme-border, #e2e8f0);
        flex-shrink: 0;
    }
    .qs-live-support-status__dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        background: #94a3b8;
        flex-shrink: 0;
    }
    .qs-live-support-status__dot.is-online { background: #22c55e; }
    .qs-live-support-status__dot.is-waiting { background: #f59e0b; }
    .qs-live-support-share {
        display: none;
        padding: 0.5rem 0.75rem;
        background: #fffbeb;
        border-top: 1px solid #fde68a;
        flex-shrink: 0;
    }
    .qs-live-support-share.is-visible { display: block; }
    .qs-live-support-share button {
        width: 100%;
        border: none;
        background: #f59e0b;
        color: #fff;
        border-radius: 0.5rem;
        padding: 0.5rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
    }
    .qs-support-live-toggle {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 80;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: 9999px;
        width: 3.5rem;
        height: 3.5rem;
        padding: 0;
        background: var(--theme-brand, var(--theme-primary-600, #2563eb));
        color: #fff;
        cursor: pointer;
        box-shadow: 0 6px 18px -6px color-mix(in srgb, var(--theme-brand, #2563eb) 50%, transparent);
        transition: box-shadow 0.18s ease;
    }
    .qs-support-live-toggle:hover {
        box-shadow: 0 10px 24px -6px color-mix(in srgb, var(--theme-brand, #2563eb) 55%, transparent);
    }
    .qs-support-live-toggle__icon {
        display: inline-flex;
        animation: qs-chat-icon-pulse 2.4s ease-in-out infinite;
    }
    .qs-support-live-toggle__icon svg { width: 1.375rem; height: 1rem; }
    @keyframes qs-chat-icon-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.72; }
    }
    .qs-support-live-toggle__label {
        position: absolute;
        width: 1px; height: 1px;
        padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0,0,0,0);
        border: 0;
    }
    .qs-support-fab-wrap--above-nav .qs-support-live-toggle {
        bottom: max(5.75rem, calc(4.75rem + env(safe-area-inset-bottom)));
    }
    @media (prefers-reduced-motion: reduce) {
        .qs-support-live-toggle__icon, .qs-typing-dots span, .qs-live-msg { animation: none; }
        .qs-live-support-panel { transition: none; }
    }
</style>
<div id="qs-live-support-panel" class="qs-live-support-panel" aria-hidden="true" role="dialog" aria-labelledby="qs-live-support-title">
    <div class="qs-live-support-header">
        <div class="qs-live-support-header__brand">
            <span class="qs-live-support-header__avatar" id="qs-live-support-header-avatar" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </span>
            <div>
                <h3 id="qs-live-support-title">Support</h3>
                <p id="qs-live-support-subtitle">We typically reply in minutes</p>
            </div>
        </div>
        <button type="button" class="qs-live-support-close" id="qs-live-support-close" aria-label="Close chat">×</button>
    </div>
    <div id="qs-live-support-agent" class="qs-live-support-agent" hidden></div>
    <div id="qs-live-support-typing" class="qs-live-support-typing" hidden>
        <span class="qs-typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
        <span class="qs-typing-label"></span>
    </div>
    <div id="qs-live-support-intake" class="qs-live-support-intake" hidden>
        <p id="qs-live-support-intake-lead">Enter your index number and phone to start.</p>
        <div id="qs-live-intake-error" class="qs-live-intake-error" hidden></div>
        <label for="qs-live-intake-index">Index number</label>
        <input type="text" id="qs-live-intake-index" maxlength="64" autocomplete="off" placeholder="BC/ITN/25/123" required>
        <label for="qs-live-intake-phone">Phone number</label>
        <input type="tel" id="qs-live-intake-phone" maxlength="32" autocomplete="tel" placeholder="e.g. 0241234567" required>
        <button type="button" id="qs-live-intake-start">Start chat</button>
    </div>
    <div id="qs-live-support-messages" class="qs-live-support-messages" aria-live="polite"></div>
    <div id="qs-live-support-share" class="qs-live-support-share">
        <button type="button" id="qs-live-support-share-btn">Share my screen with support</button>
    </div>
    <div class="qs-live-emoji-bar" id="qs-live-support-emoji-bar"></div>
    <div class="qs-live-support-compose" id="qs-live-support-compose">
        <input type="file" id="qs-live-support-image-input" accept="image/*" hidden>
        <button type="button" class="qs-live-support-icon-btn" id="qs-live-support-image-btn" aria-label="Send image">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </button>
        <button type="button" class="qs-live-support-icon-btn" id="qs-live-support-audio-btn" aria-label="Record voice message">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14a3 3 0 003-3V7a3 3 0 10-6 0v4a3 3 0 003 3zm6 0a6 6 0 01-11 3M12 19v2"/></svg>
        </button>
        <textarea id="qs-live-support-input" rows="1" placeholder="Write a message…" maxlength="2000" autocomplete="off"></textarea>
        <button type="button" id="qs-live-support-send">Send</button>
    </div>
    <div id="qs-live-support-status" class="qs-live-support-status">
        <span class="qs-live-support-status__dot" id="qs-live-support-status-dot"></span>
        <span id="qs-live-support-status-text">Connecting…</span>
    </div>
</div>
