@php
    $banner = $dashboardBanner ?? \App\Models\Setting::getStudentDashboardBannerConfig();
    $displayName = $displayName ?? $student?->first_name ?? 'User';
    $initials = $student?->initials ?? strtoupper(substr(trim($displayName), 0, 1));
    $promoTitle = $banner['title'] ?? 'Challenge Yourself.';
    $promoAccent = $banner['title_accent'] ?? 'Achieve More.';
    $promoSubtitle = trim($banner['subtitle'] ?? '');
    $myQuizTitle = $hasScheduled ? $scheduledQuiz->title : ($lastQuiz?->quiz?->title ?? 'Browse quizzes');
    $myQuizMeta = '';
    if ($scheduledInProgress) {
        $myQuizMeta = 'In progress';
    } elseif ($scheduledUpcoming) {
        $myQuizMeta = 'Starts soon';
    } elseif ($scheduledReady) {
        $myQuizMeta = 'Ready to start';
    } elseif ($showLastQuiz && $lastQuiz?->result) {
        $myQuizMeta = 'Score: ' . number_format($lastQuiz->result->score, 1) . '%';
    } elseif ($hasScheduledResult && $scheduledQuizSession?->result) {
        $myQuizMeta = 'Score: ' . number_format($scheduledQuizSession->result->score, 1) . '%';
    } else {
        $myQuizMeta = 'No active quiz';
    }
@endphp

<div class="md-dash lg:hidden" aria-label="Mobile dashboard overview">
    <header class="md-dash__header">
        <div class="md-dash__profile">
            <span class="md-dash__avatar" aria-hidden="true">{{ $initials ?: 'U' }}</span>
            <div class="md-dash__greeting min-w-0">
                <span class="md-dash__hello">{{ $greeting ?? 'Hello' }},</span>
                <span class="md-dash__name truncate">{{ $displayName }}</span>
            </div>
        </div>
        <div class="md-dash__header-actions">
            <a href="{{ route('dashboard.my-quizzes') }}" class="md-dash__icon-btn" aria-label="My quizzes">
                <i class="fas fa-search" aria-hidden="true"></i>
            </a>
            <a href="{{ route('dashboard.calendar') }}" class="md-dash__icon-btn" aria-label="Calendar">
                <i class="fas fa-sliders-h" aria-hidden="true"></i>
            </a>
        </div>
    </header>

    @if(! empty($banner['enabled']))
    <section class="md-dash__promo" aria-label="Featured">
        <div class="md-dash__promo-copy">
            <h2 class="md-dash__promo-title">{{ $promoTitle }}<br><span>{{ $promoAccent }}</span></h2>
            @if($promoSubtitle !== '')
            <p class="md-dash__promo-sub">{{ $promoSubtitle }}</p>
            @endif
        </div>
        <div class="md-dash__promo-art" aria-hidden="true">
            <i class="fas fa-graduation-cap"></i>
        </div>
    </section>
    @endif

    <section class="md-dash__section" aria-label="My quiz">
        <div class="md-dash__section-head">
            <h2 class="md-dash__section-title">My quiz</h2>
            <a href="{{ route('dashboard.my-quizzes') }}" class="md-dash__section-link">See all</a>
        </div>

        <a href="{{ $quizActionHref }}"
           @if($scheduledUpcoming && $quizRulesUrl) data-rules-url="{{ $quizRulesUrl }}" @endif
           class="md-dash__course-card md-dash__course-card--quiz @if($scheduledUpcoming) md-dash__course-card--countdown @elseif($scheduledReady) md-dash__course-card--ready @elseif($scheduledInProgress) md-dash__course-card--active @endif">
            <div class="md-dash__course-top">
                <span class="md-dash__course-icon" aria-hidden="true"><i class="fas fa-book-open"></i></span>
                <div class="md-dash__course-info min-w-0">
                    @if($scheduledQuizCourse)
                    <span class="md-dash__course-label truncate">{{ $scheduledQuizCourse }}</span>
                    @endif
                    <span class="md-dash__course-title truncate">{{ $myQuizTitle }}</span>
                </div>
                @if($scheduledQuizTypeLabel)
                <span class="md-dash__course-badge">{{ $scheduledQuizTypeLabel }}</span>
                @endif
            </div>
            <div class="md-dash__progress" role="progressbar" aria-valuenow="{{ $quizProgressPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Quiz progress">
                <span class="md-dash__progress-fill" style="width: {{ $quizProgressPercent }}%;"></span>
            </div>
            <p class="md-dash__course-meta">{{ $myQuizMeta }}@if($hasScheduled && ($scheduledQuiz->questions_count ?? null)) &bull; {{ $scheduledQuiz->questions_count }} questions @endif</p>
            @if($scheduledInProgress)
            <span class="md-dash__course-cta md-dash__course-cta--continue">Continue quiz</span>
            @elseif($scheduledUpcoming)
            <span class="md-dash__course-cta md-dash__course-cta--countdown" data-quiz-countdown="{{ $scheduledQuiz->id }}" aria-live="polite">Starts in {{ $countdownInitial }}</span>
            @elseif($scheduledReady)
            <span class="md-dash__course-cta md-dash__course-cta--start">Start quiz</span>
            @elseif($showLastQuiz)
            <span class="md-dash__course-cta md-dash__course-cta--muted">View result</span>
            @endif
        </a>
    </section>

    <section class="md-dash__section" aria-label="Quick links">
        <div class="md-dash__section-head">
            <h2 class="md-dash__section-title">Quick links</h2>
        </div>
        <div class="md-dash__grid">
            @if($student && ($hasQuizAccess ?? true))
            <a href="{{ route('dashboard.my-quizzes') }}" class="md-dash__tile md-dash__tile--primary">
                <span class="md-dash__tile-icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                <span class="md-dash__tile-value">{{ $sessionsCount ?? 0 }}</span>
                <span class="md-dash__tile-label">Quizzes taken</span>
            </a>
            @endif
            <a href="{{ route('dashboard.my-quizzes') }}" class="md-dash__tile md-dash__tile--brand">
                <span class="md-dash__tile-icon" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
                <span class="md-dash__tile-label">Results</span>
                <span class="md-dash__tile-star" aria-hidden="true"><i class="fas fa-star"></i> History</span>
            </a>
            <a href="{{ route('dashboard.calendar') }}" class="md-dash__tile md-dash__tile--accent">
                <span class="md-dash__tile-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                <span class="md-dash__tile-label">Calendar</span>
                <span class="md-dash__tile-star" aria-hidden="true"><i class="fas fa-star"></i> Exams</span>
            </a>
            <a href="{{ route('dashboard.my-profile') }}" class="md-dash__tile md-dash__tile--soft">
                <span class="md-dash__tile-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                <span class="md-dash__tile-label">Profile</span>
                <span class="md-dash__tile-star" aria-hidden="true"><i class="fas fa-star"></i> Account</span>
            </a>
        </div>
    </section>
</div>

@push('styles')
<style>
    .md-dash {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding-bottom: 0.25rem;
    }

    .md-dash__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .md-dash__profile {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
        flex: 1;
    }

    .md-dash__avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.625rem;
        height: 2.625rem;
        border-radius: 0.875rem;
        background: linear-gradient(145deg, var(--theme-primary-500) 0%, var(--theme-primary-700) 100%);
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        box-shadow: 0 8px 20px color-mix(in srgb, var(--theme-primary-600) 35%, transparent);
        flex-shrink: 0;
    }

    .md-dash__greeting {
        display: flex;
        flex-direction: column;
        gap: 0.0625rem;
    }

    .md-dash__hello {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--theme-muted);
        line-height: 1.2;
    }

    .md-dash__name {
        font-size: 1.0625rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--theme-text);
        line-height: 1.15;
    }

    .md-dash__header-actions {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        flex-shrink: 0;
    }

    .md-dash__icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.375rem;
        height: 2.375rem;
        border-radius: 0.75rem;
        background: var(--theme-surface);
        border: 1px solid var(--theme-border);
        color: var(--theme-text);
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .md-dash__icon-btn:active {
        transform: scale(0.96);
    }

    .md-dash__promo {
        position: relative;
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        gap: 0.75rem;
        min-height: 7.5rem;
        padding: 1.125rem 1rem 1.125rem 1.125rem;
        border-radius: 1.625rem;
        overflow: hidden;
        background: linear-gradient(135deg, var(--theme-primary-500) 0%, var(--theme-primary-700) 55%, var(--theme-brand-deep) 100%);
        box-shadow:
            0 10px 28px color-mix(in srgb, var(--theme-primary-600) 42%, transparent),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
        text-decoration: none;
        color: #fff;
    }

    .md-dash__promo-copy {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
        flex: 1;
        padding-right: 0.5rem;
    }

    .md-dash__promo-title {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 800;
        line-height: 1.25;
        letter-spacing: -0.03em;
    }

    .md-dash__promo-title span {
        display: inline-block;
        margin-top: 0.125rem;
        opacity: 0.92;
    }

    .md-dash__promo-sub {
        margin: 0.375rem 0 0;
        font-size: 0.6875rem;
        line-height: 1.45;
        opacity: 0.88;
        max-width: 14rem;
    }

    .md-dash__promo-art {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        width: 5.5rem;
        flex-shrink: 0;
        font-size: 3.25rem;
        opacity: 0.92;
        filter: drop-shadow(0 10px 18px rgba(15, 23, 42, 0.22));
        transform: translateY(0.35rem) rotate(-8deg);
    }

    .md-dash__section {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .md-dash__section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .md-dash__section-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--theme-text);
    }

    .md-dash__section-link {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--theme-primary-600);
        text-decoration: none;
    }

    .md-dash__course-card {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        padding: 1rem;
        border-radius: 1.375rem;
        text-decoration: none;
        color: #fff;
        background: linear-gradient(135deg, var(--theme-brand) 0%, var(--theme-brand-deep) 100%);
        box-shadow:
            0 12px 28px color-mix(in srgb, var(--theme-brand) 45%, transparent),
            inset 0 1px 0 rgba(255, 255, 255, 0.1);
        transition: transform 0.15s ease;
    }

    .md-dash__course-card:active {
        transform: scale(0.99);
    }

    .md-dash__course-top {
        display: flex;
        align-items: flex-start;
        gap: 0.625rem;
        min-width: 0;
    }

    .md-dash__course-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.375rem;
        height: 2.375rem;
        border-radius: 0.875rem;
        background: rgba(255, 255, 255, 0.16);
        font-size: 0.9375rem;
        flex-shrink: 0;
    }

    .md-dash__course-info {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        flex: 1;
        min-width: 0;
    }

    .md-dash__course-label {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        opacity: 0.82;
    }

    .md-dash__course-title {
        font-size: 0.9375rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.3;
    }

    .md-dash__course-badge {
        flex-shrink: 0;
        padding: 0.1875rem 0.4375rem;
        border-radius: 9999px;
        font-size: 0.5625rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .md-dash__progress {
        position: relative;
        height: 0.4375rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.22);
        overflow: hidden;
    }

    .md-dash__progress-fill {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: #fff;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.45);
        transition: width 0.35s ease;
    }

    .md-dash__course-meta {
        margin: 0;
        font-size: 0.6875rem;
        font-weight: 600;
        opacity: 0.88;
        line-height: 1.35;
    }

    .md-dash__course-cta {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 0.125rem;
        padding: 0.6875rem 1rem;
        border-radius: 0.875rem;
        font-size: 0.8125rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        font-variant-numeric: tabular-nums;
    }

    .md-dash__course-cta--start,
    .md-dash__course-cta--continue {
        color: var(--theme-brand-deep);
        background: #fff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
    }

    .md-dash__course-cta--countdown {
        color: var(--theme-brand-deep);
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .md-dash__course-cta--muted {
        color: #fff;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .md-dash__course-card--countdown.is-ready .md-dash__course-cta--countdown {
        color: var(--theme-brand-deep);
        background: #fff;
    }

    .md-dash__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .md-dash__tile {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0.25rem;
        min-height: 7.75rem;
        padding: 0.875rem;
        border-radius: 1.375rem;
        text-decoration: none;
        color: #fff;
        overflow: hidden;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        transition: transform 0.15s ease;
    }

    .md-dash__tile:active {
        transform: scale(0.98);
    }

    .md-dash__tile--primary {
        background: linear-gradient(145deg, var(--theme-primary-500) 0%, var(--theme-primary-800) 100%);
    }

    .md-dash__tile--brand {
        background: linear-gradient(145deg, color-mix(in srgb, var(--theme-brand) 88%, #fff) 0%, var(--theme-brand-deep) 100%);
    }

    .md-dash__tile--accent {
        background: linear-gradient(145deg, var(--theme-primary-400) 0%, var(--theme-primary-700) 100%);
    }

    .md-dash__tile--soft {
        background: linear-gradient(145deg, color-mix(in srgb, var(--theme-brand-soft) 70%, var(--theme-primary-300)) 0%, var(--theme-primary-600) 100%);
        color: var(--theme-text);
    }

    .md-dash__tile-icon {
        position: absolute;
        top: 0.875rem;
        right: 0.875rem;
        font-size: 1.625rem;
        opacity: 0.28;
    }

    .md-dash__tile-value {
        font-size: 1.625rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .md-dash__tile-label {
        font-size: 0.8125rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }

    .md-dash__tile-star {
        font-size: 0.625rem;
        font-weight: 700;
        opacity: 0.88;
    }

    .md-dash__tile-star i {
        font-size: 0.5625rem;
        margin-right: 0.125rem;
    }
</style>
@endpush
