@php
    $staffSupportFabId = 'staff-fab-';
@endphp
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
        0%, 80%, 100% { opacity: 0.3; }
        40% { opacity: 1; }
    }
    .staff-support-fab-wrap {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 90;
    }
    .staff-support-fab-toggle {
        position: relative;
        width: 3.75rem;
        height: 3.75rem;
        border: none;
        border-radius: 9999px;
        cursor: pointer;
        color: #fff;
        background: linear-gradient(145deg, #6366f1 0%, #4f46e5 100%);
        box-shadow: 0 16px 36px -14px rgba(79, 70, 229, 0.75);
        display: grid;
        place-items: center;
    }
    .staff-support-fab-toggle svg { width: 1.375rem; height: 1.375rem; }
    .staff-support-fab-badge {
        position: absolute;
        top: -0.125rem;
        right: -0.125rem;
        min-width: 1.25rem;
        height: 1.25rem;
        padding: 0 0.3125rem;
        border-radius: 9999px;
        background: #ef4444;
        color: #fff;
        font-size: 0.625rem;
        font-weight: 700;
        display: grid;
        place-items: center;
        border: 2px solid #fff;
    }
    .staff-support-fab-badge[hidden] { display: none; }
    .staff-support-fab-panel {
        position: absolute;
        right: 0;
        bottom: calc(100% + 0.75rem);
        width: min(100vw - 2rem, 22rem);
        max-height: min(70vh, 28rem);
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 24px 60px -24px rgba(15, 23, 42, 0.35);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: none;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.18s ease, visibility 0.18s ease;
    }
    .staff-support-fab-wrap.is-open .staff-support-fab-panel {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .staff-support-fab-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0.875rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #334155;
    }
    .staff-support-fab-panel__head a {
        font-size: 0.6875rem;
        font-weight: 600;
        color: #4f46e5;
        text-decoration: none;
    }
    #staff-fab-live-support-queue {
        overflow-y: auto;
        max-height: 10rem;
        flex-shrink: 0;
    }
    #staff-fab-live-support-messages {
        flex: 1;
        overflow-y: auto;
        padding: 0.625rem;
        background: #f8fafc;
        min-height: 8rem;
        max-height: 12rem;
    }
    #staff-fab-live-support-chat-header {
        padding: 0.5rem 0.875rem;
        font-size: 0.6875rem;
        color: #64748b;
        border-bottom: 1px solid #f1f5f9;
    }
    .staff-support-fab-compose {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 0.375rem;
        padding: 0.5rem;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .staff-support-fab-compose textarea {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 1.125rem;
        padding: 0.5625rem 0.875rem;
        font-size: 0.8125rem;
        min-width: 0;
        max-height: 6rem;
        resize: none;
        line-height: 1.45;
        background: #f8fafc;
        font-family: inherit;
    }
    .staff-support-fab-compose textarea:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.14);
        background: #fff;
    }
    .staff-support-fab-compose .live-support-icon-btn {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border: 1px solid #e2e8f0;
        border-radius: 9999px;
        background: #fff;
        font-size: 1rem;
        line-height: 1;
        cursor: pointer;
        display: grid;
        place-items: center;
    }
    .staff-support-fab-compose .live-support-icon-btn.is-recording {
        background: #fef2f2;
        border-color: #fca5a5;
        animation: qs-fab-rec-pulse 1s ease-in-out infinite;
    }
    @keyframes qs-fab-rec-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.35); }
        50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12); }
    }
    #staff-fab-live-support-emoji-bar {
        padding: 0.375rem 0.5rem 0;
        border-top: 1px solid #f1f5f9;
        background: #fff;
    }
    #staff-fab-live-support-queue .live-support-queue-item {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.625rem 0.875rem;
        border: none;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        cursor: pointer;
        font: inherit;
    }
    #staff-fab-live-support-queue .live-support-queue-item.is-active { background: #eef2ff; }
    #staff-fab-live-support-queue .live-support-queue-item__status {
        display: inline-block;
        font-size: 0.5625rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.125rem 0.375rem;
        border-radius: 9999px;
        margin-bottom: 0.125rem;
    }
    #staff-fab-live-support-queue .live-support-queue-item__status--waiting { background: #fef3c7; color: #92400e; }
    #staff-fab-live-support-queue .live-support-queue-item__status--active { background: #dcfce7; color: #166534; }
    .live-support-msg { margin-bottom: 0.5rem; max-width: 92%; font-size: 0.75rem; }
    .live-support-msg--admin { margin-left: auto; }
    .live-support-msg--student .live-support-msg__bubble { background: #fff; border: 1px solid #e2e8f0; padding: 0.375rem 0.625rem; border-radius: 0.625rem; }
    .live-support-msg--admin .live-support-msg__bubble { background: #4f46e5; color: #fff; padding: 0.375rem 0.625rem; border-radius: 0.625rem; }
    .live-support-msg--system .live-support-msg__bubble { background: #eef2ff; color: #3730a3; padding: 0.375rem 0.625rem; border-radius: 0.625rem; text-align: center; font-size: 0.6875rem; }
    .live-support-msg__image { max-width: 100%; max-height: 6rem; border-radius: 0.375rem; display: block; }
    .staff-support-fab-compose button {
        border: none;
        background: #4f46e5;
        color: #fff;
        border-radius: 9999px;
        padding: 0.4375rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
    }
    .live-support-taken-notice {
        padding: 0.375rem 0.875rem;
        font-size: 0.6875rem;
        font-weight: 600;
        color: #92400e;
        background: #fffbeb;
        border-bottom: 1px solid #fde68a;
    }
</style>
<div class="staff-support-fab-wrap" id="staff-support-fab-wrap">
    <div class="staff-support-fab-panel" id="staff-support-fab-panel" aria-hidden="true">
        <div class="staff-support-fab-panel__head">
            <span>Live support</span>
            <a href="{{ route('dashboard.support.index') }}">Open full console</a>
        </div>
        <div id="staff-fab-live-support-queue" class="live-support-queue"></div>
        <div id="staff-fab-live-support-chat-header">Select a chat</div>
        <div id="staff-fab-live-support-typing" class="live-support-typing" hidden style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;font-size:0.6875rem;color:#64748b;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <span class="qs-typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
            <span class="qs-typing-label"></span>
        </div>
        <div id="staff-fab-live-support-taken-notice" class="live-support-taken-notice" hidden></div>
        <div id="staff-fab-live-support-messages" aria-live="polite"></div>
        <div class="live-support-emoji-bar" id="staff-fab-live-support-emoji-bar"></div>
        <div class="staff-support-fab-compose live-support-compose">
            <input type="file" id="staff-fab-live-support-image-input" accept="image/*" hidden>
            <button type="button" id="staff-fab-live-support-image-btn" class="live-support-icon-btn" aria-label="Send image">📷</button>
            <button type="button" id="staff-fab-live-support-audio-btn" class="live-support-icon-btn" aria-label="Record voice message">🎤</button>
            <textarea id="staff-fab-live-support-input" rows="1" placeholder="Reply…" maxlength="2000" autocomplete="off"></textarea>
            <button type="button" id="staff-fab-live-support-send">Send</button>
        </div>
    </div>
    <button type="button" class="staff-support-fab-toggle" id="staff-support-fab-toggle" aria-label="Open live support inbox" aria-expanded="false">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span class="staff-support-fab-badge" id="staff-support-fab-badge" hidden>0</span>
    </button>
</div>
<script>
(function() {
    var wrap = document.getElementById('staff-support-fab-wrap');
    var toggle = document.getElementById('staff-support-fab-toggle');
    var panel = document.getElementById('staff-support-fab-panel');
    if (!wrap || !toggle) return;
    toggle.addEventListener('click', function() {
        var open = !wrap.classList.contains('is-open');
        wrap.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (panel) panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('qs-live-chat-open', open && window.matchMedia('(max-width: 640px)').matches);
    });
    document.addEventListener('click', function(e) {
        if (wrap.classList.contains('is-open') && !wrap.contains(e.target)) {
            wrap.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            if (panel) panel.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('qs-live-chat-open');
        }
    });
})();
</script>
