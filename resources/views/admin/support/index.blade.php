@extends('layouts.dashboard')

@section('title', 'Live Support')
@section('dashboard_heading', 'Live Support')

@push('styles')
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
    .live-support-layout {
        display: grid;
        grid-template-columns: minmax(240px, 300px) 1fr;
        gap: 1rem;
        min-height: calc(100vh - 12rem);
    }
    @media (max-width: 768px) {
        .live-support-layout { grid-template-columns: 1fr; min-height: auto; }
    }
    .live-support-panel {
        border: 1px solid var(--theme-border, #e2e8f0);
        border-radius: 1rem;
        background: var(--theme-surface, #fff);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 28rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .live-support-panel__head {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid var(--theme-border, #e2e8f0);
        background: var(--theme-bg, #f8fafc);
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--theme-text, #334155);
        letter-spacing: -0.01em;
    }
    .live-support-queue { overflow-y: auto; flex: 1; }
    .live-support-queue-item {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.875rem 1rem;
        border: none;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .live-support-queue-item:hover { background: #f8fafc; }
    .live-support-queue-item.is-active {
        background: color-mix(in srgb, var(--theme-brand, #4f46e5) 8%, #fff);
        box-shadow: inset 3px 0 0 var(--theme-brand, #4f46e5);
    }
    .live-support-queue-item__status {
        display: inline-block;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.125rem 0.4375rem;
        border-radius: 9999px;
        margin-bottom: 0.3125rem;
    }
    .live-support-queue-item__status--waiting { background: #fef3c7; color: #92400e; }
    .live-support-queue-item__status--active { background: #dcfce7; color: #166534; }
    .live-support-typing {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4375rem 1rem;
        font-size: 0.6875rem;
        font-weight: 500;
        color: var(--theme-muted, #64748b);
        background: var(--theme-bg, #f8fafc);
        border-bottom: 1px solid var(--theme-border, #e2e8f0);
    }
    .live-support-chat-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4375rem;
        padding: 0.625rem 1rem;
        border-bottom: 1px solid var(--theme-border, #e2e8f0);
        background: var(--theme-surface, #fff);
    }
    .live-support-chat-toolbar button {
        border: 1px solid var(--theme-border, #cbd5e1);
        background: var(--theme-surface, #fff);
        border-radius: 0.5rem;
        padding: 0.4375rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .live-support-chat-toolbar button:hover { background: var(--theme-bg, #f8fafc); }
    .live-support-chat-toolbar button.primary {
        background: var(--theme-brand, #4f46e5);
        border-color: var(--theme-brand, #4f46e5);
        color: #fff;
    }
    .live-support-chat-toolbar button.danger {
        color: #b91c1c;
        border-color: #fecaca;
    }
    .live-support-refer-wrap {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        margin-left: auto;
    }
    .live-support-refer-wrap[hidden] { display: none; }
    .live-support-refer-wrap select {
        border: 1px solid var(--theme-border, #cbd5e1);
        border-radius: 0.5rem;
        padding: 0.4375rem 0.625rem;
        font-size: 0.75rem;
        max-width: 11rem;
        background: var(--theme-surface, #fff);
        color: var(--theme-text, #0f172a);
    }
    .live-support-refer-wrap button {
        white-space: nowrap;
    }
    .live-support-identity {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem 1rem;
        padding: 0.875rem 1rem;
        border: 1px solid var(--theme-border, #e2e8f0);
        border-radius: 0.875rem;
        background: var(--theme-surface, #fff);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .live-support-identity__lead {
        min-width: 10rem;
    }
    .live-support-identity__lead strong {
        display: block;
        font-size: 0.8125rem;
        color: var(--theme-text, #0f172a);
    }
    .live-support-identity__lead p {
        margin: 0.125rem 0 0;
        font-size: 0.6875rem;
        color: var(--theme-muted, #64748b);
    }
    .live-support-identity__controls {
        display: flex;
        align-items: center;
        gap: 0.4375rem;
        flex: 1;
        min-width: min(100%, 18rem);
    }
    .live-support-identity__controls input {
        flex: 1;
        border: 1px solid var(--theme-border, #cbd5e1);
        border-radius: 0.625rem;
        padding: 0.5625rem 0.75rem;
        font-size: 0.8125rem;
        min-width: 0;
        background: var(--theme-bg, #f8fafc);
    }
    .live-support-identity__controls input:focus {
        outline: none;
        border-color: var(--theme-brand, #4f46e5);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-brand, #4f46e5) 14%, transparent);
        background: #fff;
    }
    .live-support-identity__controls button {
        border: none;
        background: var(--theme-brand, #4f46e5);
        color: #fff;
        border-radius: 0.625rem;
        padding: 0.5625rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
    }
    .live-support-identity__hint {
        font-size: 0.6875rem;
        color: var(--theme-muted, #64748b);
        white-space: nowrap;
    }
    .live-support-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: var(--theme-bg, #f8fafc);
        min-height: 14rem;
        scroll-behavior: smooth;
    }
    .live-support-msg {
        margin-bottom: 0.625rem;
        max-width: 82%;
        animation: live-msg-in 0.16s ease;
    }
    @keyframes live-msg-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .live-support-msg--admin { margin-left: auto; }
    .live-support-msg--system { margin-left: auto; margin-right: auto; max-width: 100%; }
    .live-support-msg__bubble {
        padding: 0.5625rem 0.8125rem;
        border-radius: 1rem;
        font-size: 0.8125rem;
        line-height: 1.5;
        word-break: break-word;
    }
    .live-support-msg--student .live-support-msg__bubble {
        background: var(--theme-surface, #fff);
        border: 1px solid var(--theme-border, #e2e8f0);
        color: var(--theme-text, #0f172a);
        border-bottom-left-radius: 0.25rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .live-support-msg--admin .live-support-msg__bubble {
        background: var(--theme-brand, #4f46e5);
        color: #fff;
        border-bottom-right-radius: 0.25rem;
    }
    .live-support-msg--system .live-support-msg__bubble {
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.75rem;
        text-align: center;
    }
    .live-support-msg__image {
        display: block;
        max-width: 100%;
        max-height: 10rem;
        border-radius: 0.5rem;
    }
    .live-support-taken-notice {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #92400e;
        background: #fffbeb;
        border-bottom: 1px solid #fde68a;
    }
    .live-support-compose {
        display: flex;
        align-items: center;
        gap: 0.4375rem;
        padding: 0.625rem 1rem;
        border-top: 1px solid var(--theme-border, #e2e8f0);
        background: var(--theme-surface, #fff);
    }
    .live-support-compose input[type="text"] {
        flex: 1;
        border: 1px solid var(--theme-border, #cbd5e1);
        border-radius: 0.625rem;
        padding: 0.5625rem 0.75rem;
        font-size: 0.8125rem;
        min-width: 0;
        background: var(--theme-bg, #f8fafc);
    }
    .live-support-compose input[type="text"]:focus {
        outline: none;
        border-color: var(--theme-brand, #4f46e5);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-brand, #4f46e5) 14%, transparent);
        background: #fff;
    }
    .live-support-compose button {
        border: none;
        background: var(--theme-brand, #4f46e5);
        color: #fff;
        border-radius: 0.625rem;
        padding: 0.5625rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
    }
    .live-support-compose .live-support-icon-btn {
        border: 1px solid var(--theme-border, #cbd5e1);
        background: var(--theme-bg, #f8fafc);
        border-radius: 0.625rem;
        width: 2.125rem;
        height: 2.125rem;
        padding: 0;
        cursor: pointer;
        flex-shrink: 0;
    }
    #live-support-remote-video {
        width: 100%;
        max-height: 12rem;
        background: #0f172a;
        object-fit: contain;
    }
    #live-support-remote-video.hidden { display: none; }
    @media (prefers-reduced-motion: reduce) {
        .qs-typing-dots span, .live-support-msg { animation: none; }
    }
</style>
@endpush

@section('dashboard_content')
<div class="w-full space-y-3">
    <p class="text-sm text-gray-600">Respond to student chats in real time. Request screen share to see their screen and guide them.</p>

    <div class="live-support-identity" id="live-support-identity">
        <div class="live-support-identity__lead">
            <strong>Your chat name</strong>
            <p>Students see this when you join, type, or get referred a chat.</p>
        </div>
        <div class="live-support-identity__controls">
            <input type="text" id="live-support-display-name-input" maxlength="64" placeholder="e.g. Sarah from Support" value="{{ old('support_display_name', $supportDisplayName ?? '') }}" autocomplete="off">
            <button type="button" id="live-support-display-name-save">Save</button>
        </div>
        <span class="live-support-identity__hint" id="live-support-display-name-hint">Students see: {{ $resolvedSupportDisplayName ?? 'Support' }}</span>
    </div>

    <div class="live-support-layout">
        <div class="live-support-panel">
            <div class="live-support-panel__head">Open chats</div>
            <div id="live-support-queue" class="live-support-queue">
                @forelse($openSessions as $s)
                <button type="button" class="live-support-queue-item" data-uuid="{{ $s->uuid }}">
                    <span class="live-support-queue-item__status live-support-queue-item__status--{{ $s->status }}">{{ ucfirst($s->status) }}</span>
                    <strong class="block text-sm text-gray-900 truncate">{{ $s->student_index ?: 'Unknown index' }}</strong>
                    <span class="block text-xs text-gray-500 truncate">{{ $s->issue_category ?: 'general' }}</span>
                </button>
                @empty
                <p class="text-sm text-gray-500 p-4">No open chats yet.</p>
                @endforelse
            </div>
        </div>

        <div class="live-support-panel">
            <div class="live-support-panel__head" id="live-support-chat-header">Select a chat</div>
            <div id="live-support-typing" class="live-support-typing" hidden>
                <span class="qs-typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="qs-typing-label"></span>
            </div>
            <div id="live-support-taken-notice" class="live-support-taken-notice" hidden></div>
            <div class="live-support-chat-toolbar">
                <button type="button" id="live-support-screen-btn" class="primary">Request screen share</button>
                <button type="button" id="live-support-close-btn">Close chat</button>
                @if($canDeleteSessions ?? false)
                <button type="button" id="live-support-delete-btn" class="danger">Delete chat</button>
                @endif
                <div class="live-support-refer-wrap" id="live-support-refer-wrap" hidden>
                    <select id="live-support-refer-select" aria-label="Refer to available agent">
                        <option value="">Refer to agent…</option>
                    </select>
                    <button type="button" id="live-support-refer-btn">Refer</button>
                </div>
            </div>
            <video id="live-support-remote-video" class="hidden" autoplay playsinline muted></video>
            <div id="live-support-messages" class="live-support-messages" aria-live="polite"></div>
            <div class="live-support-compose">
                <input type="file" id="live-support-image-input" accept="image/*" hidden>
                <button type="button" id="live-support-image-btn" class="live-support-icon-btn" aria-label="Send image">📷</button>
                <input type="text" id="live-support-input" placeholder="Type your reply…" maxlength="2000" autocomplete="off">
                <button type="button" id="live-support-send">Send</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts-after-reverb')
<script>window.SUPPORT_ACCESS = true;</script>
<script>window.QuizSnapLiveSupportAdmin = { baseUrl: @json(url('/dashboard/live-support')), staffId: @json(auth()->id()), canDeleteSessions: @json($canDeleteSessions ?? false), supportDisplayName: @json($supportDisplayName ?? ''), resolvedSupportDisplayName: @json($resolvedSupportDisplayName ?? '') };</script>
<script src="{{ asset('js/support-live-sounds.js') }}?v={{ filemtime(public_path('js/support-live-sounds.js')) }}"></script>
<script src="{{ asset('js/support-live-admin.js') }}?v={{ filemtime(public_path('js/support-live-admin.js')) }}"></script>
<script>
(function() {
    document.querySelectorAll('.live-support-queue-item[data-uuid]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (window.QuizSnapLiveSupportAdminConsole) {
                window.QuizSnapLiveSupportAdminConsole.openSession(btn.dataset.uuid);
            }
        });
    });
})();
</script>
@endpush
