@extends('layouts.app')

@section('title', 'Student Login')
@section('body_class', 'sal-body')

@php
    $universalOtpConfigured = \App\Services\StudentUniversalOtp::isConfigured();
    $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $salInput = 'sal-input';
    $salPassword = 'sal-input sal-input--password';
@endphp

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --sal-ink: #111318;
        --sal-muted: #6b7280;
        --sal-line: rgba(17, 19, 24, 0.1);
        --sal-yellow: #ffd500;
        --sal-yellow-deep: #e6bf00;
        --sal-surface: #ffffff;
    }
    .sal-body {
        background: #ffffff !important;
        font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
        color: var(--sal-ink);
    }
    .sal-page {
        position: relative;
        min-height: 100dvh;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: max(1.25rem, env(safe-area-inset-top)) 1.25rem max(1.5rem, env(safe-area-inset-bottom));
        overflow: hidden;
        background: #ffffff;
    }
    .sal-atmosphere {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            radial-gradient(70% 55% at 50% -5%, rgba(255, 213, 0, 0.28), transparent 62%),
            radial-gradient(42% 32% at 92% 88%, rgba(255, 213, 0, 0.14), transparent 70%),
            #ffffff;
    }
    .sal-orb {
        position: absolute;
        width: 18rem;
        height: 18rem;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 213, 0, 0.32) 0%, rgba(255, 213, 0, 0) 72%);
        filter: blur(2px);
        top: -5rem;
        left: 50%;
        transform: translateX(-50%);
        animation: sal-float 10s ease-in-out infinite;
    }
    @keyframes sal-float {
        0%, 100% { transform: translateX(-50%) translateY(0); opacity: 0.9; }
        50% { transform: translateX(-50%) translateY(1rem); opacity: 1; }
    }
    .sal-shell {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 24.5rem;
        animation: sal-rise 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        transition: opacity 0.35s ease, transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), filter 0.35s ease;
    }
    @keyframes sal-rise {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .sal-brand {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 1.5rem;
    }
    .sal-brand-mark {
        width: 2.65rem;
        height: 2.65rem;
        border-radius: 0.8rem;
        overflow: hidden;
        flex-shrink: 0;
        background: var(--sal-yellow);
        box-shadow: 0 8px 22px rgba(255, 213, 0, 0.35);
    }
    .sal-brand-mark img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .sal-brand-name {
        font-family: 'Sora', ui-sans-serif, system-ui, sans-serif;
        font-size: clamp(1.7rem, 6vw, 2rem);
        font-weight: 700;
        letter-spacing: -0.04em;
        line-height: 1;
        color: var(--sal-ink);
        margin: 0;
    }
    .sal-panel {
        background: var(--sal-surface);
        border: 1px solid rgba(17, 19, 24, 0.06);
        border-radius: 1.25rem;
        padding: 1.35rem 1.25rem 1.2rem;
        box-shadow: 0 18px 40px rgba(17, 19, 24, 0.05);
        position: relative;
    }
    .sal-step {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .sal-step.sal-step--enter {
        animation: sal-step-in 0.34s cubic-bezier(0.22, 1, 0.36, 1);
    }
    @keyframes sal-step-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .sal-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #9a8300;
    }
    .sal-kicker::before {
        content: '';
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 999px;
        background: var(--sal-yellow);
        box-shadow: 0 0 0 3px rgba(255, 213, 0, 0.25);
    }
    .sal-title {
        font-family: 'Sora', ui-sans-serif, system-ui, sans-serif;
        font-size: 1.3rem;
        font-weight: 650;
        letter-spacing: -0.03em;
        line-height: 1.2;
        color: var(--sal-ink);
        margin: 0;
    }
    .sal-copy {
        margin: 0;
        font-size: 0.92rem;
        line-height: 1.45;
        color: var(--sal-muted);
    }
    .sal-field {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .sal-label {
        font-size: 0.76rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        color: #374151;
    }
    .sal-input {
        width: 100%;
        border: 1px solid var(--sal-line);
        background: #fff;
        color: var(--sal-ink);
        border-radius: 0.85rem;
        padding: 0.88rem 1rem;
        font-size: 1rem;
        font-weight: 500;
        outline: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }
    .sal-input--password { padding-right: 2.85rem; }
    .sal-input::placeholder { color: #9ca3af; font-weight: 450; }
    .sal-input:focus {
        border-color: rgba(230, 191, 0, 0.9);
        box-shadow: 0 0 0 4px rgba(255, 213, 0, 0.22);
    }
    .sal-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 3rem;
        border: none;
        border-radius: 0.9rem;
        padding: 0.85rem 1rem;
        font-family: 'Sora', ui-sans-serif, system-ui, sans-serif;
        font-size: 0.95rem;
        font-weight: 650;
        letter-spacing: -0.01em;
        cursor: pointer;
        transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    }
    .sal-btn:active { transform: scale(0.985); }
    .sal-btn--primary {
        background: var(--sal-yellow);
        color: var(--sal-ink);
        box-shadow: 0 10px 24px rgba(255, 213, 0, 0.26);
    }
    .sal-btn--primary:hover { background: var(--sal-yellow-deep); }
    .sal-btn--ghost {
        background: transparent;
        color: var(--sal-muted);
        font-weight: 600;
        font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
        min-height: 2.55rem;
    }
    .sal-btn--ghost:hover { color: var(--sal-ink); background: rgba(17, 19, 24, 0.04); }
    .sal-btn--quiet {
        background: rgba(17, 19, 24, 0.04);
        color: var(--sal-ink);
        font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
        font-weight: 600;
    }
    .sal-btn--quiet:hover { background: rgba(17, 19, 24, 0.07); }
    .sal-link {
        color: var(--sal-ink);
        font-weight: 650;
        text-decoration: none;
        border-bottom: 1px solid rgba(17, 19, 24, 0.2);
    }
    .sal-link:hover { border-bottom-color: var(--sal-ink); }
    .sal-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 0.1rem;
    }
    .sal-error {
        border-radius: 0.8rem;
        border: 1px solid #fecaca;
        background: #fff5f5;
        color: #991b1b;
        padding: 0.75rem 0.9rem;
        font-size: 0.875rem;
        line-height: 1.4;
    }
    .sal-hint {
        border-radius: 0.8rem;
        border: 1px solid rgba(230, 191, 0, 0.4);
        background: rgba(255, 213, 0, 0.1);
        color: #5c4d00;
        padding: 0.8rem 0.9rem;
        font-size: 0.875rem;
        line-height: 1.4;
    }
    .sal-hint strong { color: var(--sal-ink); }
    .sal-note {
        border-radius: 0.8rem;
        border: 1px solid var(--sal-line);
        background: #fafafa;
        color: var(--sal-muted);
        padding: 0.75rem 0.85rem;
        font-size: 0.8rem;
        line-height: 1.4;
    }
    .sal-otp {
        display: flex;
        justify-content: space-between;
        gap: 0.4rem;
    }
    .sal-otp .otp-digit {
        width: 2.65rem;
        height: 3.05rem;
        text-align: center;
        font-family: 'Sora', ui-sans-serif, system-ui, sans-serif;
        font-size: 1.2rem;
        font-weight: 650;
        border: 1px solid var(--sal-line);
        border-radius: 0.75rem;
        background: #fff;
        color: var(--sal-ink);
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .sal-otp .otp-digit:focus {
        border-color: rgba(230, 191, 0, 0.9);
        box-shadow: 0 0 0 4px rgba(255, 213, 0, 0.22);
    }
    .sal-panel [data-password-toggle] { color: #7a8190 !important; }
    .sal-panel [data-password-toggle]:hover { color: var(--sal-ink) !important; }
    .sal-stack {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .sal-stack.hidden,
    .sal-step.hidden { display: none !important; }

    /* Full-page auth animation */
    .sal-auth-overlay {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        background: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(14px) saturate(1.1);
        -webkit-backdrop-filter: blur(14px) saturate(1.1);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.35s ease;
    }
    .sal-auth-overlay.is-visible {
        opacity: 1;
        pointer-events: auto;
    }
    .sal-auth-overlay[hidden] { display: none !important; }
    .sal-auth-overlay::before {
        content: '';
        position: absolute;
        width: 22rem;
        height: 22rem;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 213, 0, 0.28) 0%, transparent 68%);
        animation: sal-auth-glow 2.4s ease-in-out infinite;
    }
    @keyframes sal-auth-glow {
        0%, 100% { transform: scale(0.92); opacity: 0.7; }
        50% { transform: scale(1.08); opacity: 1; }
    }
    .sal-auth-card {
        position: relative;
        width: min(100%, 17rem);
        text-align: center;
        padding: 1.6rem 1.2rem 1.35rem;
        border-radius: 1.35rem;
        background: #fff;
        border: 1px solid rgba(17, 19, 24, 0.06);
        box-shadow: 0 24px 60px rgba(17, 19, 24, 0.1);
        transform: translateY(16px) scale(0.96);
        opacity: 0;
        transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.35s ease;
    }
    .sal-auth-overlay.is-visible .sal-auth-card {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    .sal-auth-mark {
        width: 2.4rem;
        height: 2.4rem;
        margin: 0 auto 1.05rem;
        border-radius: 0.7rem;
        overflow: hidden;
        background: var(--sal-yellow);
        box-shadow: 0 8px 20px rgba(255, 213, 0, 0.35);
    }
    .sal-auth-mark img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .sal-auth-ring {
        position: relative;
        width: 3.4rem;
        height: 3.4rem;
        margin: 0 auto 1rem;
    }
    .sal-auth-ring svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    .sal-auth-ring-track {
        fill: none;
        stroke: rgba(17, 19, 24, 0.08);
        stroke-width: 3.5;
    }
    .sal-auth-ring-arc {
        fill: none;
        stroke: var(--sal-yellow-deep);
        stroke-width: 3.5;
        stroke-linecap: round;
        stroke-dasharray: 88;
        stroke-dashoffset: 62;
        animation: sal-ring-spin 1s linear infinite;
        transform-origin: 26px 26px;
    }
    @keyframes sal-ring-spin {
        to { transform: rotate(360deg); }
    }
    .sal-auth-check {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        opacity: 0;
        transform: scale(0.7);
        transition: opacity 0.25s ease, transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .sal-auth-check-svg {
        width: 1.55rem;
        height: 1.55rem;
    }
    .sal-auth-check-svg path {
        fill: none;
        stroke: #16a34a;
        stroke-width: 3.4;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 36;
        stroke-dashoffset: 36;
    }
    .sal-auth-overlay.is-success .sal-auth-ring-arc {
        animation: none;
        stroke: #16a34a;
        stroke-dashoffset: 0;
        transition: stroke 0.25s ease, stroke-dashoffset 0.45s ease;
    }
    .sal-auth-overlay.is-success .sal-auth-check {
        opacity: 1;
        transform: scale(1);
    }
    .sal-auth-overlay.is-success .sal-auth-check-svg path {
        animation: sal-check-draw 0.42s ease forwards 0.08s;
    }
    @keyframes sal-check-draw {
        to { stroke-dashoffset: 0; }
    }
    .sal-auth-title {
        margin: 0;
        font-family: 'Sora', ui-sans-serif, system-ui, sans-serif;
        font-size: 1.08rem;
        font-weight: 650;
        letter-spacing: -0.025em;
        color: var(--sal-ink);
    }
    .sal-auth-sub {
        margin: 0.4rem 0 0;
        font-size: 0.86rem;
        color: var(--sal-muted);
        min-height: 1.25rem;
    }
    .sal-auth-dots {
        display: flex;
        justify-content: center;
        gap: 0.35rem;
        margin-top: 1rem;
    }
    .sal-auth-dots span {
        width: 0.35rem;
        height: 0.35rem;
        border-radius: 999px;
        background: rgba(17, 19, 24, 0.18);
        animation: sal-dot 1.1s ease-in-out infinite;
    }
    .sal-auth-dots span:nth-child(2) { animation-delay: 0.15s; }
    .sal-auth-dots span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes sal-dot {
        0%, 80%, 100% { transform: translateY(0); background: rgba(17, 19, 24, 0.16); }
        40% { transform: translateY(-3px); background: var(--sal-yellow-deep); }
    }
    .sal-auth-overlay.is-success .sal-auth-dots { display: none; }
    .sal-shell.is-auth-busy {
        opacity: 0.28;
        filter: blur(2px);
        pointer-events: none;
        transform: scale(0.985);
    }
    .sal-shell.is-auth-success {
        opacity: 0;
        transform: translateY(-10px) scale(0.97);
        filter: blur(3px);
    }
    @media (max-width: 420px) {
        .sal-panel { padding: 1.15rem 1.05rem 1.05rem; border-radius: 1.1rem; }
        .sal-otp .otp-digit { width: 2.35rem; height: 2.85rem; font-size: 1.08rem; }
        .sal-brand-name { font-size: 1.55rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sal-shell, .sal-orb, .sal-step--enter,
        .sal-auth-ring-arc, .sal-auth-dots span, .sal-auth-check-svg path,
        .sal-auth-overlay::before { animation: none !important; }
        .sal-auth-overlay, .sal-auth-card, .sal-shell { transition: none !important; }
    }
</style>
@endpush

@section('content')
<div class="sal-page">
    <div class="sal-atmosphere" aria-hidden="true"></div>
    <div class="sal-orb" aria-hidden="true"></div>

    <div class="sal-shell">
        <header class="sal-brand">
            <div class="sal-brand-mark">
                <img src="{{ \App\Support\BrandAssets::markUrl() }}" alt="" width="42" height="42" decoding="async">
            </div>
            <h1 class="sal-brand-name">{{ $appName }}</h1>
        </header>

        <div class="sal-panel">
            {{-- Step 1: Index number --}}
            <div id="step-index" class="sal-step">
                <div>
                    <p class="sal-kicker">Welcome</p>
                    <h2 class="sal-title" style="margin-top:0.35rem;">Sign in</h2>
                    <p class="sal-copy" style="margin-top:0.45rem;">
                        @if(!empty($password_login_enabled))
                            Enter your index number to continue.
                        @else
                            Use your index and phone to get a one-time SMS code.
                        @endif
                    </p>
                </div>
                <div class="sal-field">
                    <label for="index_number" class="sal-label">Index number</label>
                    <input type="text" id="index_number" name="index_number" required placeholder="e.g. BC/ITS/24/047" class="{{ $salInput }}" style="text-transform: uppercase;" autocomplete="off">
                </div>
                <div id="index-error" class="hidden">
                    <div class="sal-error" id="index-error-text"></div>
                    <div id="index-error-index-guidance" class="hidden sal-hint" style="margin-top:0.65rem;">
                        <p class="font-semibold mb-1">Contact your class rep or lecturer</p>
                        <p>Your index is not on the class list yet. Ask your <strong>class rep</strong> or <strong>lecturer</strong> to add you.</p>
                    </div>
                    @if(\App\Support\LiveSupportAccess::isEnabled())
                    <p id="index-error-support-wrap" class="hidden" style="margin-top:0.65rem;font-size:0.875rem;color:var(--sal-muted);">
                        Need technical help?
                        <button type="button" id="index-error-live-support" class="sal-link" style="border:0;background:none;padding:0;cursor:pointer;">Open live chat</button>
                    </p>
                    @endif
                </div>
                <div class="sal-actions">
                    <button type="button" id="btn-index" class="sal-btn sal-btn--primary">Continue</button>
                </div>
            </div>

            {{-- Email --}}
            <div id="step-email" class="sal-step hidden">
                <div>
                    <p class="sal-kicker">Almost done</p>
                    <h2 class="sal-title" style="margin-top:0.35rem;">Add your email</h2>
                    <p class="sal-copy" style="margin-top:0.45rem;" id="email-step-message">Enter your email address for account recovery and notifications.</p>
                </div>
                <div class="sal-field">
                    <label for="student_email" class="sal-label">Email address</label>
                    <input type="email" id="student_email" name="email" placeholder="you@example.com" class="{{ $salInput }}" autocomplete="email">
                </div>
                <div id="email-error" class="hidden">
                    <div class="sal-error" id="email-error-text"></div>
                </div>
                <div class="sal-actions">
                    <button type="button" id="btn-save-email" class="sal-btn sal-btn--primary">Continue</button>
                    <button type="button" id="btn-back-email-to-index" class="sal-btn sal-btn--ghost">Back</button>
                </div>
            </div>

            @if(!empty($password_login_enabled))
            <div id="step-password" class="sal-step hidden">
                <div>
                    <p class="sal-kicker">Welcome back</p>
                    <h2 class="sal-title" style="margin-top:0.35rem;">Enter password</h2>
                    <p class="sal-copy" style="margin-top:0.45rem;" id="password-step-message">Enter your password.</p>
                </div>
                <div class="sal-field">
                    <label for="login_password" class="sal-label">Password</label>
                    @include('student.partials.password-input', ['id' => 'login_password', 'name' => 'login_password', 'autocomplete' => 'current-password', 'class' => $salPassword])
                </div>
                <div id="password-error" class="hidden">
                    <div class="sal-error" id="password-error-text"></div>
                </div>
                <div class="sal-actions">
                    <button type="button" id="btn-verify-password" class="sal-btn sal-btn--primary">Sign in</button>
                    @if(!empty($password_reset_enabled))
                    <p style="text-align:center;margin:0.15rem 0 0;font-size:0.875rem;">
                        <a href="{{ route('student.password.forgot') }}" class="sal-link">Forgot password?</a>
                    </p>
                    @endif
                    @if(!empty($otp_return_login_enabled))
                    <button type="button" id="btn-password-use-sms" class="sal-btn sal-btn--quiet">Get a code by SMS instead</button>
                    @endif
                    <button type="button" id="btn-back-password-to-index" class="sal-btn sal-btn--ghost">Back</button>
                </div>
            </div>
            @endif

            <div id="step-phone" class="sal-step hidden">
                <div>
                    <p class="sal-kicker">Verify</p>
                    <h2 class="sal-title" style="margin-top:0.35rem;">Your phone number</h2>
                    <p class="sal-copy" style="margin-top:0.45rem;" id="phone-step-message">Enter your active phone number. We'll send a one-time SMS code.</p>
                </div>
                <div class="sal-field">
                    <label for="phone" class="sal-label">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="233XXXXXXXXX" class="{{ $salInput }}" autocomplete="tel">
                </div>
                <div id="phone-error" class="hidden">
                    <div class="sal-error" id="phone-error-text"></div>
                </div>
                <div class="sal-actions">
                    <button type="button" id="btn-send-otp" class="sal-btn sal-btn--primary">Send code</button>
                    <button type="button" id="btn-back-to-index" class="sal-btn sal-btn--ghost">Back</button>
                </div>
            </div>

            @if(!empty($password_login_enabled))
            <div id="step-setup-password" class="sal-step hidden">
                <div>
                    <p class="sal-kicker">Secure account</p>
                    <h2 class="sal-title" style="margin-top:0.35rem;">Create a password</h2>
                    <p class="sal-copy" style="margin-top:0.45rem;" id="setup-password-message">Phone verified. Create a password for your account.</p>
                </div>
                <div class="sal-field">
                    <label for="setup_password" class="sal-label">Password (min {{ \App\Models\Student::PASSWORD_MIN_LENGTH }} characters)</label>
                    @include('student.partials.password-input', ['id' => 'setup_password', 'autocomplete' => 'new-password', 'class' => $salPassword])
                </div>
                <div class="sal-field">
                    <label for="setup_password_confirmation" class="sal-label">Confirm password</label>
                    @include('student.partials.password-input', ['id' => 'setup_password_confirmation', 'autocomplete' => 'new-password', 'class' => $salPassword])
                </div>
                <div id="setup-password-error" class="hidden">
                    <div class="sal-error" id="setup-password-error-text"></div>
                </div>
                <div class="sal-actions">
                    <button type="button" id="btn-setup-password" class="sal-btn sal-btn--primary">Continue</button>
                </div>
            </div>

            <div id="step-setup-name" class="sal-step hidden">
                <div>
                    <p class="sal-kicker">Profile</p>
                    <h2 class="sal-title" style="margin-top:0.35rem;">Your name</h2>
                    <p class="sal-copy" style="margin-top:0.45rem;" id="setup-name-message">What name should we show on your account?</p>
                </div>
                <div class="sal-field">
                    <label for="setup_student_name" class="sal-label">Full name</label>
                    <input type="text" id="setup_student_name" placeholder="Full name" class="{{ $salInput }}" autocomplete="name" style="text-transform: capitalize;">
                </div>
                <div id="setup-name-error" class="hidden">
                    <div class="sal-error" id="setup-name-error-text"></div>
                </div>
                <div class="sal-actions">
                    <button type="button" id="btn-setup-name" class="sal-btn sal-btn--primary">Continue</button>
                </div>
            </div>
            @endif

            <div id="step-otp" class="sal-step hidden">
                <div>
                    <p class="sal-kicker">One-time code</p>
                    <h2 class="sal-title" style="margin-top:0.35rem;">Enter the code</h2>
                    <p class="sal-copy" style="margin-top:0.45rem;" id="otp-step-message">Enter the 6-digit code sent to your phone.</p>
                </div>
                <div id="otp-code-fields" class="sal-step" style="gap:1rem;">
                    <div class="sal-field">
                        <label class="sal-label">Code</label>
                        <div class="sal-otp" id="otp-boxes-wrap">
                            @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" data-otp-index="{{ $i }}" autocomplete="one-time-code" class="otp-digit">
                            @endfor
                        </div>
                        <input type="hidden" id="otp_code" name="code" value="">
                    </div>
                    <div id="otp-error" class="hidden">
                        <div class="sal-error" id="otp-error-text"></div>
                    </div>
                    <div class="sal-actions">
                        <button type="button" id="btn-verify-otp" class="sal-btn sal-btn--primary">Verify code</button>
                        <p style="text-align:center;margin:0;font-size:0.875rem;color:var(--sal-muted);">
                            Didn't get the code?
                            <button type="button" id="btn-resend-otp" class="sal-link" style="border:0;background:none;padding:0;cursor:pointer;">Resend</button>
                        </p>
                    </div>
                    <div id="otp-universal-fallback-wrap" class="hidden sal-note">
                        <p id="otp-universal-fallback-hint" style="margin:0;"></p>
                    </div>
                    @if(!empty($onboarding_email_otp_enabled) && !empty($mail_configured))
                    <div id="otp-email-fallback-wrap" class="hidden sal-hint sal-stack">
                        <p id="otp-email-fallback-hint" class="hidden" style="margin:0;">Having trouble with SMS? Get a one-time code by email instead (setup only).</p>
                        <button type="button" id="btn-show-email-fallback" class="sal-btn sal-btn--quiet">Get code by email instead</button>
                        <div id="otp-email-fallback-fields" class="hidden sal-stack">
                            <div class="sal-field">
                                <label for="fallback_email" class="sal-label">Email address</label>
                                <input type="email" id="fallback_email" placeholder="you@example.com" autocomplete="email" class="{{ $salInput }}">
                                <p style="margin:0;font-size:0.78rem;color:var(--sal-muted);">We will save this to your account and send a code that expires in 15 minutes.</p>
                            </div>
                            <button type="button" id="btn-send-email-otp" class="sal-btn sal-btn--primary">Send code to email</button>
                        </div>
                    </div>
                    @endif
                    <p id="otp-days-remaining" class="hidden" style="text-align:center;margin:0;font-size:0.8rem;color:var(--sal-muted);" aria-live="polite"></p>
                    <button type="button" id="btn-back-to-phone" class="sal-btn sal-btn--ghost">Back</button>
                </div>
            </div>
        </div>

        <div id="sal-auth-overlay" class="sal-auth-overlay" hidden aria-live="polite" aria-atomic="true">
            <div class="sal-auth-card">
                <div class="sal-auth-mark" aria-hidden="true">
                    <img src="{{ \App\Support\BrandAssets::markUrl() }}" alt="" width="38" height="38" decoding="async">
                </div>
                <div class="sal-auth-ring" aria-hidden="true">
                    <svg viewBox="0 0 52 52" data-auth-spinner>
                        <circle class="sal-auth-ring-track" cx="26" cy="26" r="14"/>
                        <circle class="sal-auth-ring-arc" cx="26" cy="26" r="14"/>
                    </svg>
                    <div class="sal-auth-check" data-auth-check>
                        <svg viewBox="0 0 24 24" class="sal-auth-check-svg">
                            <path d="M5 12.5l4.2 4.2L19 7.2"/>
                        </svg>
                    </div>
                </div>
                <p class="sal-auth-title" data-auth-title>Signing you in</p>
                <p class="sal-auth-sub" data-auth-sub>Verifying your account…</p>
                <div class="sal-auth-dots" data-auth-dots aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var studentPasswordMinLength = {{ \App\Models\Student::PASSWORD_MIN_LENGTH }};
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var csrfRefreshUrl = '{{ route("student.account.csrf-token") }}';
    var jsonHeaders = function(token) {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token || csrf || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    };
    function ensureFreshCsrf() {
        if (!csrfRefreshUrl) return Promise.resolve(csrf);
        return fetch(csrfRefreshUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.token) {
                    csrf = data.token;
                    var m = document.querySelector('meta[name="csrf-token"]');
                    if (m) m.setAttribute('content', csrf);
                }
                return csrf;
            });
    }
    function parseJsonResponse(r) {
        var ct = (r.headers.get('content-type') || '').toLowerCase();
        if (ct.indexOf('application/json') === -1) {
            return Promise.reject(new Error(r.status >= 500
                ? 'Server error. Please try again in a moment.'
                : 'Unexpected response from server.'));
        }
        return r.json().then(function(data) {
            if (!r.ok && data && !data.message) {
                data.message = r.status >= 500
                    ? 'Server error. Please try again in a moment.'
                    : 'Request failed. Please try again.';
            }
            return data;
        });
    }
    function postJson(url, payload, timeoutMs) {
        var controller = new AbortController();
        var timer = setTimeout(function() { controller.abort(); }, timeoutMs || 12000);
        function doFetch(token) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(token),
                body: JSON.stringify(payload),
                signal: controller.signal
            });
        }
        return ensureFreshCsrf()
            .then(function() { return doFetch(csrf); })
            .then(function(r) {
                if (r.status === 419) {
                    return ensureFreshCsrf().then(function(t) { return doFetch(t); });
                }
                return r;
            })
            .then(function(r) {
                clearTimeout(timer);
                return parseJsonResponse(r).then(function(data) {
                    return { ok: r.ok, status: r.status, data: data };
                });
            })
            .catch(function(err) {
                clearTimeout(timer);
                throw err;
            });
    }
    var passwordLoginEnabled = @json(!empty($password_login_enabled));
    var onboardingEmailOtpEnabled = @json(!empty($onboarding_email_otp_enabled) && !empty($mail_configured));
    var otpChannel = 'sms';
    var smsResendCount = 0;
    var universalOtpConfigured = @json($universalOtpConfigured ?? false);
    var stepIndex = document.getElementById('step-index');
    var stepEmail = document.getElementById('step-email');
    var stepPhone = document.getElementById('step-phone');
    var stepOtp = document.getElementById('step-otp');
    var stepPassword = document.getElementById('step-password');
    var stepSetupPassword = document.getElementById('step-setup-password');
    var stepSetupName = document.getElementById('step-setup-name');
    var indexInput = document.getElementById('index_number');
    var emailInput = document.getElementById('student_email');
    var phoneInput = document.getElementById('phone');
    var currentIndexNumber = '';
    var lastPhoneUsed = '';

    function showStep(step) {
        var panels = [stepIndex, stepEmail, stepPhone, stepOtp, stepPassword, stepSetupPassword, stepSetupName];
        panels.forEach(function (el) {
            if (!el) return;
            el.classList.add('hidden');
            el.classList.remove('sal-step--enter');
        });
        var active = null;
        if (step === 'index') active = stepIndex;
        else if ((step === 'email' || step === 'setup_email') && stepEmail) active = stepEmail;
        else if (step === 'phone') active = stepPhone;
        else if (step === 'password' && stepPassword) active = stepPassword;
        else if (step === 'setup_password' && stepSetupPassword) active = stepSetupPassword;
        else if (step === 'setup_name' && stepSetupName) active = stepSetupName;
        else if (step === 'otp') {
            active = stepOtp;
            initOtpBoxes();
        }
        if (active) {
            active.classList.remove('hidden');
            void active.offsetWidth;
            active.classList.add('sal-step--enter');
        }
    }

    function redirectIfReady(data) {
        if (data && data.redirect) {
            window.location.href = data.redirect;
            return true;
        }
        return false;
    }

    function isIndexNotFoundError(data, text) {
        if (data && data.error_code === 'index_not_found') return true;
        if (!text) return false;
        var lower = String(text).toLowerCase();
        return lower.indexOf('index number not found') !== -1
            || lower.indexOf('not on the class list') !== -1
            || lower.indexOf('class rep') !== -1;
    }

    function showError(elId, text, data) {
        var wrap = document.getElementById(elId);
        var textEl = document.getElementById(elId + '-text');
        if (!wrap || !textEl) return;
        textEl.textContent = text || '';
        wrap.classList.toggle('hidden', !text);
        var supportWrap = document.getElementById('index-error-support-wrap');
        var supportLink = document.getElementById('index-error-support');
        var indexGuidance = document.getElementById('index-error-index-guidance');
        var liveBtn = document.getElementById('index-error-live-support');
        if (elId === 'index-error') {
            var indexIssue = isIndexNotFoundError(data, text);
            if (indexGuidance) indexGuidance.classList.toggle('hidden', !indexIssue);
            if (supportWrap && supportLink) {
                if (text && !indexIssue) {
                    supportLink.dataset.supportHint = text;
                    supportLink.dataset.supportIndex = (indexInput && indexInput.value) ? indexInput.value.trim() : '';
                    supportWrap.classList.remove('hidden');
                } else {
                    delete supportLink.dataset.supportHint;
                    delete supportLink.dataset.supportIndex;
                    supportWrap.classList.add('hidden');
                }
            }
            if (liveBtn && text && !indexIssue) {
                liveBtn.onclick = function() {
                    if (window.QuizSnapLiveSupport) {
                        window.QuizSnapLiveSupport.open({
                            student_index: (indexInput && indexInput.value) ? indexInput.value.trim() : '',
                            page_url: window.location.pathname,
                            issue_category: 'account_login',
                            initial_message: 'Account login issue: ' + text
                        });
                    }
                };
            }
        }
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
        btn.textContent = loading ? 'Please wait…' : (btn.dataset.originalText || 'Continue');
    }

    function showAuthChecking() {
        var shell = document.querySelector('.sal-shell');
        var overlay = document.getElementById('sal-auth-overlay');
        if (!overlay) return;
        var check = overlay.querySelector('[data-auth-check]');
        var title = overlay.querySelector('[data-auth-title]');
        var sub = overlay.querySelector('[data-auth-sub]');
        overlay.hidden = false;
        overlay.classList.remove('is-success');
        if (check) check.setAttribute('aria-hidden', 'true');
        if (title) title.textContent = 'Signing you in';
        if (sub) sub.textContent = 'Verifying your account…';
        requestAnimationFrame(function () {
            overlay.classList.add('is-visible');
            if (shell) shell.classList.add('is-auth-busy');
        });
    }

    function hideAuthChecking() {
        var shell = document.querySelector('.sal-shell');
        var overlay = document.getElementById('sal-auth-overlay');
        if (shell) shell.classList.remove('is-auth-busy', 'is-auth-success');
        if (!overlay) return;
        overlay.classList.remove('is-visible', 'is-success');
        setTimeout(function () {
            if (!overlay.classList.contains('is-visible')) overlay.hidden = true;
        }, 320);
    }

    function completeAuthSuccess(redirectUrl) {
        var shell = document.querySelector('.sal-shell');
        var overlay = document.getElementById('sal-auth-overlay');
        if (!overlay) {
            window.location.href = redirectUrl;
            return;
        }
        var check = overlay.querySelector('[data-auth-check]');
        var title = overlay.querySelector('[data-auth-title]');
        var sub = overlay.querySelector('[data-auth-sub]');
        overlay.hidden = false;
        overlay.classList.add('is-visible', 'is-success');
        if (check) check.setAttribute('aria-hidden', 'false');
        if (title) title.textContent = "You're in";
        if (sub) sub.textContent = 'Opening your dashboard…';
        if (shell) {
            shell.classList.add('is-auth-busy');
            setTimeout(function () { shell.classList.add('is-auth-success'); }, 320);
        }
        setTimeout(function () {
            window.location.href = redirectUrl;
        }, 980);
    }

    function updateUniversalFallbackUi(data, forceShow) {
        var wrap = document.getElementById('otp-universal-fallback-wrap');
        var hint = document.getElementById('otp-universal-fallback-hint');
        if (!wrap || !hint) return;
        var available = universalOtpConfigured || !!(data && data.universal_fallback_available);
        var promote = forceShow || universalOtpConfigured || !!(data && data.show_universal_fallback);
        wrap.classList.toggle('hidden', !available || !promote);
        if (promote) {
            hint.textContent = (data && data.universal_fallback_message)
                || 'If SMS is unavailable, enter your institution login code below.';
        }
    }

    function updateEmailFallbackUi(data, forceShow) {
        if (!onboardingEmailOtpEnabled) return;
        var wrap = document.getElementById('otp-email-fallback-wrap');
        var hint = document.getElementById('otp-email-fallback-hint');
        if (!wrap) return;
        var available = !!(data && data.email_fallback_available);
        var promote = forceShow || !!(data && data.show_email_fallback) || smsResendCount >= 1;
        wrap.classList.toggle('hidden', !(available && promote));
        if (hint) hint.classList.toggle('hidden', !(available && promote));
        if (data && data.prefill_email) {
            var fe = document.getElementById('fallback_email');
            if (fe && !fe.value) fe.value = data.prefill_email;
        }
        if (data && data.otp_channel === 'email') {
            otpChannel = 'email';
            var daysEl = document.getElementById('otp-days-remaining');
            if (daysEl && data.expires_minutes) {
                daysEl.textContent = 'Email code expires in ' + data.expires_minutes + ' minutes.';
                daysEl.style.display = 'block';
            }
        }
    }

    function handleLoginStepData(data, indexFallback) {
        currentIndexNumber = data.index_number || indexFallback || currentIndexNumber;
        if (redirectIfReady(data)) return;

        if ((data.step === 'email' || data.step === 'setup_email') && stepEmail) {
            document.getElementById('email-step-message').textContent = data.message || 'Enter your email address.';
            if (emailInput) emailInput.value = data.prefill_email || emailInput.value || '';
            showError('email-error', '');
            showStep('setup_email');
        } else if (data.step === 'setup_password' && stepSetupPassword) {
            document.getElementById('setup-password-message').textContent = data.message || 'Create a password for your account.';
            showError('setup-password-error', '');
            var sp = document.getElementById('setup_password');
            var spc = document.getElementById('setup_password_confirmation');
            if (sp) sp.value = '';
            if (spc) spc.value = '';
            showStep('setup_password');
        } else if (data.step === 'setup_name' && stepSetupName) {
            document.getElementById('setup-name-message').textContent = data.message || 'What name should we show on your account?';
            showError('setup-name-error', '');
            var sn = document.getElementById('setup_student_name');
            if (sn) sn.value = data.prefill_name || '';
            showStep('setup_name');
        } else if (data.step === 'password' && passwordLoginEnabled && stepPassword) {
            document.getElementById('password-step-message').textContent = data.message || 'Enter your password.';
            showError('password-error', '');
            var lp = document.getElementById('login_password');
            if (lp) lp.value = '';
            showStep('password');
        } else if (data.step === 'phone') {
            document.getElementById('phone-step-message').textContent = data.message || 'Enter your active phone number. We will send a one-time SMS code.';
            showStep('phone');
            if (phoneInput) {
                phoneInput.value = data.prefill_phone || '';
                phoneInput.readOnly = false;
            }
        } else if (data.step === 'otp') {
            document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your phone.';
            if (data.can_resend) lastPhoneUsed = '__registered__';
            var resendBtn = document.getElementById('btn-resend-otp');
            if (resendBtn) {
                resendBtn.disabled = data.can_resend === false;
                resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                    ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
            }
            var daysEl = document.getElementById('otp-days-remaining');
            if (daysEl) {
                if (data.days_remaining != null) {
                    daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                    daysEl.style.display = 'block';
                } else if (data.otp_never_expires) {
                    daysEl.textContent = 'This code does not expire until you receive a new one.';
                    daysEl.style.display = 'block';
                }
            }
            updateEmailFallbackUi(data, false);
            updateUniversalFallbackUi(data, true);
            showStep('otp');
        }
    }

    document.getElementById('btn-index').addEventListener('click', function() {
        var index = (indexInput && indexInput.value) ? indexInput.value.trim().toUpperCase() : '';
        if (!index) {
            showError('index-error', 'Please enter your index number.');
            return;
        }
        showError('index-error', '');
        setLoading(this, true);
        postJson('{{ route("student.account.verify-index") }}', { index_number: index })
        .then(function(result) {
            setLoading(document.getElementById('btn-index'), false);
            var data = result.data;
            if (!data || !data.success) {
                showError('index-error', (data && data.message) || 'Verification failed. Please try again.', data);
                var btnIndex = document.getElementById('btn-index');
                if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
                return;
            }
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) btnIndex.dataset.originalText = 'Continue';
            handleLoginStepData(data, index);
        })
        .catch(function(err) {
            setLoading(document.getElementById('btn-index'), false);
            var msg = (err && err.name === 'AbortError')
                ? 'Request timed out. Please try again.'
                : 'Network error. Please try again.';
            showError('index-error', msg);
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
        });
    });

    if (document.getElementById('btn-save-email')) {
        document.getElementById('btn-save-email').addEventListener('click', function() {
            var email = (emailInput && emailInput.value) ? emailInput.value.trim() : '';
            if (!email || email.indexOf('@') < 1) {
                showError('email-error', 'Please enter a valid email address.');
                return;
            }
            showError('email-error', '');
            setLoading(this, true);
            fetch('{{ route("student.account.save-email") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify({ index_number: currentIndexNumber, email: email })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-save-email'), false);
                if (!data.success) {
                    showError('email-error', data.message || 'Could not save email.');
                    return;
                }
                if (redirectIfReady(data)) return;
                handleLoginStepData(data, currentIndexNumber);
            })
            .catch(function() {
                setLoading(document.getElementById('btn-save-email'), false);
                showError('email-error', 'Network error. Please try again.');
            });
        });
    }
    if (document.getElementById('btn-back-email-to-index')) {
        document.getElementById('btn-back-email-to-index').addEventListener('click', function() {
            showStep('index');
            showError('email-error', '');
        });
    }

    document.getElementById('btn-back-to-index').addEventListener('click', function() {
        showStep('index');
        showError('phone-error', '');
        if (phoneInput) phoneInput.readOnly = false;
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    if (passwordLoginEnabled && document.getElementById('btn-verify-password')) {
        document.getElementById('btn-verify-password').addEventListener('click', function() {
            var pw = document.getElementById('login_password');
            var v = pw && pw.value ? pw.value : '';
            if (!v) {
                showError('password-error', 'Please enter your password.');
                return;
            }
            showError('password-error', '');
            setLoading(this, true);
            showAuthChecking();
            var startedAt = Date.now();
            var verifyPwUrl = '{{ route("student.account.verify-password") }}';
            function doPw() {
                return fetch(verifyPwUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber, password: v })
                });
            }
            function waitMin(ms) {
                var left = Math.max(0, ms - (Date.now() - startedAt));
                return left ? new Promise(function (resolve) { setTimeout(resolve, left); }) : Promise.resolve();
            }
            ensureFreshCsrf().then(function() { return doPw(); })
            .then(function(r) {
                if (r.status === 419) return ensureFreshCsrf().then(function() { return doPw(); });
                return r;
            })
            .then(function(r) { return parseJsonResponse(r); })
            .then(function(data) {
                return waitMin(650).then(function () { return data; });
            })
            .then(function(data) {
                setLoading(document.getElementById('btn-verify-password'), false);
                if (!data.success) {
                    hideAuthChecking();
                    if (data.step === 'phone') {
                        handleLoginStepData(data, currentIndexNumber);
                        return;
                    }
                    showError('password-error', data.message || 'Sign-in failed.');
                    return;
                }
                if (data.redirect) {
                    completeAuthSuccess(data.redirect);
                    return;
                }
                hideAuthChecking();
            })
            .catch(function(err) {
                setLoading(document.getElementById('btn-verify-password'), false);
                hideAuthChecking();
                showError('password-error', (err && err.message) ? err.message : 'Network error. Please try again.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-password-use-sms')) {
        document.getElementById('btn-password-use-sms').addEventListener('click', function() {
            if (!currentIndexNumber) return;
            showError('password-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.request-otp-login") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-password-use-sms'), false);
                if (!data.success) {
                    showError('password-error', data.message || 'Could not send SMS.');
                    return;
                }
                document.getElementById('otp-step-message').textContent = data.message || 'Enter the code from your phone.';
                showStep('otp');
            })
            .catch(function() {
                setLoading(document.getElementById('btn-password-use-sms'), false);
                showError('password-error', 'Network error.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-back-password-to-index')) {
        document.getElementById('btn-back-password-to-index').addEventListener('click', function() {
            showStep('index');
            showError('password-error', '');
        });
    }

    document.getElementById('btn-send-otp').addEventListener('click', function() {
        var phone = (phoneInput && phoneInput.value) ? phoneInput.value.trim() : '';
        if (!phone) {
            showError('phone-error', 'Please enter your phone number.');
            return;
        }
        showError('phone-error', '');
        setLoading(this, true);
        var sendBody = { index_number: currentIndexNumber, phone: phone };
        ensureFreshCsrf().then(function() {
            return fetch('{{ route("student.account.send-otp") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(sendBody)
            });
        })
        .then(function(r) { return parseJsonResponse(r); })
        .then(function(data) {
            setLoading(document.getElementById('btn-send-otp'), false);
            if (!data.success) {
                showError('phone-error', data.message || 'We couldn\'t send the code. Please try again.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
                if (data.email_fallback_available || data.universal_fallback_available) {
                    if (data.show_universal_fallback && data.universal_fallback_message) {
                        document.getElementById('otp-step-message').textContent = data.universal_fallback_message;
                    }
                    showStep('otp');
                }
                var sendBtn = document.getElementById('btn-send-otp');
                if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
                return;
            }
            lastPhoneUsed = phone;
            otpChannel = data.otp_channel || 'sms';
            document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your number.';
            showStep('otp');
            updateEmailFallbackUi(data, false);
            updateUniversalFallbackUi(data, false);
            showError('otp-error', '');
        })
        .catch(function(err) {
            setLoading(document.getElementById('btn-send-otp'), false);
            showError('phone-error', (err && err.message) ? err.message : 'Network error. Please try again.');
            var sendBtn = document.getElementById('btn-send-otp');
            if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
        });
    });

    document.getElementById('btn-back-to-phone').addEventListener('click', function() {
        showStep('phone');
        showError('otp-error', '');
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    document.getElementById('btn-resend-otp').addEventListener('click', function() {
        if (!currentIndexNumber) {
            showError('otp-error', 'Go back and enter your index number, then try again.');
            return;
        }
        var resendBtn = document.getElementById('btn-resend-otp');
        if (resendBtn.disabled) return;
        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';
        showError('otp-error', '');
        var payload = { index_number: currentIndexNumber };
        if (lastPhoneUsed && lastPhoneUsed !== '__registered__') {
            payload.phone = lastPhoneUsed;
        }
        ensureFreshCsrf().then(function() {
            return fetch('{{ route("student.account.send-otp") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(payload)
            });
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                smsResendCount += 1;
                otpChannel = data.otp_channel || 'sms';
                document.getElementById('otp-step-message').textContent = data.message || 'A new code has been sent. Enter it above.';
                updateEmailFallbackUi(data, smsResendCount >= 1);
                updateUniversalFallbackUi(data, true);
                resendBtn.disabled = true;
                resendBtn.textContent = 'Wait ~1 min to resend';
                setTimeout(function() {
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend code';
                }, 65000);
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl) {
                    if (data.days_remaining != null) {
                        daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                        daysEl.style.display = 'block';
                    } else if (data.otp_never_expires) {
                        daysEl.textContent = 'This code does not expire until you receive a new one.';
                        daysEl.style.display = 'block';
                    }
                }
            } else {
                resendBtn.disabled = data.can_resend === false;
                resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                    ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
                showError('otp-error', data.message || 'Could not resend. Please try again.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
            }
        })
        .catch(function() {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend code';
            showError('otp-error', 'Network error. Please try again.');
        });
    });

    function getOtpCode() {
        var boxes = document.querySelectorAll('.otp-digit');
        var code = '';
        for (var i = 0; i < (boxes.length || 6); i++) {
            if (boxes[i]) code += (boxes[i].value || '').trim();
        }
        return code;
    }
    function setOtpHidden(val) {
        var h = document.getElementById('otp_code');
        if (h) h.value = val;
    }
    function initOtpBoxes() {
        var boxes = document.querySelectorAll('.otp-digit');
        setOtpHidden('');
        boxes.forEach(function(b) { b.value = ''; });
        if (boxes[0]) boxes[0].focus();

        function syncAndMaybeSubmit() {
            var code = getOtpCode();
            setOtpHidden(code);
            if (code.length === 6) {
                var btn = document.getElementById('btn-verify-otp');
                if (btn && !btn.disabled) btn.click();
            }
        }
        boxes.forEach(function(box, i) {
            box.onkeydown = function(e) {
                if (/^[0-9]$/.test(e.key)) {
                    e.preventDefault();
                    this.value = e.key;
                    if (boxes[i + 1]) boxes[i + 1].focus();
                    else this.blur();
                    syncAndMaybeSubmit();
                    return;
                }
                if (e.key === 'Backspace' && !this.value && boxes[i - 1]) {
                    e.preventDefault();
                    boxes[i - 1].focus();
                }
            };
            box.oninput = function() {
                var v = this.value.replace(/\D/g, '').slice(0, 1);
                this.value = v;
                if (v && boxes[i + 1]) boxes[i + 1].focus();
                syncAndMaybeSubmit();
            };
            box.onpaste = function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                for (var j = 0; j < pasted.length && j < boxes.length; j++) {
                    boxes[j].value = pasted[j];
                }
                if (pasted.length > 0 && boxes[pasted.length - 1]) boxes[pasted.length - 1].focus();
                syncAndMaybeSubmit();
            };
        });
    }

    document.getElementById('btn-verify-otp').addEventListener('click', function() {
        var code = getOtpCode();
        if (!code || code.length !== 6) {
            showError('otp-error', 'Please enter the 6-digit code.');
            return;
        }
        showError('otp-error', '');
        setLoading(this, true);
        var payload = { index_number: currentIndexNumber, code: code };
        var verifyUrl = '{{ route("student.account.verify-otp") }}';
        function doVerify() {
            return fetch(verifyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(payload)
            });
        }
        ensureFreshCsrf().then(function() { return doVerify(); })
        .then(function(r) {
            if (r.status === 419) {
                return ensureFreshCsrf().then(function() { return doVerify(); });
            }
            return r;
        })
        .then(function(r) { return parseJsonResponse(r); })
        .then(function(data) {
            setLoading(document.getElementById('btn-verify-otp'), false);
            if (!data.success) {
                if (data.step === 'phone') {
                    handleLoginStepData(data, currentIndexNumber);
                    return;
                }
                showError('otp-error', data.message || 'Invalid or expired code.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
                return;
            }
            if (redirectIfReady(data)) return;
            handleLoginStepData(data, currentIndexNumber);
        })
        .catch(function(err) {
            setLoading(document.getElementById('btn-verify-otp'), false);
            showError('otp-error', (err && err.message) ? err.message : 'Network error. Please try again.');
        });
    });

    var btnShowEmailFallback = document.getElementById('btn-show-email-fallback');
    if (btnShowEmailFallback) {
        btnShowEmailFallback.addEventListener('click', function() {
            var fields = document.getElementById('otp-email-fallback-fields');
            if (fields) fields.classList.remove('hidden');
            this.classList.add('hidden');
            var fe = document.getElementById('fallback_email');
            if (fe) fe.focus();
        });
    }
    var btnSendEmailOtp = document.getElementById('btn-send-email-otp');
    if (btnSendEmailOtp) {
        btnSendEmailOtp.addEventListener('click', function() {
            var email = (document.getElementById('fallback_email') && document.getElementById('fallback_email').value) ? document.getElementById('fallback_email').value.trim() : '';
            if (!email) {
                showError('otp-error', 'Enter your email address.');
                return;
            }
            if (!currentIndexNumber) {
                showError('otp-error', 'Go back and enter your index number first.');
                return;
            }
            showError('otp-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.send-onboarding-email-otp") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber, email: email })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(btnSendEmailOtp, false);
                if (!data.success) {
                    showError('otp-error', data.message || 'Could not send email code.');
                    updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                    return;
                }
                handleLoginStepData(data, currentIndexNumber);
                initOtpBoxes();
            })
            .catch(function() {
                setLoading(btnSendEmailOtp, false);
                showError('otp-error', 'Network error. Please try again.');
            });
        });
    }

    if (document.getElementById('btn-setup-password')) {
        document.getElementById('btn-setup-password').addEventListener('click', function() {
            var sp = document.getElementById('setup_password');
            var spc = document.getElementById('setup_password_confirmation');
            var password = sp ? sp.value : '';
            var confirmation = spc ? spc.value : '';
            if (!password || password.length < studentPasswordMinLength) {
                showError('setup-password-error', 'Password must be at least ' + studentPasswordMinLength + ' characters.');
                return;
            }
            if (password !== confirmation) {
                showError('setup-password-error', 'Password confirmation does not match.');
                return;
            }
            showError('setup-password-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.setup-password") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({
                        index_number: currentIndexNumber,
                        password: password,
                        password_confirmation: confirmation
                    })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-setup-password'), false);
                if (!data.success) {
                    showError('setup-password-error', data.message || 'Could not save password.');
                    return;
                }
                if (redirectIfReady(data)) return;
                handleLoginStepData(data, currentIndexNumber);
            })
            .catch(function() {
                setLoading(document.getElementById('btn-setup-password'), false);
                showError('setup-password-error', 'Network error. Please try again.');
            });
        });
    }

    if (document.getElementById('btn-setup-name')) {
        document.getElementById('btn-setup-name').addEventListener('click', function() {
            var nameEl = document.getElementById('setup_student_name');
            var name = nameEl && nameEl.value ? nameEl.value.trim() : '';
            if (!name) {
                showError('setup-name-error', 'Please enter your name.');
                return;
            }
            showError('setup-name-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.setup-name") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber, student_name: name })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-setup-name'), false);
                if (!data.success) {
                    showError('setup-name-error', data.message || 'Could not save name.');
                    return;
                }
                if (redirectIfReady(data)) return;
                handleLoginStepData(data, currentIndexNumber);
            })
            .catch(function() {
                setLoading(document.getElementById('btn-setup-name'), false);
                showError('setup-name-error', 'Network error. Please try again.');
            });
        });
    }
})();
</script>
@endpush
@endsection
