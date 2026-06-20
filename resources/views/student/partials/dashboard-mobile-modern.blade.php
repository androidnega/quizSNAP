@php
    $banner = $dashboardBanner ?? \App\Models\Setting::getStudentDashboardBannerConfig();
    $bannerMode = $banner['mode'] ?? 'image';
    $bannerImage = $banner['image'] ?? null;
    if (empty($bannerImage) && ! empty($banner['images'][0] ?? null)) {
        $bannerImage = $banner['images'][0];
    }
    if (empty($bannerImage)) {
        $fallback = trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_LOGIN_HERO_IMAGE, ''));
        if ($fallback !== '' && $bannerMode === 'image_text') {
            $bannerImage = $fallback;
        }
    }
    $bannerImageUrl = $bannerImage;
    if (is_string($bannerImageUrl) && $bannerImageUrl !== '' && ! preg_match('#^https?://#i', $bannerImageUrl)) {
        $bannerImageUrl = asset(ltrim($bannerImageUrl, '/'));
    }
    $bundledSlug = 'student-dashboard-midsem-exams-good-luck-banner';
    $usesBundledBanner = $bannerMode === 'image' && (
        empty($bannerImage)
        || str_contains((string) $bannerImage, $bundledSlug)
    );
    $bundledBase = asset('images/' . $bundledSlug);
    $bannerAlt = 'Dashboard banner';
    $showMobileBanner = ! empty($banner['enabled']) && (
        ($bannerMode === 'image' && ($usesBundledBanner || ! empty($bannerImageUrl)))
        || $bannerMode === 'image_text'
    );
    $promoTitle = $banner['title'] ?? 'Challenge Yourself.';
    $promoAccent = $banner['title_accent'] ?? 'Achieve More.';
    $promoSubtitle = trim($banner['subtitle'] ?? '');
    $displayName = $displayName ?? $student?->first_name ?? 'User';
    $initials = $student?->initials ?? strtoupper(substr(trim($displayName), 0, 1));
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
        <a href="{{ route('dashboard.my-profile') }}" class="md-dash__profile md-dash__profile-link">
            <span class="md-dash__avatar" aria-hidden="true">{{ $initials ?: 'U' }}</span>
            <div class="md-dash__greeting min-w-0">
                <span class="md-dash__hello">{{ $greeting ?? 'Hello' }},</span>
                <span class="md-dash__name truncate">{{ $displayName }}</span>
            </div>
        </a>
        <div class="md-dash__header-actions">
            @if($student ?? null)
            @include('student.partials.dashboard-student-notifications')
            @endif
            <a href="{{ route('dashboard.calendar') }}" class="md-dash__icon-btn" aria-label="Calendar">
                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            </a>
        </div>
    </header>

    @if($showMobileBanner)
    <section class="md-dash__banner" aria-label="Dashboard banner">
        @if($bannerMode === 'image' && ($usesBundledBanner || ! empty($bannerImageUrl)))
        <figure class="md-dash__banner-media">
            @if($usesBundledBanner)
            <picture>
                <source type="image/webp"
                        srcset="{{ $bundledBase }}-640.webp 640w, {{ $bundledBase }}.webp 1024w"
                        sizes="100vw">
                <source type="image/jpeg"
                        srcset="{{ $bundledBase }}-640.jpg 640w, {{ $bundledBase }}.jpg 1024w"
                        sizes="100vw">
                <img src="{{ $bundledBase }}.jpg"
                     alt="{{ $bannerAlt }}"
                     class="md-dash__banner-img"
                     width="999"
                     height="291"
                     loading="eager"
                     decoding="async"
                     fetchpriority="high">
            </picture>
            @else
            <img src="{{ e($bannerImageUrl) }}"
                 alt="{{ $bannerAlt }}"
                 class="md-dash__banner-img"
                 width="999"
                 height="291"
                 loading="eager"
                 decoding="async"
                 fetchpriority="high">
            @endif
        </figure>
        @elseif($bannerMode === 'image_text')
        <div class="md-dash__banner-card">
            @if(! empty($bannerImageUrl))
            <figure class="md-dash__banner-media md-dash__banner-media--compact">
                <img src="{{ e($bannerImageUrl) }}"
                     alt=""
                     class="md-dash__banner-img"
                     loading="eager"
                     decoding="async"
                     referrerpolicy="no-referrer">
            </figure>
            @endif
            <div class="md-dash__banner-copy">
                <h2 class="md-dash__banner-title">
                    {{ $promoTitle }}
                    <span class="md-dash__banner-accent">{{ $promoAccent }}</span>
                </h2>
                @if($promoSubtitle !== '')
                <p class="md-dash__banner-sub">{{ $promoSubtitle }}</p>
                @endif
            </div>
        </div>
        @endif
    </section>
    @endif

    <section class="md-dash__section" aria-label="My quiz">
        <div class="md-dash__section-head">
            <h2 class="md-dash__section-title">My quiz</h2>
            <a href="{{ route('dashboard.my-quizzes') }}" class="md-dash__section-link">See all</a>
        </div>

        <a href="{{ $quizActionHref }}"
           @if($scheduledUpcoming && $quizRulesUrl) data-rules-url="{{ $quizRulesUrl }}" @endif
           class="md-dash__course-card @if($scheduledUpcoming) md-dash__course-card--countdown @elseif($scheduledReady) md-dash__course-card--ready @elseif($scheduledInProgress) md-dash__course-card--active @endif">
            <div class="md-dash__course-top">
                <span class="md-dash__course-icon" aria-hidden="true"><i class="fas fa-book-open"></i></span>
                <div class="md-dash__course-info min-w-0">
                    @if($scheduledQuizCourse)
                    <span class="md-dash__course-label truncate">{{ $scheduledQuizCourse }}</span>
                    @endif
                    <span class="md-dash__course-title">{{ $myQuizTitle }}</span>
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
                <span class="md-dash__tile-hint">History</span>
            </a>
            <a href="{{ route('dashboard.calendar') }}" class="md-dash__tile md-dash__tile--accent">
                <span class="md-dash__tile-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                <span class="md-dash__tile-label">Calendar</span>
                <span class="md-dash__tile-hint">Exams</span>
            </a>
            <a href="{{ route('dashboard.my-profile') }}" class="md-dash__tile">
                <span class="md-dash__tile-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                <span class="md-dash__tile-label">Profile</span>
                <span class="md-dash__tile-hint">Account</span>
            </a>
        </div>
    </section>
</div>

@push('styles')
<style>
    .md-dash {
        display: flex;
        flex-direction: column;
        gap: 1.125rem;
        padding-bottom: 0.25rem;
    }

    @media (max-width: 1023px) {
        body.sd-home-mobile-modern {
            overflow: hidden;
        }

        body.sd-home-mobile-modern #student-dashboard-wrap {
            max-height: 100dvh;
            overflow: hidden;
        }

        body.sd-home-mobile-modern #student-dashboard-wrap > main {
            overflow: hidden;
            padding-top: max(0.75rem, env(safe-area-inset-top));
            padding-bottom: 5.5rem;
        }

        body.sd-home-mobile-modern #student-dashboard-wrap > main > div {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        body.sd-home-mobile-modern .md-dash {
            gap: 0.875rem;
            max-height: calc(100dvh - 7rem - env(safe-area-inset-top));
            overflow: hidden;
        }

        body.sd-home-mobile-modern .md-dash__banner-media {
            aspect-ratio: 999 / 220;
        }

        body.sd-home-mobile-modern .md-dash__tile {
            min-height: 6.25rem;
        }
    }

    .md-dash__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .md-dash__profile-link {
        text-decoration: none;
        color: inherit;
        min-width: 0;
        flex: 1;
        border-radius: 0.875rem;
        transition: background 0.15s ease;
    }

    .md-dash__profile-link:active {
        background: var(--theme-primary-50);
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
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        background: var(--theme-primary-600);
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 800;
        letter-spacing: 0.02em;
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
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.75rem;
        background: var(--theme-surface);
        border: 1px solid var(--theme-border);
        color: var(--theme-text);
        text-decoration: none;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .md-dash__icon-btn:active {
        background: var(--theme-primary-50);
        border-color: var(--theme-primary-200);
    }

    .md-dash__banner-media {
        position: relative;
        margin: 0;
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid var(--theme-border);
        background: var(--theme-surface);
        aspect-ratio: 999 / 291;
    }

    .md-dash__banner-media--compact {
        aspect-ratio: 16 / 9;
        border-radius: 0;
        border: none;
        border-bottom: 1px solid var(--theme-border);
    }

    .md-dash__banner-img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .md-dash__banner-card {
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid var(--theme-border);
        background: var(--theme-surface);
    }

    .md-dash__banner-copy {
        padding: 0.875rem 1rem 1rem;
    }

    .md-dash__banner-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.3;
        letter-spacing: -0.02em;
        color: var(--theme-text);
    }

    .md-dash__banner-accent {
        color: var(--theme-brand-dark);
    }

    .md-dash__banner-sub {
        margin: 0.375rem 0 0;
        font-size: 0.75rem;
        line-height: 1.45;
        color: var(--theme-muted);
    }

    .md-dash__section {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .md-dash__section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .md-dash__section-title {
        margin: 0;
        font-size: 0.9375rem;
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
        padding: 0.9375rem;
        border-radius: 1rem;
        text-decoration: none;
        color: var(--theme-text);
        background: var(--theme-surface);
        border: 1px solid var(--theme-border);
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    .md-dash__course-card:active {
        background: var(--theme-primary-50);
        border-color: var(--theme-primary-200);
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
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.625rem;
        background: var(--theme-primary-50);
        color: var(--theme-primary-600);
        font-size: 0.875rem;
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
        color: var(--theme-muted);
    }

    .md-dash__course-title {
        font-size: 0.875rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .md-dash__course-badge {
        flex-shrink: 0;
        padding: 0.1875rem 0.4375rem;
        border-radius: 9999px;
        font-size: 0.5625rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--theme-primary-700);
        background: var(--theme-primary-50);
        border: 1px solid var(--theme-primary-200);
    }

    .md-dash__progress {
        position: relative;
        height: 0.375rem;
        border-radius: 9999px;
        background: var(--theme-primary-100);
        overflow: hidden;
    }

    .md-dash__progress-fill {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: var(--theme-primary-600);
        transition: width 0.35s ease;
    }

    .md-dash__course-meta {
        margin: 0;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--theme-muted);
        line-height: 1.35;
    }

    .md-dash__course-cta {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 0.125rem;
        padding: 0.625rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        font-variant-numeric: tabular-nums;
    }

    .md-dash__course-cta--start,
    .md-dash__course-cta--continue {
        color: #fff;
        background: var(--theme-primary-600);
        border: 1px solid var(--theme-primary-700);
    }

    .md-dash__course-cta--countdown {
        color: var(--theme-primary-700);
        background: var(--theme-primary-50);
        border: 1px solid var(--theme-primary-200);
    }

    .md-dash__course-cta--muted {
        color: var(--theme-text);
        background: var(--theme-bg);
        border: 1px solid var(--theme-border);
    }

    .md-dash__course-card--countdown.is-ready .md-dash__course-cta--countdown {
        color: #fff;
        background: var(--theme-primary-600);
        border-color: var(--theme-primary-700);
    }

    .md-dash__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
    }

    .md-dash__tile {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0.1875rem;
        min-height: 6.75rem;
        padding: 0.8125rem;
        border-radius: 1rem;
        text-decoration: none;
        color: var(--theme-text);
        background: var(--theme-surface);
        border: 1px solid var(--theme-border);
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    .md-dash__tile:active {
        background: var(--theme-bg);
    }

    .md-dash__tile--primary {
        background: var(--theme-primary-50);
        border-color: var(--theme-primary-200);
    }

    .md-dash__tile--brand {
        background: var(--theme-brand-soft);
        border-color: var(--theme-brand-border);
    }

    .md-dash__tile--accent {
        background: var(--theme-surface);
        border-color: var(--theme-primary-300);
    }

    .md-dash__tile-icon {
        position: absolute;
        top: 0.8125rem;
        right: 0.8125rem;
        font-size: 1.125rem;
        color: var(--theme-primary-500);
        opacity: 0.55;
        pointer-events: none;
    }

    .md-dash__tile--brand .md-dash__tile-icon {
        color: var(--theme-brand-dark);
    }

    .md-dash__tile-value {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1;
        font-variant-numeric: tabular-nums;
        color: var(--theme-primary-700);
    }

    .md-dash__tile-label {
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }

    .md-dash__tile-hint {
        font-size: 0.625rem;
        font-weight: 600;
        color: var(--theme-muted);
    }
</style>
@endpush
