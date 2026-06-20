<section aria-label="At a glance">
    <h2 class="text-[10px] sm:text-xs lg:text-sm font-semibold text-slate-500 mb-2 lg:mb-4 uppercase tracking-wider">At a glance</h2>
    <div class="glance-grid grid grid-cols-3 gap-2 sm:gap-3 lg:gap-4">
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
        <div class="glance-card glance-card--emerald group relative {{ ($scheduledReady || $scheduledUpcoming || $scheduledInProgress) ? 'glance-card--actionable glance-card--has-cta' : '' }}">
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
            <a href="{{ $quizActionHref }}"
               @if($scheduledUpcoming && $quizRulesUrl) data-rules-url="{{ $quizRulesUrl }}" @endif
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
                    @if(isset($scheduledQuizSession) && $scheduledQuizSession?->result)
                        <span class="glance-card__label">Score: {{ number_format($scheduledQuizSession->result->score, 1) }}%</span>
                    @elseif($scheduledInProgress)
                        <span class="glance-card__label glance-card__label--hint">In progress</span>
                        <span class="glance-card__cta glance-card__cta--in-card glance-card__cta--continue">Continue</span>
                    @elseif($scheduledUpcoming)
                        <span class="glance-card__label glance-card__label--hint">Upcoming</span>
                        <span class="glance-card__cta glance-card__cta--in-card glance-card__cta--countdown" data-quiz-countdown="{{ $scheduledQuiz->id }}" aria-live="polite">Starts in {{ $countdownInitial }}</span>
                    @elseif($scheduledReady)
                        <span class="glance-card__label glance-card__label--hint">Ready now</span>
                        <span class="glance-card__cta glance-card__cta--in-card glance-card__cta--start">Start quiz</span>
                    @else
                        <span class="glance-card__label">View quizzes</span>
                    @endif
                </div>
                @if(! $scheduledInProgress && ! $scheduledUpcoming && ! $scheduledReady)
                <span class="glance-card__chevron glance-card__chevron--emerald" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                @endif
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

    @if($student && ($showMobileQuizBar ?? false))
    <a href="{{ $quizActionHref }}"
       @if(($scheduledUpcoming ?? false) && ($quizRulesUrl ?? null)) data-rules-url="{{ $quizRulesUrl }}" @endif
       class="glance-mobile-quiz-panel lg:hidden @if($scheduledInProgress) glance-mobile-quiz-panel--continue @elseif($scheduledUpcoming) glance-mobile-quiz-panel--countdown @else glance-mobile-quiz-panel--start @endif">
        <div class="glance-mobile-quiz-panel__head">
            <span class="glance-mobile-quiz-panel__course">{{ $scheduledQuizCourse ?: 'Course' }}</span>
            <span class="glance-mobile-quiz-panel__type glance-mobile-quiz-panel__type--{{ $scheduledQuizExamType }}">{{ $scheduledQuizTypeLabel ?: 'Quiz' }}</span>
        </div>
        <p class="glance-mobile-quiz-panel__title">{{ $scheduledQuiz->title }}</p>
        <span class="glance-mobile-quiz-panel__cta @if($scheduledInProgress) glance-mobile-quiz-panel__cta--continue @elseif($scheduledUpcoming) glance-mobile-quiz-panel__cta--countdown @else glance-mobile-quiz-panel__cta--start @endif">
            @if($scheduledInProgress)
                Continue quiz
            @elseif($scheduledUpcoming)
                <span data-quiz-countdown="{{ $scheduledQuiz->id }}" aria-live="polite">Starts in {{ $countdownInitial }}</span>
            @else
                Start quiz
            @endif
        </span>
    </a>
    @endif
</section>
