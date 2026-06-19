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

@include('student.partials.dashboard-pill-nav', ['class' => 'hidden lg:block'])

<section aria-label="At a glance">
    <h2 class="text-[10px] sm:text-xs lg:text-sm font-semibold text-slate-500 mb-2.5 lg:mb-4 uppercase tracking-wider">At a glance</h2>
    <div class="grid grid-cols-3 gap-2 sm:gap-3 lg:gap-4">
        @if($student && ($hasQuizAccess ?? true))
        <a href="{{ route('dashboard.my-quizzes') }}" class="group rounded-xl border border-blue-200/80 bg-gradient-to-br from-blue-50 to-blue-100/90 p-3 sm:p-3.5 flex flex-col no-underline transition-all duration-200 ease-out hover:border-blue-300 hover:from-blue-100 hover:to-blue-200/80 hover:shadow-md hover:shadow-blue-100/80 hover:-translate-y-0.5">
            <span class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-blue-600/10 text-blue-700 flex items-center justify-center text-sm shrink-0 transition-colors duration-200 group-hover:bg-blue-600 group-hover:text-white"><i class="fas fa-clipboard-list"></i></span>
            <span class="text-lg sm:text-xl font-bold tabular-nums mt-2 truncate text-slate-900">{{ $sessionsCount ?? 0 }}</span>
            <span class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide mt-0.5 truncate text-blue-800/70 leading-tight">Quizzes taken</span>
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
        <div class="group rounded-xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50 to-emerald-100/90 p-3 sm:p-3.5 flex flex-col relative overflow-hidden transition-all duration-200 ease-out hover:border-emerald-300 hover:from-emerald-100 hover:to-emerald-200/80 hover:shadow-md hover:shadow-emerald-100/80 hover:-translate-y-0.5">
            @if($showLastQuiz)
            <a href="{{ route('dashboard.my-quizzes.show', ['sessionId' => $lastQuiz->id]) }}" class="flex flex-col flex-1 no-underline text-inherit min-w-0">
                <span class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-emerald-600/10 text-emerald-700 flex items-center justify-center text-sm shrink-0 transition-colors duration-200 group-hover:bg-emerald-600 group-hover:text-white"><i class="fas fa-book"></i></span>
                <span class="text-xs sm:text-sm font-bold mt-2 truncate text-slate-900">{{ $lastQuiz->quiz?->title ?? 'Latest quiz' }}</span>
                <span class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide mt-0.5 truncate text-emerald-800/70 leading-tight">Score: {{ number_format($lastQuiz->result->score, 1) }}%</span>
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
               class="flex flex-col flex-1 no-underline text-inherit min-w-0">
                <span class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-emerald-600/10 text-emerald-700 flex items-center justify-center text-sm shrink-0 transition-colors duration-200 group-hover:bg-emerald-600 group-hover:text-white"><i class="fas fa-book"></i></span>
                <span class="text-xs sm:text-sm font-bold mt-2 truncate text-slate-900">
                    @if(isset($scheduledQuiz) && $scheduledQuiz)
                        {{ $scheduledQuiz->title }}
                    @else
                        No active quiz
                    @endif
                </span>
                <span class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide mt-0.5 truncate text-emerald-800/70 leading-tight">
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
            <a href="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" class="mt-2 self-start inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wide text-amber-950 bg-amber-400 hover:bg-amber-500 hover:shadow-sm transition-all duration-200 no-underline">Start</a>
            @endif
            @endif
        </div>
        @endif

        <a href="{{ route('dashboard.my-profile') }}" class="group rounded-xl border border-amber-200/80 bg-gradient-to-br from-amber-50 to-amber-100/90 p-3 sm:p-3.5 flex flex-col no-underline transition-all duration-200 ease-out hover:border-amber-300 hover:from-amber-100 hover:to-amber-200/80 hover:shadow-md hover:shadow-amber-100/80 hover:-translate-y-0.5">
            <span class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-amber-600/10 text-amber-800 flex items-center justify-center text-sm shrink-0 transition-colors duration-200 group-hover:bg-amber-600 group-hover:text-white"><i class="fas fa-user"></i></span>
            <span class="text-xs sm:text-sm font-bold mt-2 truncate text-slate-900">View</span>
            <span class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-wide mt-0.5 truncate text-amber-900/70 leading-tight">Profile</span>
        </a>
    </div>
</section>

@include('student.partials.dashboard-pill-nav', ['class' => 'lg:hidden mt-4', 'compact' => true])
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
