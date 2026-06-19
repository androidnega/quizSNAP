@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
<div class="space-y-6 lg:space-y-10">
<header>
    <h1 class="text-xl sm:text-2xl lg:text-[1.75rem] xl:text-3xl font-bold text-slate-900 tracking-tight">{{ $greeting ?? 'Hello' }}, {{ $displayName ?? $student?->first_name ?? 'User' }}</h1>
    <p class="text-sm lg:text-base text-slate-600 mt-1.5 lg:mt-2">Your quiz history and quick actions.</p>
</header>

@include('student.partials.dashboard-hero-banner')

@include('student.partials.dashboard-nav-grid')

<nav class="hidden lg:block" aria-label="Dashboard sections">
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard') ? 'bg-amber-400 border-amber-400 text-slate-900' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
            <i class="fas fa-home mr-1.5 text-xs"></i>
            Overview
        </a>

        @if($student)
        <a href="{{ route('dashboard.my-quizzes') }}"
           class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.my-quizzes*') ? 'bg-amber-400 border-amber-400 text-slate-900' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
            <i class="fas fa-clipboard-list mr-1.5 text-xs"></i>
            Quizzes
        </a>
        <a href="{{ route('dashboard.calendar') }}"
           class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.calendar') ? 'bg-amber-400 border-amber-400 text-slate-900' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
            <i class="fas fa-calendar-alt mr-1.5 text-xs"></i>
            Calendar
        </a>
        <a href="{{ route('dashboard.course-materials') }}"
           class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.course-materials') ? 'bg-amber-400 border-amber-400 text-slate-900' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
            <i class="fas fa-book mr-1.5 text-xs"></i>
            Materials
        </a>
        @endif

        <a href="{{ route('dashboard.my-profile') }}"
           class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.my-profile') ? 'bg-amber-400 border-amber-400 text-slate-900' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
            <i class="fas fa-user mr-1.5 text-xs"></i>
            Profile
        </a>

        @if($student)
        <a href="{{ route('dashboard.my-quizzes') }}"
           class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors whitespace-nowrap">
            <i class="fas fa-file-alt mr-1.5 text-xs"></i>
            Class results
        </a>
        @endif
    </div>
</nav>

<section aria-label="At a glance">
    <h2 class="text-[10px] sm:text-xs lg:text-sm font-semibold text-slate-500 mb-3 lg:mb-5 uppercase tracking-wider">At a glance</h2>
    <div class="grid grid-cols-3 gap-2 sm:gap-3 lg:gap-5">
        @if($student && ($hasQuizAccess ?? true))
        <a href="{{ route('dashboard.my-quizzes') }}" class="rounded-2xl p-3 sm:p-5 lg:p-6 flex flex-col no-underline hover:opacity-95 transition-opacity min-h-[100px] lg:min-h-[128px]" style="background-color: #dbeafe;">
            <span class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-base shrink-0" style="background-color: #bfdbfe; color: #1d4ed8;"><i class="fas fa-clipboard-list"></i></span>
            <span class="text-xl sm:text-2xl lg:text-3xl font-bold tabular-nums mt-2 sm:mt-3 lg:mt-4 truncate text-slate-900">{{ $sessionsCount ?? 0 }}</span>
            <span class="text-[9px] sm:text-[10px] lg:text-xs font-bold uppercase tracking-wide mt-0.5 sm:mt-1 lg:mt-1.5 truncate text-slate-600 leading-tight">Quizzes taken</span>
        </a>
        @endif

        @if($student)
        @php
            $hasScheduled = isset($scheduledQuiz) && $scheduledQuiz;
            $hasScheduledResult = isset($scheduledQuizSession) && $scheduledQuizSession?->result;
            $scheduledUpcoming = $hasScheduled && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture();
            $scheduledActive = $hasScheduled && !$hasScheduledResult && !$scheduledUpcoming;
            $showLastQuiz = isset($lastQuiz) && $lastQuiz && $lastQuiz->result && !$scheduledActive;
        @endphp
        <div class="rounded-2xl p-3 sm:p-5 lg:p-6 flex flex-col min-h-[100px] lg:min-h-[128px] relative overflow-hidden" style="background-color: #d1fae5;">
            @if($showLastQuiz)
            <a href="{{ route('dashboard.my-quizzes.show', ['sessionId' => $lastQuiz->id]) }}" class="flex flex-col flex-1 no-underline text-inherit hover:opacity-90 transition-opacity min-w-0">
                <span class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-base shrink-0" style="background-color: #a7f3d0; color: #047857;"><i class="fas fa-book"></i></span>
                <span class="text-xs sm:text-sm lg:text-base font-bold mt-2 sm:mt-3 lg:mt-4 truncate text-slate-900">{{ $lastQuiz->quiz?->title ?? 'Latest quiz' }}</span>
                <span class="text-[9px] sm:text-[10px] lg:text-xs font-bold uppercase tracking-wide mt-0.5 sm:mt-1 lg:mt-1.5 truncate text-slate-600 leading-tight">Score: {{ number_format($lastQuiz->result->score, 1) }}%</span>
            </a>
            @else
            <a href="@if($hasScheduled && $hasScheduledResult)
                      {{ route('dashboard.my-quizzes.show', ['sessionId' => $scheduledQuizSession->id]) }}
                  @elseif($scheduledUpcoming)
                      {{ route('student.quiz-will-start', ['token' => $scheduledQuiz->link_token]) }}
                  @elseif($hasScheduled)
                      {{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}
                  @else
                      {{ route('dashboard.my-quizzes') }}
                  @endif"
               @if($scheduledUpcoming) data-rules-url="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" @endif
               class="flex flex-col flex-1 no-underline text-inherit hover:opacity-90 transition-opacity min-w-0">
                <span class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-base shrink-0" style="background-color: #a7f3d0; color: #047857;"><i class="fas fa-book"></i></span>
                <span class="text-xs sm:text-sm lg:text-base font-bold mt-2 sm:mt-3 lg:mt-4 truncate text-slate-900">
                    @if(isset($scheduledQuiz) && $scheduledQuiz)
                        {{ $scheduledQuiz->title }}
                    @else
                        No active quiz
                    @endif
                </span>
                <span class="text-[9px] sm:text-[10px] lg:text-xs font-bold uppercase tracking-wide mt-0.5 sm:mt-1 lg:mt-1.5 truncate text-slate-600 leading-tight">
                    @if(isset($scheduledQuizSession) && $scheduledQuizSession?->result)
                        Score: {{ number_format($scheduledQuizSession->result->score, 1) }}%
                    @elseif($scheduledUpcoming)
                        <span id="quiz-countdown-{{ $scheduledQuiz->id }}" aria-live="polite">—</span>
                    @elseif($scheduledActive)
                        Ready to take
                    @else
                        View quizzes
                    @endif
                </span>
            </a>
            @if($scheduledActive && $scheduledQuiz)
            <a href="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" class="mt-3 lg:mt-4 self-start inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide text-amber-900 bg-amber-400 hover:bg-amber-500 transition-colors no-underline">Start</a>
            @endif
            @endif
        </div>
        @endif

        <a href="{{ route('dashboard.my-profile') }}" class="rounded-2xl p-3 sm:p-5 lg:p-6 flex flex-col no-underline hover:opacity-95 transition-opacity min-h-[100px] lg:min-h-[128px]" style="background-color: #fef3c7;">
            <span class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-base shrink-0" style="background-color: #fde68a; color: #b45309;"><i class="fas fa-user"></i></span>
            <span class="text-xs sm:text-sm lg:text-base font-bold mt-2 sm:mt-3 lg:mt-4 truncate text-slate-900">View</span>
            <span class="text-[9px] sm:text-[10px] lg:text-xs font-bold uppercase tracking-wide mt-0.5 sm:mt-1 lg:mt-1.5 truncate text-slate-600 leading-tight">Profile</span>
        </a>
    </div>
</section>

<section aria-label="Quick access" class="lg:hidden">
    <h2 class="text-[10px] sm:text-xs lg:text-sm font-semibold text-slate-500 mb-3 lg:mb-5 uppercase tracking-wider">Quick access</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 lg:gap-5">
        <a href="{{ route('dashboard.calendar') }}" class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 lg:px-6 lg:py-5 flex items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 transition-colors min-h-[72px] lg:min-h-[80px]">
            <div class="flex items-center gap-3 lg:gap-4 min-w-0">
                <span class="w-11 h-11 lg:w-12 lg:h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0">
                    <i class="fas fa-calendar-alt text-sm"></i>
                </span>
                <div class="min-w-0">
                    <span class="text-sm lg:text-base font-semibold text-slate-900 block truncate">Calendar</span>
                    <span class="text-xs lg:text-sm text-slate-600 block truncate mt-0.5">Exam & quiz dates</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 ml-3"></i>
        </a>
        @if($student)
        <a href="{{ route('dashboard.my-quizzes') }}" class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 lg:px-6 lg:py-5 flex items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 transition-colors min-h-[72px] lg:min-h-[80px]">
            <div class="flex items-center gap-3 lg:gap-4 min-w-0">
                <span class="w-11 h-11 lg:w-12 lg:h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0">
                    <i class="fas fa-file-alt text-sm"></i>
                </span>
                <div class="min-w-0">
                    <span class="text-sm lg:text-base font-semibold text-slate-900 block truncate">Class results</span>
                    <span class="text-xs lg:text-sm text-slate-600 block truncate mt-0.5">See class results</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 ml-3"></i>
        </a>
        @endif
    </div>
</section>
</div>

@if($student)
@push('scripts')
@if(isset($scheduledQuiz) && $scheduledQuiz && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
<script>
(function() {
    var startsAt = @json($scheduledQuiz->starts_at->toIso8601String());
    var startMs = new Date(startsAt).getTime();
    var el = document.getElementById('quiz-countdown-{{ $scheduledQuiz->id }}');
    if (!el) return;
    var cardLink = el.closest('a');
    var rulesUrl = cardLink && cardLink.getAttribute('data-rules-url');
    function update() {
        var now = Date.now();
        var left = Math.max(0, Math.floor((startMs - now) / 1000));
        if (left <= 0) {
            el.textContent = 'Start';
            if (cardLink && rulesUrl) cardLink.href = rulesUrl;
            return;
        }
        var h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
        el.textContent = (h > 0 ? h + ':' : '') + (m < 10 && h > 0 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }
    update();
    setInterval(update, 1000);
})();
</script>
@endif
@endpush
@endif
@endsection
