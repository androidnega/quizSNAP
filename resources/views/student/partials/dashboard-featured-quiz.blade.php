@if($student ?? null)
@php
    $featuredTitle = isset($scheduledQuiz) && $scheduledQuiz
        ? $scheduledQuiz->title
        : ($showLastQuiz ? ($lastQuiz->quiz?->title ?? 'Latest quiz') : 'No active quiz');
    $featuredQuestions = isset($scheduledQuiz) && $scheduledQuiz ? ($scheduledQuiz->questions_count ?? null) : null;
@endphp
<aside aria-label="Upcoming quiz" class="sd-featured-quiz hidden lg:flex w-full h-full">
    <a href="{{ $quizActionHref }}"
       @if(($scheduledUpcoming ?? false) && ($quizRulesUrl ?? null)) data-rules-url="{{ $quizRulesUrl }}" @endif
       class="sd-featured-quiz__card group no-underline text-inherit @if($scheduledUpcoming ?? false) sd-featured-quiz__card--countdown @elseif($scheduledReady ?? false) sd-featured-quiz__card--ready @elseif($scheduledInProgress ?? false) sd-featured-quiz__card--active @endif">
        <div class="sd-featured-quiz__head">
            <span class="sd-featured-quiz__eyebrow">
                @if($showLastQuiz ?? false)
                    Latest result
                @elseif($scheduledInProgress ?? false)
                    In progress
                @elseif($scheduledUpcoming ?? false)
                    Upcoming quiz
                @elseif($scheduledReady ?? false)
                    Ready now
                @else
                    My quiz
                @endif
            </span>
            @if($scheduledQuizTypeLabel ?? null)
                <span class="sd-featured-quiz__badge sd-featured-quiz__badge--{{ $scheduledQuizExamType ?? 'quiz' }}">{{ $scheduledQuizTypeLabel }}</span>
            @endif
        </div>

        @if($scheduledQuizCourse ?? null)
            <p class="sd-featured-quiz__course">{{ $scheduledQuizCourse }}</p>
        @endif

        <h3 class="sd-featured-quiz__title">{{ $featuredTitle }}</h3>

        @if($showLastQuiz ?? false)
            <p class="sd-featured-quiz__meta">Score: {{ number_format($lastQuiz->result->score, 1) }}%</p>
            <span class="sd-featured-quiz__cta sd-featured-quiz__cta--muted">View result</span>
        @elseif(isset($scheduledQuizSession) && $scheduledQuizSession?->result)
            <p class="sd-featured-quiz__meta">Score: {{ number_format($scheduledQuizSession->result->score, 1) }}%</p>
            <span class="sd-featured-quiz__cta sd-featured-quiz__cta--muted">View result</span>
        @elseif($scheduledInProgress ?? false)
            <p class="sd-featured-quiz__meta">@if($featuredQuestions){{ $featuredQuestions }} questions @else Pick up where you left off @endif</p>
            <span class="sd-featured-quiz__cta sd-featured-quiz__cta--continue">Continue quiz</span>
        @elseif($scheduledUpcoming ?? false)
            <p class="sd-featured-quiz__meta">@if($featuredQuestions){{ $featuredQuestions }} questions @else Starts soon @endif</p>
            <span class="sd-featured-quiz__countdown" data-quiz-countdown="{{ $scheduledQuiz->id }}" aria-live="polite">{{ $countdownInitial }}</span>
            <span class="sd-featured-quiz__countdown-label">Until start</span>
            <span class="sd-featured-quiz__cta sd-featured-quiz__cta--start sd-featured-quiz__cta--after-countdown">Start quiz</span>
        @elseif($scheduledReady ?? false)
            <p class="sd-featured-quiz__meta">@if($featuredQuestions){{ $featuredQuestions }} questions @else Available now @endif</p>
            <span class="sd-featured-quiz__cta sd-featured-quiz__cta--start">Start quiz</span>
        @else
            <p class="sd-featured-quiz__meta">Browse available quizzes and past results.</p>
            <span class="sd-featured-quiz__cta sd-featured-quiz__cta--muted">View quizzes</span>
        @endif
    </a>
</aside>
@endif
