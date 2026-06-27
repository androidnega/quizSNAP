@extends('layouts.dashboard')

@section('title', 'Live Support')
@section('dashboard_heading', 'Live Support')

@push('styles')
<style>
    .live-support-layout {
        display: grid;
        grid-template-columns: minmax(220px, 280px) 1fr;
        gap: 1rem;
        min-height: calc(100vh - 12rem);
    }
    @media (max-width: 768px) {
        .live-support-layout { grid-template-columns: 1fr; min-height: auto; }
    }
    .live-support-panel {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 28rem;
    }
    .live-support-panel__head {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
    }
    .live-support-queue {
        overflow-y: auto;
        flex: 1;
    }
    .live-support-queue-item {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.75rem 1rem;
        border: none;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        cursor: pointer;
    }
    .live-support-queue-item:hover { background: #f8fafc; }
    .live-support-queue-item.is-active { background: #eef2ff; }
    .live-support-queue-item__status {
        display: inline-block;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.125rem 0.375rem;
        border-radius: 9999px;
        margin-bottom: 0.25rem;
    }
    .live-support-queue-item__status--waiting { background: #fef3c7; color: #92400e; }
    .live-support-queue-item__status--active { background: #dcfce7; color: #166534; }
    .live-support-chat-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.625rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
    }
    .live-support-chat-toolbar button {
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 0.5rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
    }
    .live-support-chat-toolbar button:hover { background: #f8fafc; }
    .live-support-chat-toolbar button.primary {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #fff;
    }
    .live-support-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: #f8fafc;
        min-height: 14rem;
    }
    .live-support-msg { margin-bottom: 0.625rem; max-width: 85%; }
    .live-support-msg--admin { margin-left: auto; }
    .live-support-msg--system { margin-left: auto; margin-right: auto; max-width: 100%; }
    .live-support-msg__bubble {
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.8125rem;
        line-height: 1.45;
    }
    .live-support-msg--student .live-support-msg__bubble {
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #0f172a;
    }
    .live-support-msg--admin .live-support-msg__bubble {
        background: #4f46e5;
        color: #fff;
    }
    .live-support-msg--system .live-support-msg__bubble {
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.75rem;
        text-align: center;
    }
    .live-support-compose {
        display: flex;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-top: 1px solid #e2e8f0;
    }
    .live-support-compose input {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 9999px;
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
    }
    .live-support-compose button {
        border: none;
        background: #4f46e5;
        color: #fff;
        border-radius: 9999px;
        padding: 0.5rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
    }
    #live-support-remote-video {
        width: 100%;
        max-height: 12rem;
        background: #0f172a;
        object-fit: contain;
    }
    #live-support-remote-video.hidden { display: none; }
</style>
@endpush

@section('dashboard_content')
<div class="w-full space-y-3">
    <p class="text-sm text-gray-600">Respond to student chats in real time. Request screen share to see their screen and guide them.</p>

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
            <div class="live-support-chat-toolbar">
                <button type="button" id="live-support-screen-btn" class="primary">Request screen share</button>
                <button type="button" id="live-support-close-btn">Close chat</button>
            </div>
            <video id="live-support-remote-video" class="hidden" autoplay playsinline muted></video>
            <div id="live-support-messages" class="live-support-messages" aria-live="polite"></div>
            <div class="live-support-compose">
                <input type="text" id="live-support-input" placeholder="Type your reply…" maxlength="2000" autocomplete="off">
                <button type="button" id="live-support-send">Send</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts-after-reverb')
<script>window.SUPPORT_ACCESS = true;</script>
<script>window.QuizSnapLiveSupportAdmin = { baseUrl: @json(url('/dashboard/live-support')) };</script>
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
