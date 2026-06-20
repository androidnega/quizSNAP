@extends('layouts.app')

@php
    $appName = $appName ?? config('app.name', 'QuizSnap');
    $institutionName = $institutionName ?? null;
    $supportWhatsAppE164 = '233541069241';
    $supportCallE164 = '+233257940791';
    $supportWhatsAppMessage = '[QuizSnap Support | Landing Page] Hi, I need help with: ';
    $supportWhatsAppUrl = 'https://wa.me/' . $supportWhatsAppE164 . '?text=' . rawurlencode($supportWhatsAppMessage);
@endphp

@section('title', $appName)
@section('body_class', 'landing-page qs-landing')

@push('styles')
<style>
    /* Landing page layout — colors come from partials.theme-styles (--qs-* aliases) */
    body.landing-page,
    body.qs-landing {
        background: var(--qs-bg) !important;
        overflow-x: hidden;
    }

    .qs-landing-shell {
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
        font-family: var(--font-sans);
        font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        color: var(--qs-text);
    }

    .qs-container {
        width: 100%;
        max-width: 72rem;
        margin: 0 auto;
        padding-left: max(1rem, env(safe-area-inset-left));
        padding-right: max(1rem, env(safe-area-inset-right));
    }

    /* Header — matches student dashboard amber bar */
    .qs-header {
        flex-shrink: 0;
        background: var(--qs-brand);
        border-bottom: 1px solid rgba(245, 158, 11, 0.35);
    }

    .qs-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 4.5rem;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    @media (min-width: 768px) {
        .qs-header-inner {
            min-height: 5rem;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
    }

    .qs-logo {
        min-width: 0;
    }

    .qs-header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-shrink: 0;
    }

    .qs-btn-get-started {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.625rem 1.25rem;
        border-radius: 0.75rem;
        background: #fff;
        color: var(--qs-text) !important;
        font-family: var(--font-display);
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        text-decoration: none;
        border: none;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
        white-space: nowrap;
    }

    .qs-btn-get-started:hover {
        background: #fffbeb;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    }

    .qs-btn-get-started:active {
        transform: scale(0.98);
    }

    @media (min-width: 640px) {
        .qs-btn-get-started {
            padding: 0.75rem 1.5rem;
            font-size: 0.75rem;
        }
    }

    .qs-btn-outline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.6875rem 1.25rem;
        border-radius: 0.75rem;
        background: #fff;
        color: #334155;
        font-family: var(--font-display);
        font-size: 0.9375rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        text-decoration: none;
        border: 1px solid var(--qs-border);
        transition: border-color 0.15s, background 0.15s;
    }

    .qs-btn-outline:hover {
        border-color: #fcd34d;
        background: #fffbeb;
    }

    .qs-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        padding: 0.5625rem 1.125rem;
        border-radius: 0.625rem;
        background: var(--qs-accent);
        color: #fff !important;
        font-family: var(--font-display);
        font-size: 0.9375rem;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: background 0.15s, transform 0.15s;
    }

    .qs-btn-primary:hover { background: #1d4ed8; }
    .qs-btn-primary:active { transform: scale(0.98); }
    .qs-btn-primary:disabled,
    .qs-btn-primary.btn-cta-disabled {
        background: #cbd5e1 !important;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* Hero */
    .qs-main {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        min-height: 0;
        padding: 1.5rem 0 1rem;
    }

    .qs-hero-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
        align-items: center;
        width: 100%;
    }

    .qs-hero-copy {
        max-width: 34rem;
    }

    .qs-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.875rem;
        border-radius: 9999px;
        background: var(--qs-accent-soft);
        color: var(--qs-brand-deep);
        font-family: var(--font-display);
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        margin-bottom: 1.25rem;
        border: 1px solid rgba(251, 191, 36, 0.35);
    }

    .qs-hero-title {
        font-family: var(--font-display);
        font-size: clamp(2.125rem, 5.5vw, 3.375rem);
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: -0.04em;
        margin: 0 0 1rem;
        color: var(--qs-text);
    }

    .qs-hero-title .accent {
        display: block;
        color: var(--qs-brand-deep);
    }

    .qs-hero-sub {
        font-size: clamp(1rem, 2vw, 1.125rem);
        line-height: 1.65;
        letter-spacing: -0.015em;
        color: var(--qs-muted);
        margin: 0 0 1.5rem;
        max-width: 32rem;
    }

    .qs-hero-sub strong {
        color: #334155;
        font-weight: 600;
    }

    .qs-hero-sub--mobile {
        display: none;
    }

    .qs-cta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }

    .qs-btn-hero {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.8125rem 1.375rem;
        border-radius: 0.75rem;
        background: var(--qs-brand);
        color: var(--qs-text) !important;
        font-family: var(--font-display);
        font-size: 0.9375rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        text-decoration: none;
        border: none;
        cursor: pointer;
        box-shadow: 0 8px 24px -10px rgba(245, 158, 11, 0.65);
        transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
    }

    .qs-btn-hero:hover {
        transform: translateY(-1px);
        background: var(--qs-brand-dark);
        box-shadow: 0 12px 28px -10px rgba(245, 158, 11, 0.75);
    }

    .qs-token-block {
        margin-top: 0.25rem;
    }

    .qs-token-row {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-width: 26rem;
    }

    @media (min-width: 480px) {
        .qs-token-row {
            flex-direction: row;
            align-items: stretch;
        }
        .qs-token-row .qs-input {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            flex: 1;
        }
        .qs-token-row .qs-btn-primary {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            white-space: nowrap;
            padding-left: 1.25rem;
            padding-right: 1.25rem;
        }
    }

    .qs-input {
        width: 100%;
        min-height: 2.75rem;
        padding: 0.625rem 0.875rem;
        border: 1px solid var(--qs-border);
        border-radius: 0.75rem;
        background: #fff;
        font-size: 0.9375rem;
        color: var(--qs-text);
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        -webkit-user-select: text;
        user-select: text;
    }

    .qs-input:focus {
        border-color: var(--qs-brand-dark);
        box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.28);
    }

    .qs-input.token-valid {
        border-color: #22c55e;
        background: #f0fdf4;
        color: #15803d;
    }

    .qs-input.token-invalid {
        border-color: #ef4444;
        background: #fef2f2;
        color: #dc2626;
    }

    .qs-input.token-loading {
        border-color: #f59e0b;
        background: #fffbeb;
        color: #d97706;
    }

    .qs-token-msg {
        min-height: 1.125rem;
        font-size: 0.8125rem;
        font-weight: 500;
        margin-top: 0.375rem;
    }

    .qs-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1.25rem;
    }

    .qs-hero-actions--row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.625rem;
        margin-top: 1.25rem;
        max-width: 32rem;
    }

    .qs-hero-actions--row .qs-btn-hero,
    .qs-hero-actions--row .qs-btn-hero-secondary,
    .qs-hero-actions--row .qs-btn-outline {
        min-height: 2.75rem;
        padding: 0.625rem 0.75rem;
        font-size: 0.8125rem;
        justify-content: center;
        text-align: center;
        width: 100%;
    }

    .qs-hero-actions--row .qs-btn-hero svg {
        display: none;
    }

    @media (min-width: 480px) {
        .qs-hero-actions--row {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.75rem;
            max-width: none;
        }
        .qs-hero-actions--row .qs-btn-hero,
        .qs-hero-actions--row .qs-btn-hero-secondary,
        .qs-hero-actions--row .qs-btn-outline {
            width: auto;
            flex: 1 1 0;
            padding: 0.6875rem 1.125rem;
            font-size: 0.875rem;
        }
        .qs-hero-actions--row .qs-btn-hero svg {
            display: inline;
        }
    }

    .qs-hero-actions .qs-btn-outline,
    .qs-hero-actions .qs-btn-hero-secondary {
        min-height: 2.75rem;
        padding: 0.6875rem 1.25rem;
        font-size: 0.9375rem;
    }

    .qs-btn-hero-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.6875rem 1.25rem;
        border-radius: 0.75rem;
        background: var(--qs-text);
        color: #fff !important;
        font-family: var(--font-display);
        font-size: 0.9375rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        text-decoration: none;
        border: none;
        transition: background 0.15s, transform 0.15s;
    }

    .qs-btn-hero-secondary:hover {
        background: #1e293b;
    }

    .qs-btn-hero-secondary:active {
        transform: scale(0.98);
    }

    /* Hero photo */
    .qs-hero-visual {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 16rem;
        padding: 0;
    }

    .qs-blob {
        position: absolute;
        border-radius: 9999px;
        filter: blur(56px);
        opacity: 0.55;
        pointer-events: none;
    }

    .qs-blob-1 {
        width: 18rem;
        height: 18rem;
        background: #fde68a;
        top: 4%;
        right: -2%;
    }

    .qs-blob-2 {
        width: 14rem;
        height: 14rem;
        background: #fef3c7;
        bottom: -4%;
        left: 0;
    }

    .qs-hero-photo {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 36rem;
        margin: 0;
        border-radius: 1.375rem;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.95);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 0 0 1px rgba(255, 255, 255, 0.6) inset,
            0 24px 48px -20px rgba(245, 158, 11, 0.28);
        transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .qs-hero-photo::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        pointer-events: none;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
    }

    .qs-hero-photo img {
        display: block;
        width: 100%;
        height: auto;
        aspect-ratio: 720 / 479;
        object-fit: cover;
        vertical-align: middle;
    }

    @media (min-width: 768px) {
        .qs-hero-visual {
            justify-content: flex-end;
            align-self: stretch;
            min-height: 22rem;
        }

        .qs-hero-photo {
            max-width: none;
            width: 100%;
        }

        .qs-hero-photo:hover {
            transform: translateY(-4px);
            box-shadow:
                0 2px 4px rgba(15, 23, 42, 0.05),
                0 0 0 1px rgba(255, 255, 255, 0.6) inset,
                0 32px 64px -22px rgba(245, 158, 11, 0.34);
        }
    }

    @media (min-width: 768px) {
        .qs-hero-grid {
            grid-template-columns: 0.92fr 1.08fr;
            gap: 2.75rem;
            align-items: center;
        }
        .qs-main { padding: 2rem 0 1.5rem; }
    }

    @media (max-width: 767px) {
        .qs-landing-shell {
            min-height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
        }
        body.landing-page { overflow: hidden; }
        .qs-main {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            align-items: flex-start;
            padding-top: 0;
        }
        .qs-hero-grid {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .qs-hero-copy {
            display: contents;
        }
        .qs-hero-visual {
            order: 1;
            min-height: 0;
            align-self: stretch;
            margin: 0 calc(-1 * max(1rem, env(safe-area-inset-right))) 1.25rem calc(-1 * max(1rem, env(safe-area-inset-left)));
            width: auto;
            max-width: none;
        }
        .qs-hero-head {
            order: 2;
        }
        .qs-hero-copy > :not(.qs-hero-head) {
            order: 3;
        }
        .qs-hero-sub--desktop {
            display: none;
        }
        .qs-hero-sub--mobile {
            display: block;
            font-size: 0.9375rem;
            line-height: 1.55;
            margin-bottom: 1rem;
        }
        .qs-hero-title {
            margin-bottom: 0.625rem;
        }
        .qs-badge {
            margin-bottom: 0.875rem;
        }
        .qs-hero-photo {
            width: 100%;
            max-width: none;
            border-radius: 0 0 1.25rem 1.25rem;
            border-top: none;
            box-shadow:
                0 16px 40px -18px rgba(15, 23, 42, 0.14),
                0 0 0 1px rgba(226, 232, 240, 0.7) inset;
        }
        .qs-hero-photo img {
            aspect-ratio: 720 / 479;
            max-height: none;
            min-height: 12.5rem;
            object-fit: cover;
        }
        .qs-hero-photo--banner img {
            aspect-ratio: 21 / 9;
            min-height: 10rem;
        }
        .qs-blob {
            opacity: 0.35;
        }
        .qs-blob-1 {
            width: 12rem;
            height: 12rem;
            top: auto;
            bottom: 0;
            right: 8%;
        }
        .qs-blob-2 {
            width: 9rem;
            height: 9rem;
            left: 6%;
        }
    }

    /* Support FAB */
    .qs-support-fab {
        position: fixed;
        right: max(1rem, env(safe-area-inset-right));
        bottom: max(1rem, env(safe-area-inset-bottom));
        z-index: 80;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .qs-support-menu {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.625rem;
        opacity: 0;
        visibility: hidden;
        transform: translateY(0.5rem) scale(0.96);
        transform-origin: bottom right;
        transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s;
        pointer-events: none;
    }

    .qs-support-fab.is-open .qs-support-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .qs-support-action {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.625rem 0.875rem 0.625rem 0.625rem;
        border-radius: 9999px;
        background: #fff;
        color: #0f172a;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 10px 30px -12px rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(226, 232, 240, 0.95);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        white-space: nowrap;
    }

    .qs-support-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 34px -12px rgba(15, 23, 42, 0.4);
    }

    .qs-support-action-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 9999px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        color: #fff;
    }

    .qs-support-action-icon svg {
        width: 1.125rem;
        height: 1.125rem;
    }

    .qs-support-action--whatsapp .qs-support-action-icon {
        background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    }

    .qs-support-action--call .qs-support-action-icon {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .qs-support-toggle {
        position: relative;
        width: 3.625rem;
        height: 3.625rem;
        border: none;
        border-radius: 9999px;
        cursor: pointer;
        color: var(--qs-text);
        background: var(--qs-brand);
        box-shadow: 0 16px 36px -14px rgba(245, 158, 11, 0.55);
        display: grid;
        place-items: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .qs-support-toggle:hover {
        transform: translateY(-2px);
        background: var(--qs-brand-dark);
        box-shadow: 0 20px 40px -14px rgba(245, 158, 11, 0.65);
    }

    .qs-support-toggle:active {
        transform: scale(0.96);
    }

    .qs-support-toggle svg {
        width: 1.375rem;
        height: 1.375rem;
        transition: transform 0.22s ease, opacity 0.22s ease;
    }

    .qs-support-toggle .qs-support-icon-close {
        position: absolute;
        opacity: 0;
        transform: rotate(-90deg) scale(0.8);
    }

    .qs-support-fab.is-open .qs-support-toggle .qs-support-icon-open {
        opacity: 0;
        transform: rotate(90deg) scale(0.8);
    }

    .qs-support-fab.is-open .qs-support-toggle .qs-support-icon-close {
        opacity: 1;
        transform: rotate(0) scale(1);
    }

    .qs-support-backdrop {
        position: fixed;
        inset: 0;
        z-index: 75;
        background: transparent;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s;
    }

    .qs-support-fab-wrap.is-open .qs-support-backdrop {
        opacity: 1;
        visibility: visible;
    }

    @media (min-width: 768px) {
        .qs-support-fab {
            right: max(1.5rem, env(safe-area-inset-right));
            bottom: max(1.5rem, env(safe-area-inset-bottom));
        }

        .qs-support-toggle {
            width: 4rem;
            height: 4rem;
        }
    }
</style>
@endpush

@section('content')
@php $mobileHeroImage = trim($landingHeroImage ?? ''); @endphp
<div class="qs-landing-shell">
    @include('student.partials.marketing-header', [
        'appName' => $appName,
        'student' => $student ?? null,
        'showGetStarted' => $landingShowQuizToken ?? false,
    ])

    <main class="qs-main">
        <div class="qs-container qs-hero-grid">
            <div class="qs-hero-copy">
                <div class="qs-hero-head">
                    <span class="qs-badge">Secure · Proctored · Reliable</span>

                    <h1 class="qs-hero-title">
                        Secure online assessments.
                        <span class="accent">Built for your institution.</span>
                    </h1>

                    <p class="qs-hero-sub qs-hero-sub--desktop">
                    @if($institutionName)
                        <strong>{{ $institutionName }}</strong> uses {{ $appName }} for proctored quizzes and student dashboards.
                    @else
                        {{ $appName }} is a secure assessment platform for proctored quizzes and student dashboards.
                    @endif
                    Enter your quiz token from your lecturer to begin.
                    </p>

                    <p class="qs-hero-sub qs-hero-sub--mobile">
                    @if($institutionName)
                        <strong>{{ $institutionName }}</strong> uses {{ $appName }} for secure, proctored assessments.
                    @else
                        Secure proctored quizzes and student dashboards — enter your token to begin.
                    @endif
                    </p>
                </div>

                @if(($landingShowQuizToken ?? false) || (isset($student) && $student))
                @if($landingShowQuizToken ?? false)
                <div class="qs-cta-row">
                        <button type="button" class="qs-btn-hero" id="scroll-to-token-btn">
                            Start quiz
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                </div>
                @elseif(isset($student) && $student)
                <div class="qs-hero-actions qs-hero-actions--row">
                    <a href="{{ route('dashboard.my-quizzes') }}" class="qs-btn-hero">
                        My quizzes
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="{{ route('dashboard') }}" class="qs-btn-hero-secondary">Dashboard</a>
                    <a href="{{ route('about-system') }}" class="qs-btn-outline">About us</a>
                </div>
                @endif
                @endif

                @if($landingShowQuizToken ?? false)
                    <div class="qs-token-block" id="quiz-token-section">
                        <form action="{{ route('student.start-quiz') }}" method="post" id="start-quiz-form">
                            @csrf
                            <div class="qs-token-row">
                                <label for="quiz-token" class="sr-only">Quiz token</label>
                                <input type="text" id="quiz-token" name="link" placeholder="Enter quiz token (e.g. KTdie54-3Sx9)" required autocomplete="off" class="qs-input">
                                <button type="submit" id="start-quiz-btn" disabled class="qs-btn-primary btn-cta-disabled">Start quiz</button>
                            </div>
                            <div id="token-message" class="qs-token-msg"></div>
                            @error('link')
                                <div class="qs-token-msg" style="color:#dc2626;">{{ $message }}</div>
                            @enderror
                        </form>
                    </div>
                @endif

                @if(!isset($student) || !$student)
                <div class="qs-hero-actions">
                    <a href="{{ route('student.account.login.form') }}" class="qs-btn-hero-secondary">Student login</a>
                    <a href="{{ route('about-system') }}" class="qs-btn-outline">About us</a>
                </div>
                @endif
            </div>

            <div class="qs-hero-visual">
                <div class="qs-blob qs-blob-1" aria-hidden="true"></div>
                <div class="qs-blob qs-blob-2" aria-hidden="true"></div>
                <figure class="qs-hero-photo @if($mobileHeroImage !== '') qs-hero-photo--banner @endif">
                    <picture>
                        <source media="(max-width: 767px)" srcset="{{ e($mobileHeroImage !== '' ? $mobileHeroImage : asset('images/landing/hero-student-mobile.webp')) }}">
                        <img
                            src="{{ asset('images/landing/hero-student.webp') }}"
                            alt="Student using {{ $appName }} for online assessments"
                            width="720"
                            height="479"
                            loading="eager"
                            decoding="async"
                            fetchpriority="high">
                    </picture>
                </figure>
            </div>
        </div>
    </main>

    @include('student.partials.support-fab', [
        'supportWhatsAppUrl' => $supportWhatsAppUrl,
        'supportCallE164' => $supportCallE164,
    ])
</div>
@endsection

@push('scripts')
@include('student.partials.marketing-support-scripts')
<script>
(function() {
    function scrollToToken() {
        var tokenSection = document.getElementById('quiz-token-section');
        var tokenInput = document.getElementById('quiz-token');
        if (tokenSection) {
            tokenSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            if (tokenInput) setTimeout(function() { tokenInput.focus(); }, 300);
        }
    }

    var scrollBtn = document.getElementById('scroll-to-token-btn');
    var headerBtn = document.getElementById('header-get-started-btn');
    if (scrollBtn) scrollBtn.addEventListener('click', scrollToToken);
    if (headerBtn) headerBtn.addEventListener('click', scrollToToken);
})();
</script>
@if($landingShowQuizToken ?? false)
<script>
(function() {
    var DEBOUNCE_MS = 350;
    var input = document.getElementById('quiz-token');
    var messageEl = document.getElementById('token-message');
    var form = document.getElementById('start-quiz-form');
    var validateUrl = '{{ route("student.validate-token") }}';
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var debounceTimer = null;
    var lastToken = '';
    var btn = document.getElementById('start-quiz-btn');

    function setButtonState(enable) {
        if (!btn) return;
        btn.disabled = !enable;
        btn.classList.toggle('btn-cta-disabled', !enable);
    }

    function setState(klass, text) {
        if (!input) return;
        input.classList.remove('token-valid', 'token-invalid', 'token-loading');
        if (klass) input.classList.add(klass);
        if (messageEl) {
            messageEl.textContent = text || '';
            messageEl.style.color = klass === 'token-valid' ? '#15803d' : klass === 'token-invalid' ? '#dc2626' : klass === 'token-loading' ? '#d97706' : '';
        }
        setButtonState(klass === 'token-valid');
    }

    function runValidation(tokenValue) {
        if (!tokenValue || tokenValue.length < 8) {
            setState('token-invalid', 'Please enter a valid quiz token.');
            setButtonState(false);
            return;
        }
        setState('token-loading', 'Checking…');
        setButtonState(false);
        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('token', tokenValue);
        fetch(validateUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setState(data.valid ? 'token-valid' : 'token-invalid', data.valid ? 'Valid token — you can start.' : (data.message || 'Invalid token.'));
            })
            .catch(function() {
                setState('token-invalid', 'Could not validate. Try again.');
            });
    }

    function onTokenInput() {
        var raw = (input && input.value) ? input.value.trim() : '';
        if (debounceTimer) clearTimeout(debounceTimer);
        if (!raw || raw.length < 8) {
            setState('', '');
            setButtonState(false);
            lastToken = '';
            return;
        }
        lastToken = raw;
        debounceTimer = setTimeout(function() {
            debounceTimer = null;
            runValidation(raw);
        }, DEBOUNCE_MS);
    }

    if (input) {
        input.addEventListener('input', onTokenInput);
        input.addEventListener('paste', function() {
            setTimeout(function() {
                var raw = (input && input.value) ? input.value.trim() : '';
                if (raw.length >= 8) {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = null;
                    runValidation(raw);
                } else {
                    onTokenInput();
                }
            }, 50);
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!input.classList.contains('token-valid')) {
                e.preventDefault();
                var raw = (input && input.value) ? input.value.trim() : '';
                if (raw.length >= 8) {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = null;
                    runValidation(raw);
                } else {
                    setState('token-invalid', 'Please enter a valid quiz token (e.g. KTdie54-3Sx9).');
                    setButtonState(false);
                }
                return false;
            }
        });
    }
})();
</script>
@endif
@endpush
