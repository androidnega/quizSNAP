@extends('layouts.dashboard')

@section('title', $quiz->title)
@section('dashboard_heading', \Illuminate\Support\Str::limit($quiz->title, 40))

@section('dashboard_content')
@php $activeTab = request('tab', 'overview'); @endphp
<div class="w-full min-w-0 space-y-4">
    @if(!empty($aiGenerationProgress) && in_array($aiGenerationProgress['status'] ?? '', ['running', 'failed'], true))
    <div id="ai-generation-banner" class="rounded-lg border p-4 flex items-start gap-3 {{ ($aiGenerationProgress['status'] ?? '') === 'failed' ? 'bg-red-50 border-red-300' : 'bg-indigo-50 border-indigo-300' }}"
         data-status-url="{{ route('dashboard.quizzes.ai-generation-status', $quiz) }}">
        @if(($aiGenerationProgress['status'] ?? '') === 'failed')
            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-red-900">AI question generation failed</p>
                <p id="ai-generation-message" class="text-sm text-red-800 mt-1">{{ $aiGenerationProgress['message'] ?? 'Try generating again from the overview below.' }}</p>
            </div>
        @else
            <svg class="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-indigo-900">AI is generating questions</p>
                <p id="ai-generation-message" class="text-sm text-indigo-800 mt-1">{{ $aiGenerationProgress['message'] ?? 'This runs in the background. The page will refresh when complete.' }}</p>
                <div class="mt-2 w-full max-w-md bg-indigo-200 rounded-full h-2">
                    @php
                        $genTarget = max(1, (int) ($aiGenerationProgress['target'] ?? $quiz->number_of_questions));
                        $genDone = (int) ($aiGenerationProgress['generated'] ?? 0);
                        $genPct = min(100, (int) round(($genDone / $genTarget) * 100));
                    @endphp
                    <div id="ai-generation-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ $genPct }}%"></div>
                </div>
                <p id="ai-generation-counts" class="text-xs text-indigo-700 mt-1">{{ $genDone }} / {{ $genTarget }} in pool</p>
            </div>
        @endif
    </div>
    @endif
    {{-- Compact header with tabs integrated --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                        <span>{{ $quiz->course->name ?? '-' }}</span>
                        <span>•</span>
                        <span>{{ $quiz->getQuestionsPerStudent() }} per student</span>
                        <span>•</span>
                        <span>{{ $quiz->duration_minutes }} min</span>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        @php $displayStatus = $quiz->getDisplayStatus(); @endphp
                        @if($displayStatus === 'Draft')
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600">Draft</span>
                        @elseif($displayStatus === 'Scheduled')
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-amber-100 text-amber-800">Scheduled</span>
                        @elseif($displayStatus === 'Active')
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-success-100 text-success-700">Active</span>
                        @elseif($displayStatus === 'Ended')
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-gray-200 text-gray-700">Ended</span>
                        @endif
                        @if(!$quiz->hasEnded() && $quiz->is_active && !$quiz->hasEnoughApprovedQuestions())
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700">Locked</span>
                        @endif
                        @if(isset($allowedDevicesEffective))
                            @php
                                $dev = $allowedDevicesEffective;
                                $devLabel = $dev === 'desktop' ? 'Desktop only' : ($dev === 'mobile' ? 'Mobile only' : 'Both');
                                $devClass = $dev === 'mobile' ? 'bg-sky-100 text-sky-800' : ($dev === 'both' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600');
                            @endphp
                            <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded {{ $devClass }}" title="Allowed devices: set by coordinator on class group">{{ $devLabel }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if(!$quiz->hasStarted())
                        <a href="{{ route('dashboard.quizzes.edit', $quiz) }}" class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500">Edit</a>
                        <form action="{{ route('dashboard.quizzes.destroy', $quiz) }}" method="post" class="inline" onsubmit="return confirm('Delete this quiz?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:ring-2 focus:ring-red-500">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Tabs (live: no full page reload) --}}
        <div class="border-b border-gray-100 bg-gray-50" id="quiz-tabs-nav" data-quiz-show-url="{{ route('dashboard.quizzes.show', $quiz) }}">
            <nav class="flex px-4 gap-1" aria-label="Quiz sections">
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'overview']) }}" data-quiz-tab="overview"
                   class="quiz-tab-link py-3 px-4 text-sm font-semibold whitespace-nowrap border-b-3 transition-all flex items-center gap-2 {{ $activeTab === 'overview' ? 'border-primary-500 text-primary-700 bg-white shadow-sm' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-white/70' }}">
                    <i class="fas fa-info-circle"></i>
                    <span>Overview</span>
                </a>
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions']) }}" data-quiz-tab="sessions"
                   class="quiz-tab-link py-3 px-4 text-sm font-semibold whitespace-nowrap border-b-3 transition-all flex items-center gap-2 {{ $activeTab === 'sessions' ? 'border-primary-500 text-primary-700 bg-white shadow-sm' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-white/70' }}">
                    <i class="fas fa-users"></i>
                    <span>Sessions</span>
                    @if($sessionsStats['total_students'] > 0)
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-100 text-primary-700">{{ $sessionsStats['total_students'] }}</span>
                    @endif
                </a>
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'scores']) }}" data-quiz-tab="scores"
                   class="quiz-tab-link py-3 px-4 text-sm font-semibold whitespace-nowrap border-b-3 transition-all flex items-center gap-2 {{ $activeTab === 'scores' ? 'border-primary-500 text-primary-700 bg-white shadow-sm' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-white/70' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Scores &amp; Export</span>
                </a>
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'analytics']) }}" data-quiz-tab="analytics"
                   class="quiz-tab-link py-3 px-4 text-sm font-semibold whitespace-nowrap border-b-3 transition-all flex items-center gap-2 {{ $activeTab === 'analytics' ? 'border-primary-500 text-primary-700 bg-white shadow-sm' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-white/70' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Question analytics</span>
                </a>
            </nav>
        </div>
    </div>

    <div id="quiz-tab-content" data-current-tab="{{ $activeTab }}">
    @include('admin.quizzes.partials.' . $activeTab)
    </div>
</div>

@push('scripts')
<script>
(function() {
    var container = document.getElementById('quiz-tab-content');
    var nav = document.getElementById('quiz-tabs-nav');
    if (!container || !nav) return;
    var baseUrl = (nav.getAttribute('data-quiz-show-url') || '').split('?')[0];

    function isQuizShowLink(href) {
        if (!href || href.indexOf('#') === 0) return false;
        try {
            var u = href.split('?')[0];
            return u === baseUrl || u === baseUrl + '/';
        } catch (e) { return false; }
    }

    function setActiveTab(tab) {
        container.setAttribute('data-current-tab', tab);
        nav.querySelectorAll('.quiz-tab-link').forEach(function(a) {
            var isActive = (a.getAttribute('data-quiz-tab') || '') === tab;
            a.classList.toggle('border-primary-500', isActive);
            a.classList.toggle('text-primary-700', isActive);
            a.classList.toggle('bg-white', isActive);
            a.classList.toggle('shadow-sm', isActive);
            a.classList.toggle('border-transparent', !isActive);
            a.classList.toggle('text-gray-600', !isActive);
            a.classList.toggle('hover:text-gray-900', !isActive);
            a.classList.toggle('hover:bg-white/70', !isActive);
        });
    }

    function loadTab(url) {
        var wrap = document.createElement('div');
        wrap.innerHTML = '<div class="flex items-center justify-center py-12 text-gray-500"><span>Loading…</span></div>';
        container.innerHTML = '';
        container.appendChild(wrap.firstElementChild);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                container.innerHTML = html;
                var scripts = container.querySelectorAll('script');
                scripts.forEach(function(oldScript) {
                    var newScript = document.createElement('script');
                    if (oldScript.src) { newScript.src = oldScript.src; newScript.async = false; }
                    else { newScript.textContent = oldScript.textContent; }
                    container.appendChild(newScript);
                });
                var tab = (url.match(/[?&]tab=([^&]+)/) || [])[1] || 'overview';
                setActiveTab(tab);
                if (typeof history !== 'undefined' && history.pushState) {
                    history.pushState({ tab: tab }, '', url);
                }
            })
            .catch(function() {
                container.innerHTML = '<div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-700">Failed to load. <a href="' + url + '">Reload page</a></div>';
            });
    }

    document.addEventListener('click', function(e) {
        var a = e.target && (e.target.closest ? e.target.closest('a') : e.target);
        if (!a || !a.href || a.target === '_blank' || a.getAttribute('download')) return;
        if (isQuizShowLink(a.href)) {
            e.preventDefault();
            loadTab(a.href);
        }
    }, true);

    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.tab) {
            var url = baseUrl + '?tab=' + e.state.tab;
            loadTab(url);
        }
    });

    // Live search: delegate from container so it works after AJAX load
    container.addEventListener('input', function(e) {
        var id = e.target && e.target.id;
        var q = (e.target.value || '').trim();
        if (id === 'sessions-search-index') {
            var qUpper = q.toUpperCase().trim();
            container.querySelectorAll('.sessions-row').forEach(function(row) {
                var index = (row.getAttribute('data-student-index') || '').toUpperCase().trim();
                // Use includes for more flexible matching, handle special chars
                var normalizedQuery = qUpper.replace(/[^A-Z0-9]/g, '');
                var normalizedIndex = index.replace(/[^A-Z0-9]/g, '');
                var matches = !qUpper || index.indexOf(qUpper) !== -1 || normalizedIndex.indexOf(normalizedQuery) !== -1;
                row.style.display = matches ? '' : 'none';
            });
        } else if (id === 'questions-search' || id === 'pool-search') {
            var qLower = q.toLowerCase();
            var selector = id === 'questions-search' ? '.approved-question-row' : '.pool-question-row';
            container.querySelectorAll(selector).forEach(function(row) {
                var text = (row.getAttribute('data-search') || '');
                row.style.display = !qLower || text.indexOf(qLower) !== -1 ? '' : 'none';
            });
        }
    });
    container.addEventListener('keyup', function(e) {
        if (e.target && (e.target.id === 'sessions-search-index' || e.target.id === 'questions-search' || e.target.id === 'pool-search')) {
            e.target.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
})();

(function() {
    var banner = document.getElementById('ai-generation-banner');
    if (!banner || banner.dataset.statusUrl === undefined) return;
    var statusUrl = banner.dataset.statusUrl;
    var pollMs = 3000;

    function poll() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(data) {
                var status = data.status || 'idle';
                if (status === 'running') {
                    var target = Math.max(1, parseInt(data.target, 10) || 1);
                    var generated = parseInt(data.generated, 10) || 0;
                    var pct = Math.min(100, Math.round((generated / target) * 100));
                    var bar = document.getElementById('ai-generation-bar');
                    var counts = document.getElementById('ai-generation-counts');
                    var msg = document.getElementById('ai-generation-message');
                    if (bar) bar.style.width = pct + '%';
                    if (counts) counts.textContent = generated + ' / ' + target + ' in pool';
                    if (msg) msg.textContent = data.message || ('Generating… ' + generated + ' of ' + target);
                    setTimeout(poll, pollMs);
                } else if (status === 'completed') {
                    var doneMsg = document.getElementById('ai-generation-message');
                    if (doneMsg) doneMsg.textContent = data.message || 'Generation complete. Refreshing…';
                    setTimeout(function() { window.location.reload(); }, 600);
                } else if (status === 'failed') {
                    banner.classList.remove('bg-indigo-50', 'border-indigo-300');
                    banner.classList.add('bg-red-50', 'border-red-300');
                    var msg = document.getElementById('ai-generation-message');
                    if (msg) {
                        msg.textContent = data.message || 'Question generation failed. Try again from the overview below.';
                        msg.classList.remove('text-indigo-800');
                        msg.classList.add('text-red-800');
                    }
                } else if (status === 'idle') {
                    var idleMsg = document.getElementById('ai-generation-message');
                    if (idleMsg && data.message) idleMsg.textContent = data.message;
                    setTimeout(poll, pollMs);
                }
            })
            .catch(function() {
                var msg = document.getElementById('ai-generation-message');
                if (msg) msg.textContent = 'Could not reach the server. Retrying…';
                setTimeout(poll, pollMs * 2);
            });
    }

    setTimeout(poll, pollMs);
})();
</script>
@endpush
@endsection
