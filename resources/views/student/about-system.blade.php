@extends('layouts.app')

@php
    $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
@endphp

@section('title', 'About ' . $appName)
@section('body_class', 'qs-marketing')

@push('styles')
@include('student.partials.marketing-chrome-styles')
<style>
    .about-page-main {
        flex: 1;
        width: 100%;
        padding: 1.5rem 0 3rem;
    }
    .about-hero {
        margin-bottom: 1.5rem;
    }
    .about-card {
        width: 100%;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        padding: 1.5rem;
    }
    @media (min-width: 640px) {
        .about-page-main { padding: 2rem 0 4rem; }
        .about-card { padding: 2rem; border-radius: 1.5rem; }
    }
    @media (min-width: 1024px) {
        .about-card { padding: 2.5rem 3rem; }
    }
    .about-section {
        padding: 1.25rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .about-section:last-child { border-bottom: none; padding-bottom: 0; }
    .about-section:first-child { padding-top: 0; }
    .about-section-head {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 0.875rem;
    }
    .about-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }
    .about-icon svg { width: 1.375rem; height: 1.375rem; color: #fff; }
    .about-section-body {
        color: #475569;
        font-size: 0.9375rem;
        line-height: 1.65;
    }
    .about-section-body p { margin: 0 0 0.625rem; }
    .about-section-body p:last-child { margin-bottom: 0; }
    .about-section-body ul { margin: 0.5rem 0 0; padding-left: 1.25rem; }
    .about-section-body li { margin-bottom: 0.375rem; }
    .about-highlight {
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 1rem;
        padding: 1.25rem;
        margin-top: 0.5rem;
    }
    .about-cta {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen flex flex-col font-sans antialiased qs-landing-shell">
    @include('student.partials.marketing-header', [
        'appName' => $appName,
        'student' => $student ?? null,
        'showStudentLogin' => true,
    ])

    <main class="about-page-main">
        <div class="qs-container">
            <div class="about-hero">
                <a href="{{ route('student.landing') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1 mb-4 no-underline">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Home
                </a>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 tracking-tight">How {{ $appName }} Works</h1>
                <p class="mt-3 text-base sm:text-lg text-slate-600 max-w-3xl leading-relaxed">
                    {{ $appName }} is a secure online assessment platform for educational institutions.
                    Here is everything you need to know about taking quizzes.
                </p>
            </div>

            <div class="about-card">
                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Getting Started</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Your first steps to taking a quiz</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <p><strong>Receive your token:</strong> Your lecturer or examiner will provide a unique quiz token (e.g., KTdie54-3Sx9).</p>
                        <p><strong>Enter the token:</strong> On the homepage, enter the token and click Start Quiz.</p>
                        <p><strong>Login to your account:</strong> You can also log in to see all available quizzes on your dashboard.</p>
                    </div>
                </div>

                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Verification Process</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Secure identity verification steps</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <p><strong>Index number:</strong> Enter your student index number for verification.</p>
                        <p><strong>Phone verification:</strong> First-time users verify their phone number with an OTP.</p>
                        <p><strong>Pre-quiz photo:</strong> Take a clear face photo using your device camera.</p>
                    </div>
                </div>

                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Taking the Quiz</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Important guidelines during the quiz</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <p><strong>Timer:</strong> A countdown begins when you start. Complete the quiz before time runs out.</p>
                        <p><strong>Answer questions:</strong> Select your answers carefully on each screen.</p>
                        <p><strong>Auto-save:</strong> Your answers are saved automatically as you progress.</p>
                        <p><strong>Stay focused:</strong> Remain on the quiz tab. Switching tabs may be logged as a violation.</p>
                        <p><strong>Desktop recommended:</strong> QuizSnap is optimized for desktop browsers with a working webcam.</p>
                    </div>
                </div>

                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Proctoring & Security</h2>
                            <p class="text-sm text-slate-500 mt-0.5">What is monitored during a proctored quiz</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <ul>
                            <li><strong>Face verification & camera:</strong> Pre- and post-quiz photos confirm your identity.</li>
                            <li><strong>Screen focus:</strong> Leaving the quiz tab or resizing the window may be logged.</li>
                            <li><strong>Multiple faces:</strong> More than one face in frame is recorded for review.</li>
                            <li><strong>Clipboard & screenshots:</strong> Copy/paste and screenshot attempts are serious violations.</li>
                            <li><strong>Devices & network:</strong> Using a second device or different IP may be flagged.</li>
                        </ul>
                        <p class="mt-3"><strong>Auto-submit:</strong> Repeated or serious violations can automatically submit your quiz for examiner review.</p>
                        <p><strong>Network problems:</strong> Brief disconnections do not auto-submit; answers sync when you reconnect.</p>
                    </div>
                </div>

                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Submitting & Results</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Complete and view your results</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <p><strong>Final photo:</strong> After completing questions, take a final photo to submit.</p>
                        <p><strong>Instant results:</strong> Your score is calculated immediately after submission.</p>
                        <p><strong>Review answers:</strong> See correct answers if enabled by your lecturer.</p>
                        <p><strong>Results history:</strong> Scores are saved; detailed reviews are available for 21 days.</p>
                    </div>
                </div>

                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Technical Requirements</h2>
                            <p class="text-sm text-slate-500 mt-0.5">What you need to get started</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <p><strong>Device:</strong> Desktop or laptop computer with a working webcam.</p>
                        <p><strong>Browser:</strong> Modern browser with JavaScript enabled (Chrome, Firefox, Safari, Edge).</p>
                        <p><strong>Internet:</strong> Stable connection throughout the quiz.</p>
                        <p><strong>Environment:</strong> Quiet space with good lighting.</p>
                    </div>
                </div>

                <div class="about-highlight">
                    <h2 class="text-lg font-semibold text-blue-900 mb-3">Tips for Success</h2>
                    <ul class="text-slate-700 space-y-2 text-sm sm:text-base">
                        <li>Test your camera and internet connection before starting.</li>
                        <li>Keep your face visible during photo capture.</li>
                        <li>Do not switch tabs during the quiz.</li>
                        <li>Read each question carefully and manage your time.</li>
                        <li>Use a quiet environment with good lighting.</li>
                    </ul>
                </div>

                <div class="about-section mt-2">
                    <h2 class="text-lg font-semibold text-slate-900 mb-2">Need Help?</h2>
                    <p class="about-section-body">
                        If you encounter issues during your quiz, contact your lecturer or examiner immediately.
                        Common issues include camera problems, invalid tokens, connection drops, or technical errors.
                    </p>
                </div>

                <div class="about-cta">
                    <a href="{{ route('student.landing') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors no-underline">
                        Go to Homepage
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </main>

    @include('student.partials.support-fab', ['supportPage' => 'About'])
</div>
@endsection

@push('scripts')
@include('student.partials.marketing-support-scripts')
@endpush
