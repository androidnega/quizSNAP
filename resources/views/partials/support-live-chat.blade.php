{{-- Live support chat widget (student / public pages) --}}
<style>
    .qs-live-support-panel {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 115;
        width: min(100vw - 2rem, 22rem);
        max-height: min(70vh, 32rem);
        display: flex;
        flex-direction: column;
        border-radius: 1rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 50px -20px rgba(15, 23, 42, 0.35);
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
        padding: 0.75rem 0.875rem;
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: #fff;
    }
    .qs-live-support-header h3 {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 700;
    }
    .qs-live-support-header p {
        margin: 0.125rem 0 0;
        font-size: 0.6875rem;
        opacity: 0.9;
    }
    .qs-live-support-close {
        border: none;
        background: rgba(255,255,255,0.15);
        color: #fff;
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        cursor: pointer;
    }
    .qs-live-support-messages {
        flex: 1;
        overflow-y: auto;
        padding: 0.75rem;
        background: #f8fafc;
        min-height: 12rem;
    }
    .qs-live-msg {
        margin-bottom: 0.625rem;
        max-width: 92%;
        animation: qs-live-fade 0.25s ease;
    }
    @keyframes qs-live-fade {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .qs-live-msg--student { margin-left: auto; }
    .qs-live-msg--system { margin-left: auto; margin-right: auto; max-width: 100%; }
    .qs-live-msg__bubble {
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        word-break: break-word;
    }
    .qs-live-msg--student .qs-live-msg__bubble {
        background: #4f46e5;
        color: #fff;
        border-bottom-right-radius: 0.25rem;
    }
    .qs-live-msg--admin .qs-live-msg__bubble {
        background: #fff;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 0.25rem;
    }
    .qs-live-msg--system .qs-live-msg__bubble {
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.75rem;
        text-align: center;
    }
    .qs-live-support-compose {
        display: flex;
        gap: 0.5rem;
        padding: 0.625rem;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .qs-live-support-compose input {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 9999px;
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
    }
    .qs-live-support-compose button {
        border: none;
        background: #4f46e5;
        color: #fff;
        border-radius: 9999px;
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
    }
    .qs-live-support-status {
        padding: 0.375rem 0.75rem;
        font-size: 0.6875rem;
        color: #64748b;
        background: #f1f5f9;
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
</style>
<div id="qs-live-support-panel" class="qs-live-support-panel" aria-hidden="true" role="dialog" aria-labelledby="qs-live-support-title">
    <div class="qs-live-support-header">
        <div>
            <h3 id="qs-live-support-title">Live support</h3>
            <p id="qs-live-support-subtitle">We typically reply within a few minutes</p>
        </div>
        <button type="button" class="qs-live-support-close" id="qs-live-support-close" aria-label="Close chat">×</button>
    </div>
    <div id="qs-live-support-messages" class="qs-live-support-messages" aria-live="polite"></div>
    <div id="qs-live-support-share" class="qs-live-support-share">
        <button type="button" id="qs-live-support-share-btn">Share my screen with support</button>
    </div>
    <div class="qs-live-support-compose">
        <input type="text" id="qs-live-support-input" placeholder="Type a message…" maxlength="2000" autocomplete="off">
        <button type="button" id="qs-live-support-send">Send</button>
    </div>
    <div id="qs-live-support-status" class="qs-live-support-status">Connecting…</div>
</div>
