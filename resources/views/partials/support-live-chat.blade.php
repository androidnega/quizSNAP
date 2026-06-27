{{-- Live support chat widget (student / public pages) --}}
<style>
    .qs-live-support-panel {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 115;
        width: min(100vw - 2rem, 24rem);
        max-height: min(78vh, 36rem);
        display: flex;
        flex-direction: column;
        border-radius: 1.125rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 24px 60px -24px rgba(15, 23, 42, 0.4);
        overflow: hidden;
        transform: translateY(110%) scale(0.96);
        opacity: 0;
        pointer-events: none;
        transition: transform 0.28s ease, opacity 0.28s ease;
    }
    .qs-live-support-panel.is-open {
        transform: translateY(0) scale(1);
        opacity: 1;
        pointer-events: auto;
    }
    .qs-live-support-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.875rem 1rem;
        background: linear-gradient(135deg, #4338ca 0%, #6366f1 55%, #818cf8 100%);
        color: #fff;
    }
    .qs-live-support-header h3 {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 700;
    }
    .qs-live-support-header p {
        margin: 0.125rem 0 0;
        font-size: 0.6875rem;
        opacity: 0.92;
    }
    .qs-live-support-close {
        border: none;
        background: rgba(255,255,255,0.18);
        color: #fff;
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        cursor: pointer;
        font-size: 1.125rem;
        line-height: 1;
    }
    .qs-live-support-agent {
        padding: 0.375rem 0.875rem;
        font-size: 0.6875rem;
        font-weight: 600;
        color: #4338ca;
        background: #eef2ff;
        border-bottom: 1px solid #e0e7ff;
    }
    .qs-live-support-messages {
        flex: 1;
        overflow-y: auto;
        padding: 0.875rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 14rem;
    }
    .qs-live-msg {
        margin-bottom: 0.75rem;
        max-width: 88%;
        animation: qs-live-fade 0.25s ease;
    }
    @keyframes qs-live-fade {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .qs-live-msg--student { margin-left: auto; }
    .qs-live-msg--system { margin-left: auto; margin-right: auto; max-width: 100%; }
    .qs-live-msg__bubble {
        padding: 0.5625rem 0.8125rem;
        border-radius: 1rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        word-break: break-word;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .qs-live-msg--student .qs-live-msg__bubble {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        border-bottom-right-radius: 0.3125rem;
    }
    .qs-live-msg--admin .qs-live-msg__bubble {
        background: #fff;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 0.3125rem;
    }
    .qs-live-msg--system .qs-live-msg__bubble {
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.75rem;
        text-align: center;
        box-shadow: none;
    }
    .qs-live-msg__time {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.625rem;
        color: #94a3b8;
    }
    .qs-live-msg--student .qs-live-msg__time { text-align: right; }
    .qs-live-msg__image {
        display: block;
        max-width: 100%;
        max-height: 12rem;
        border-radius: 0.5rem;
        object-fit: cover;
    }
    .qs-live-support-compose {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.625rem;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .qs-live-support-compose input[type="text"] {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 9999px;
        padding: 0.5625rem 0.875rem;
        font-size: 0.8125rem;
        min-width: 0;
    }
    .qs-live-support-icon-btn {
        border: none;
        background: #f1f5f9;
        color: #475569;
        border-radius: 9999px;
        width: 2.25rem;
        height: 2.25rem;
        flex-shrink: 0;
        cursor: pointer;
        display: grid;
        place-items: center;
    }
    .qs-live-support-icon-btn svg { width: 1rem; height: 1rem; }
    .qs-live-support-compose button[type="button"]#qs-live-support-send {
        border: none;
        background: #4f46e5;
        color: #fff;
        border-radius: 9999px;
        padding: 0.5625rem 0.9375rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
    }
    .qs-live-support-status {
        padding: 0.375rem 0.875rem;
        font-size: 0.6875rem;
        color: #64748b;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    .qs-live-support-share {
        display: none;
        padding: 0.5rem 0.75rem;
        background: #fffbeb;
        border-top: 1px solid #fde68a;
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
        gap: 0.5rem;
        border: none;
        border-radius: 9999px;
        padding: 0.75rem 1.125rem 0.75rem 0.875rem;
        background: linear-gradient(145deg, #6366f1 0%, #4f46e5 100%);
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 14px 32px -12px rgba(79, 70, 229, 0.65);
    }
    .qs-support-live-toggle svg { width: 1.25rem; height: 1.25rem; }
    .qs-support-fab-wrap--above-nav .qs-support-live-toggle {
        bottom: max(5.75rem, calc(4.75rem + env(safe-area-inset-bottom)));
    }
</style>
<div id="qs-live-support-panel" class="qs-live-support-panel" aria-hidden="true" role="dialog" aria-labelledby="qs-live-support-title">
    <div class="qs-live-support-header">
        <div>
            <h3 id="qs-live-support-title">Live support</h3>
            <p id="qs-live-support-subtitle">Chat with our team — replies in minutes</p>
        </div>
        <button type="button" class="qs-live-support-close" id="qs-live-support-close" aria-label="Close chat">×</button>
    </div>
    <div id="qs-live-support-agent" class="qs-live-support-agent" hidden></div>
    <div id="qs-live-support-messages" class="qs-live-support-messages" aria-live="polite"></div>
    <div id="qs-live-support-share" class="qs-live-support-share">
        <button type="button" id="qs-live-support-share-btn">Share my screen with support</button>
    </div>
    <div class="qs-live-support-compose">
        <input type="file" id="qs-live-support-image-input" accept="image/*" hidden>
        <button type="button" class="qs-live-support-icon-btn" id="qs-live-support-image-btn" aria-label="Send image">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </button>
        <input type="text" id="qs-live-support-input" placeholder="Type a message…" maxlength="2000" autocomplete="off">
        <button type="button" id="qs-live-support-send">Send</button>
    </div>
    <div id="qs-live-support-status" class="qs-live-support-status">Connecting…</div>
</div>
