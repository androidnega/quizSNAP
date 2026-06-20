@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4 sm:space-y-6">
    <div class="min-w-0">
        <p class="text-sm sm:text-base text-gray-600">Courses, users, class groups (view only), and system settings</p>
    </div>

    {{-- Update mode: very slim height, clean, no animation; countdown mm:ss, no overflow --}}
    <section class="rounded border px-2.5 py-1.5 min-w-0 overflow-hidden {{ ($update_mode ?? false) ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0 flex-1 flex items-center gap-2 flex-wrap">
                <h2 class="text-xs font-semibold {{ ($update_mode ?? false) ? 'text-green-900' : 'text-gray-900' }}">Update mode</h2>
                <span class="text-xs font-medium px-1.5 py-0.5 rounded {{ ($update_mode ?? false) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">{{ ($update_mode ?? false) ? 'ON' : 'OFF' }}</span>
                @if(($update_mode ?? false) && ($update_estimated_end ?? null))
                    <span class="text-xs text-green-900 font-semibold tabular-nums shrink-0 overflow-hidden" style="max-width:100%">Time left: <span id="update-mode-countdown">--:--</span></span>
                @endif
                <span class="text-xs {{ ($update_mode ?? false) ? 'text-green-800' : 'text-gray-600' }}">Only staff at <code class="px-0.5 rounded {{ ($update_mode ?? false) ? 'bg-green-100' : 'bg-gray-200' }}">/login</code></span>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                @if($update_mode ?? false)
                    <form method="post" action="{{ route('dashboard.settings.update-estimated-end') }}" class="flex items-center gap-1.5">
                        @csrf
                        <label class="sr-only">Estimated end</label>
                        <input type="datetime-local" name="estimated_end" value="{{ $update_estimated_end ? \Carbon\Carbon::parse($update_estimated_end)->format('Y-m-d\TH:i') : '' }}" class="text-xs rounded border border-green-300 px-1.5 py-0.5 min-w-0 w-36" />
                        <button type="submit" class="text-xs font-medium text-green-800 py-0.5">Save</button>
                    </form>
                @endif
                <form method="post" action="{{ route('dashboard.settings.update-mode') }}" class="inline">
                    @csrf
                    <button type="submit" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent focus:outline-none focus:ring-1 focus:ring-offset-0 {{ ($update_mode ?? false) ? 'bg-green-500 focus:ring-green-400' : 'bg-gray-300 focus:ring-gray-400' }}" role="switch" aria-checked="{{ ($update_mode ?? false) ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow {{ ($update_mode ?? false) ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Live activity (real-time, not cached) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 min-w-0">
        <div id="live-visitors-card" class="live-stat-card rounded-xl bg-gradient-to-br from-rose-600 to-rose-800 p-4 sm:p-5 shadow-sm min-w-0 relative overflow-hidden">
            <span class="live-stat-pulse absolute top-3 right-3 h-2.5 w-2.5 rounded-full bg-white/90" aria-hidden="true"></span>
            <p class="text-xs sm:text-sm font-medium text-rose-100 truncate flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-300 animate-pulse" aria-hidden="true"></span>
                Live on site
            </p>
            <p id="live-visitors-count" class="mt-1 text-3xl sm:text-4xl font-bold tabular-nums text-white">{{ (int) ($liveVisitors ?? 0) }}</p>
            <p class="mt-1 text-xs text-rose-100/90">Visitors active in the last 90 seconds</p>
        </div>
        <div id="live-quiz-takers-card" class="live-stat-card rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 p-4 sm:p-5 shadow-sm min-w-0 relative overflow-hidden">
            <span class="live-stat-pulse live-stat-pulse--delay absolute top-3 right-3 h-2.5 w-2.5 rounded-full bg-white/90" aria-hidden="true"></span>
            <p class="text-xs sm:text-sm font-medium text-indigo-100 truncate flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-300 animate-pulse" aria-hidden="true"></span>
                Writing quiz now
            </p>
            <p id="live-quiz-takers-count" class="mt-1 text-3xl sm:text-4xl font-bold tabular-nums text-white">{{ (int) ($liveQuizTakers ?? 0) }}</p>
            <p class="mt-1 text-xs text-indigo-100/90">Students with an active quiz session</p>
        </div>
    </div>

    <style>
    @keyframes live-card-breathe {
        0%, 100% { transform: scale(1); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        50% { transform: scale(1.012); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); }
    }
    @keyframes live-dot-breathe {
        0%, 100% { opacity: 0.55; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.35); }
    }
    .live-stat-card { animation: live-card-breathe 3.5s ease-in-out infinite; }
    .live-stat-pulse { animation: live-dot-breathe 2s ease-in-out infinite; }
    .live-stat-pulse--delay { animation-delay: 0.8s; }
    </style>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4 min-w-0">
        <div class="rounded-lg bg-sky-700 p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-sky-100 truncate">Staff users</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-white">{{ $overview['users'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-emerald-700 p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-emerald-100 truncate">Courses</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-white">{{ $overview['courses'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-violet-700 p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-violet-100 truncate">Class groups</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-white">{{ $overview['class_groups'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-amber-700 p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-amber-100 truncate">Students</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-white">{{ $overview['students'] ?? 0 }}</p>
        </div>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 min-w-0">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">Quick links</h2>
        <div class="flex flex-wrap gap-2 sm:gap-2">
            <a href="{{ route('dashboard.settings.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Configure app, mail, AI, and storage">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Settings</span>
            </a>
            <a href="{{ route('dashboard.institutions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Manage institutions and assign examiners">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Institutions</span>
            </a>
            <a href="{{ route('dashboard.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Manage staff (Super Admin and Examiners)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Users</span>
            </a>
            <a href="{{ route('dashboard.system.reset.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-danger-200 bg-danger-50 px-3 py-2.5 min-h-[44px] hover:bg-danger-100 hover:border-danger-300 transition-colors touch-manipulation" title="Clear data or full system reset (use with caution)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-danger-600"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Reset</span>
            </a>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    var visitorsEl = document.getElementById('live-visitors-count');
    var quizEl = document.getElementById('live-quiz-takers-count');
    if (!visitorsEl && !quizEl) return;

    var liveStatsUrl = @json(route('dashboard.live-stats'));
    var pollMs = 20000;
    var pollTimer = null;

    function applyStats(data) {
        if (!data || !data.success) return;
        if (visitorsEl && typeof data.visitors === 'number') {
            visitorsEl.textContent = String(data.visitors);
        }
        if (quizEl && typeof data.quiz_takers === 'number') {
            quizEl.textContent = String(data.quiz_takers);
        }
    }

    function fetchLiveStats() {
        fetch(liveStatsUrl, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(applyStats)
            .catch(function () {});
    }

    function schedulePoll() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(fetchLiveStats, pollMs);
    }

    fetchLiveStats();
    schedulePoll();

    if (window.QuizSnapLive && typeof window.QuizSnapLive.registerRefresher === 'function') {
        window.QuizSnapLive.registerRefresher(function (type) {
            if (type === 'sessions' || type === 'dashboard') {
                fetchLiveStats();
            }
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') fetchLiveStats();
    });
})();
</script>
@endpush

@if(($update_mode ?? false) && ($update_estimated_end ?? null))
@push('scripts')
<script>
(function () {
    var el = document.getElementById('update-mode-countdown');
    if (!el) return;
    var endMs = new Date("{{ \Carbon\Carbon::parse($update_estimated_end)->toIso8601String() }}").getTime();
    if (!endMs || Number.isNaN(endMs)) return;
    function formatLeft(totalSeconds) {
        totalSeconds = Math.max(0, Math.floor(totalSeconds));
        var h = Math.floor(totalSeconds / 3600);
        var m = Math.floor((totalSeconds % 3600) / 60);
        var s = totalSeconds % 60;
        if (h > 0) {
            return String(h) + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }
    function tick() {
        var left = Math.max(0, Math.ceil((endMs - Date.now()) / 1000));
        el.textContent = formatLeft(left);
        if (left <= 0) {
            clearInterval(timer);
        }
    }
    tick();
    var timer = setInterval(tick, 1000);
})();
</script>
@endpush
@endif
@endsection
