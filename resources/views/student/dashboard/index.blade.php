@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
<div class="space-y-5 lg:space-y-10">
<header>
    <h1 class="text-xl sm:text-2xl lg:text-[1.75rem] xl:text-3xl font-bold text-slate-900 tracking-tight">{{ $greeting ?? 'Hello' }}, {{ $displayName ?? $student?->first_name ?? 'User' }}</h1>
    <p class="text-sm lg:text-base text-slate-600 mt-1.5 lg:mt-2">Your quiz history and quick actions.</p>
</header>

@include('student.partials.dashboard-hero-banner')

@include('student.partials.dashboard-pill-nav', ['class' => 'hidden lg:block'])

@include('student.partials.dashboard-pill-nav', ['class' => 'lg:hidden mb-3', 'compact' => true])

<section aria-label="At a glance">
    <h2 class="text-[10px] sm:text-xs lg:text-sm font-semibold text-slate-500 mb-2 lg:mb-4 uppercase tracking-wider">At a glance</h2>
    <div class="grid grid-cols-3 gap-2 sm:gap-3 lg:gap-4">
        @if($student && ($hasQuizAccess ?? true))
        <a href="{{ route('dashboard.my-quizzes') }}" class="group glance-card glance-card--blue no-underline">
            <span class="glance-card__glow" aria-hidden="true"></span>
            <div class="glance-card__body">
                <div class="glance-card__icon glance-card__icon--blue">
                    <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                </div>
                <div class="glance-card__content min-w-0">
                    <span class="glance-card__value">{{ $sessionsCount ?? 0 }}</span>
                    <span class="glance-card__label">Quizzes taken</span>
                </div>
                <span class="glance-card__chevron" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
            </div>
        </a>
        @endif

        @if($student)
        @php
            $hasScheduled = isset($scheduledQuiz) && $scheduledQuiz;
            $hasScheduledResult = isset($scheduledQuizSession) && $scheduledQuizSession?->result;
            $scheduledInProgress = $hasScheduled && ! empty($scheduledOpenSession);
            $scheduledUpcoming = $hasScheduled && ! $scheduledInProgress && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture();
            $scheduledReady = $hasScheduled && ! $hasScheduledResult && ! $scheduledUpcoming && ! $scheduledInProgress;
            $showLastQuiz = isset($lastQuiz) && $lastQuiz && $lastQuiz->result && ! $scheduledReady && ! $scheduledInProgress && ! $scheduledUpcoming;
            $countdownSeconds = $scheduledUpcoming ? max(0, (int) $scheduledQuiz->starts_at->diffInSeconds(now())) : 0;
            $countdownHours = intdiv($countdownSeconds, 3600);
            $countdownMinutes = intdiv($countdownSeconds % 3600, 60);
            $countdownSecs = $countdownSeconds % 60;
            $countdownInitial = $countdownHours > 0
                ? sprintf('%d:%02d:%02d', $countdownHours, $countdownMinutes, $countdownSecs)
                : sprintf('%d:%02d', $countdownMinutes, $countdownSecs);
        @endphp
        <div class="glance-card glance-card--emerald group relative {{ ($scheduledReady || $scheduledUpcoming) ? 'glance-card--actionable' : '' }}">
            @if($showLastQuiz)
            <a href="{{ route('dashboard.my-quizzes.show', ['sessionId' => $lastQuiz->id]) }}" class="glance-card__body no-underline text-inherit min-w-0">
                <span class="glance-card__glow" aria-hidden="true"></span>
                <div class="glance-card__icon glance-card__icon--emerald">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                </div>
                <div class="glance-card__content min-w-0">
                    <span class="glance-card__value glance-card__value--sm truncate">{{ $lastQuiz->quiz?->title ?? 'Latest quiz' }}</span>
                    <span class="glance-card__label">Score: {{ number_format($lastQuiz->result->score, 1) }}%</span>
                </div>
                <span class="glance-card__chevron" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
            </a>
            @else
            <a href="@if($hasScheduled && $hasScheduledResult)
                      {{ route('dashboard.my-quizzes.show', ['sessionId' => $scheduledQuizSession->id]) }}
                  @elseif($scheduledInProgress)
                      {{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}
                  @elseif($scheduledUpcoming)
                      {{ route('student.quiz-will-start', ['token' => $scheduledQuiz->link_token]) }}
                  @elseif($hasScheduled)
                      {{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}
                  @else
                      {{ route('dashboard.my-quizzes') }}
                  @endif"
               @if($scheduledUpcoming) data-rules-url="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" @endif
               class="glance-card__body no-underline text-inherit min-w-0">
                <span class="glance-card__glow" aria-hidden="true"></span>
                <div class="glance-card__icon glance-card__icon--emerald">
                    <i class="fas fa-book-open" aria-hidden="true"></i>
                </div>
                <div class="glance-card__content min-w-0">
                    <span class="glance-card__value glance-card__value--sm truncate">
                        @if(isset($scheduledQuiz) && $scheduledQuiz)
                            {{ $scheduledQuiz->title }}
                        @else
                            No active quiz
                        @endif
                    </span>
                    <span class="glance-card__label">
                        @if(isset($scheduledQuizSession) && $scheduledQuizSession?->result)
                            Score: {{ number_format($scheduledQuizSession->result->score, 1) }}%
                        @elseif($scheduledInProgress)
                            <span class="glance-card__status glance-card__status--continue">Continue</span>
                        @elseif($scheduledUpcoming)
                            <span class="glance-card__status glance-card__status--countdown" id="quiz-countdown-{{ $scheduledQuiz->id }}" aria-live="polite">Starts in {{ $countdownInitial }}</span>
                        @elseif($scheduledReady)
                            <span class="glance-card__status glance-card__status--start">Start</span>
                        @else
                            View quizzes
                        @endif
                    </span>
                </div>
                <span class="glance-card__chevron glance-card__chevron--emerald" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
            </a>
            @endif
        </div>
        @endif

        <a href="{{ route('dashboard.my-profile') }}" class="group glance-card glance-card--violet no-underline">
            <span class="glance-card__glow" aria-hidden="true"></span>
            <div class="glance-card__body">
                <div class="glance-card__icon glance-card__icon--violet">
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                </div>
                <div class="glance-card__content min-w-0">
                    <span class="glance-card__value glance-card__value--sm">Profile</span>
                    <span class="glance-card__label">Account &amp; settings</span>
                </div>
                <span class="glance-card__chevron" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
            </div>
        </a>
    </div>
</section>

@push('styles')
<style>
    .glance-card {
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1rem;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.95);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 4px 16px rgba(15, 23, 42, 0.05);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    a.glance-card:hover,
    .glance-card:has(a.glance-card__body:hover) {
        transform: translateY(-2px);
        border-color: rgba(203, 213, 225, 0.95);
        box-shadow:
            0 4px 8px rgba(15, 23, 42, 0.05),
            0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .glance-card__glow {
        position: absolute;
        top: -1.5rem;
        right: -1.5rem;
        width: 5rem;
        height: 5rem;
        border-radius: 9999px;
        opacity: 0.55;
        pointer-events: none;
        transition: opacity 0.22s ease, transform 0.22s ease;
    }

    .glance-card--blue .glance-card__glow { background: radial-gradient(circle, rgba(59, 130, 246, 0.22) 0%, transparent 70%); }
    .glance-card--emerald .glance-card__glow { background: radial-gradient(circle, rgba(16, 185, 129, 0.22) 0%, transparent 70%); }
    .glance-card--violet .glance-card__glow { background: radial-gradient(circle, rgba(139, 92, 246, 0.2) 0%, transparent 70%); }

    a.glance-card:hover .glance-card__glow,
    .glance-card:has(a.glance-card__body:hover) .glance-card__glow {
        opacity: 0.85;
        transform: scale(1.08);
    }

    .glance-card__body {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 0.875rem 0.875rem 1rem;
        min-height: 100%;
    }

    @media (min-width: 640px) {
        .glance-card__body { padding: 1rem 1rem 1.125rem; gap: 0.875rem; }
    }

    .glance-card__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.125rem;
        height: 2.125rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        color: #fff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }

    @media (min-width: 640px) {
        .glance-card__icon { width: 2.375rem; height: 2.375rem; font-size: 0.9375rem; }
    }

    .glance-card__icon--blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.28); }
    .glance-card__icon--emerald { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.28); }
    .glance-card__icon--violet { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 6px 16px rgba(124, 58, 237, 0.28); }

    a.glance-card:hover .glance-card__icon,
    .glance-card:has(a.glance-card__body:hover) .glance-card__icon {
        transform: scale(1.05);
    }

    .glance-card__content {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .glance-card__value {
        font-size: 1.375rem;
        line-height: 1.15;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }

    .glance-card__value--sm {
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    @media (min-width: 640px) {
        .glance-card__value { font-size: 1.5rem; }
        .glance-card__value--sm { font-size: 0.875rem; }
    }

    .glance-card__label {
        font-size: 0.625rem;
        line-height: 1.35;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.01em;
    }

    .glance-card__status {
        display: inline-flex;
        align-items: center;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
    }

    .glance-card__status--start,
    .glance-card__status--continue {
        color: #059669;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.625rem;
    }

    .glance-card__status--countdown {
        color: #0f766e;
        font-weight: 600;
        font-size: 0.625rem;
    }

    .glance-card__chevron--emerald {
        background: #ecfdf5;
        color: #059669;
        opacity: 1;
        transform: none;
    }

    .glance-card--actionable .glance-card__chevron--emerald {
        opacity: 1;
    }

    @media (min-width: 640px) {
        .glance-card__status--start,
        .glance-card__status--continue {
            font-size: 0.6875rem;
        }

        .glance-card__status--countdown {
            font-size: 0.6875rem;
        }
    }

    @media (min-width: 640px) {
        .glance-card__label { font-size: 0.6875rem; }
    }

    .glance-card__chevron {
        position: absolute;
        right: 0.875rem;
        bottom: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.375rem;
        height: 1.375rem;
        border-radius: 9999px;
        background: #f8fafc;
        color: #94a3b8;
        font-size: 0.5625rem;
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity 0.22s ease, transform 0.22s ease, background 0.22s ease, color 0.22s ease;
    }

    a.glance-card:hover .glance-card__chevron,
    .glance-card:has(a.glance-card__body:hover) .glance-card__chevron {
        opacity: 1;
        transform: translateX(0);
    }

    .glance-card--blue:hover .glance-card__chevron { background: #eff6ff; color: #2563eb; }
    .glance-card--emerald:hover .glance-card__chevron { background: #ecfdf5; color: #059669; }
    .glance-card--violet:hover .glance-card__chevron { background: #f5f3ff; color: #7c3aed; }

    @media (max-width: 639px) {
        .glance-card {
            border-radius: 0.875rem;
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.03),
                0 2px 10px rgba(15, 23, 42, 0.04);
        }

        .glance-card__body {
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5625rem 0.625rem;
        }

        .glance-card__icon {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.5625rem;
            font-size: 0.6875rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1);
        }

        .glance-card__content {
            flex: 1;
            min-width: 0;
            gap: 0.0625rem;
        }

        .glance-card__value {
            font-size: 1.125rem;
            line-height: 1.1;
        }

        .glance-card__value--sm {
            font-size: 0.6875rem;
            line-height: 1.25;
        }

        .glance-card__label {
            font-size: 0.5625rem;
            line-height: 1.3;
        }

        .glance-card__status--start,
        .glance-card__status--continue {
            font-size: 0.5625rem;
        }

        .glance-card__status--countdown {
            font-size: 0.5625rem;
        }

        .glance-card__chevron {
            position: static;
            flex-shrink: 0;
            width: 1.125rem;
            height: 1.125rem;
            margin-left: auto;
            opacity: 0.45;
            transform: none;
            background: transparent;
            font-size: 0.5rem;
        }

        a.glance-card:hover .glance-card__chevron,
        .glance-card:has(a.glance-card__body:hover) .glance-card__chevron {
            opacity: 0.65;
            transform: none;
        }

        .glance-card__chevron--emerald {
            opacity: 1;
            background: #ecfdf5;
            color: #059669;
        }

        .glance-card__glow {
            width: 3.5rem;
            height: 3.5rem;
            top: -1rem;
            right: -1rem;
            opacity: 0.4;
        }
    }
</style>
@endpush

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
    var card = cardLink && cardLink.closest('.glance-card');
    var rulesUrl = cardLink && cardLink.getAttribute('data-rules-url');

    function formatCountdown(totalSeconds) {
        var h = Math.floor(totalSeconds / 3600);
        var m = Math.floor((totalSeconds % 3600) / 60);
        var s = totalSeconds % 60;
        if (h > 0) {
            return h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function setStartState() {
        el.textContent = 'Start';
        el.classList.remove('glance-card__status--countdown');
        el.classList.add('glance-card__status--start');
        if (cardLink && rulesUrl) {
            cardLink.href = rulesUrl;
        }
        if (card) {
            card.classList.add('glance-card--actionable');
        }
    }

    function update() {
        var now = Date.now();
        var left = Math.max(0, Math.floor((startMs - now) / 1000));
        if (left <= 0) {
            setStartState();
            return;
        }
        el.textContent = 'Starts in ' + formatCountdown(left);
    }

    update();
    setInterval(update, 1000);
})();
</script>
@endif
@endpush
@endif
@endsection
