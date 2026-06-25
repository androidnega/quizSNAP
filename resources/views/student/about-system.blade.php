@extends('layouts.app')

@php
    $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $proctoringThresholds = \App\Models\Setting::getProctoringThresholds();
    $tabSwitchLimit = $proctoringThresholds['tab_switch_limit'];
    $outOfFrameSeconds = $proctoringThresholds['out_of_frame_seconds'];
    $multipleFacesSeconds = $proctoringThresholds['multiple_faces_seconds'];
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
    .about-rules-box {
        border-radius: 1rem;
        padding: 1.25rem;
        margin-top: 0.75rem;
    }
    .about-rules-box--critical {
        background: #fef2f2;
        border: 1px solid #fecaca;
    }
    .about-rules-box--warning {
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .about-rules-box--info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .about-rules-box h3 {
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 0.625rem;
    }
    .about-rules-box--critical h3 { color: #991b1b; }
    .about-rules-box--warning h3 { color: #92400e; }
    .about-rules-box--info h3 { color: #0f172a; }
    .about-rules-list {
        margin: 0;
        padding-left: 1.25rem;
        color: #334155;
        font-size: 0.9375rem;
        line-height: 1.65;
    }
    .about-rules-list li { margin-bottom: 0.5rem; }
    .about-rules-list li:last-child { margin-bottom: 0; }
    .about-rule-tag {
        display: inline-block;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        vertical-align: middle;
        margin-right: 0.375rem;
    }
    .about-rule-tag--critical { background: #fee2e2; color: #991b1b; }
    .about-rule-tag--warning { background: #fef3c7; color: #92400e; }
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
                        <p><strong>Timer:</strong> The clock starts when you begin. Finish before time runs out.</p>
                        <p><strong>Full screen:</strong> Proctored quizzes run in full screen. Stay in full screen for the whole attempt.</p>
                        <p><strong>Answer questions:</strong> Read each question carefully. Your answers save automatically as you go.</p>
                        <p><strong>One tab only:</strong> Keep the quiz tab open and in front of you. Do not open other apps, windows, or browser tabs.</p>
                        <p><strong>Camera on:</strong> Your face should stay clearly visible. Sit in a quiet place with good lighting.</p>
                        <p><strong>Desktop or laptop:</strong> Use a computer with a working webcam. Phones are not recommended for proctored exams.</p>
                    </div>
                </div>

                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Quiz Rules &amp; Auto-Submit</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Read this before you start — breaking these rules can end your quiz early</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <p>
                            During a proctored quiz, {{ $appName }} watches your camera, screen focus, and exam behaviour to keep the test fair.
                            Some mistakes only give a <strong>warning</strong>. Others are <strong>critical</strong> and can
                            <strong>submit your quiz immediately</strong>, even if time is still left.
                        </p>

                        <div class="about-rules-box about-rules-box--critical">
                            <h3>Critical rules — quiz can end immediately</h3>
                            <p class="text-sm text-red-900 mb-3">The first time you do any of these, your quiz may be submitted straight away. You cannot continue after that.</p>
                            <ul class="about-rules-list">
                                <li><span class="about-rule-tag about-rule-tag--critical">Critical</span><strong>Phone or prohibited object:</strong> A phone, tablet, or similar item appears in your camera view.</li>
                                <li><span class="about-rule-tag about-rule-tag--critical">Critical</span><strong>Screenshot attempt:</strong> Trying to capture the screen (for example with keyboard shortcuts or screen capture tools).</li>
                                <li><span class="about-rule-tag about-rule-tag--critical">Critical</span><strong>Copy or paste:</strong> Copying, cutting, or pasting text during the quiz.</li>
                                <li><span class="about-rule-tag about-rule-tag--critical">Critical</span><strong>Different device or location:</strong> Continuing the same quiz from another IP address or network (for example switching computers mid-exam).</li>
                                <li><span class="about-rule-tag about-rule-tag--critical">Critical</span><strong>Leaving full screen again:</strong> The first time you exit full screen you get a strong warning. If you leave full screen or resize the window a second time, the quiz is submitted.</li>
                                <li><span class="about-rule-tag about-rule-tag--critical">Critical</span><strong>Face away too long:</strong> Your face is missing from the camera for about <strong>{{ $outOfFrameSeconds }} seconds</strong> in a row.</li>
                                <li><span class="about-rule-tag about-rule-tag--critical">Critical</span><strong>More than one person:</strong> Two or more faces stay in the camera for about <strong>{{ $multipleFacesSeconds }} seconds</strong> in a row.</li>
                            </ul>
                        </div>

                        <div class="about-rules-box about-rules-box--warning">
                            <h3>Repeated mistakes — quiz ends after several warnings</h3>
                            <p class="text-sm text-amber-900 mb-3">These build up. If you keep doing them, your quiz will be submitted even if you still have time left.</p>
                            <ul class="about-rules-list">
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Switching tabs or minimizing:</strong> Leaving the quiz tab or blurring the window is logged. After <strong>{{ $tabSwitchLimit }}</strong> tab-switch or minimize events, your quiz is auto-submitted.</li>
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Staying away from the quiz tab:</strong> If you switch away and stay in another tab or app for about <strong>20 seconds</strong>, the quiz can be submitted automatically.</li>
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Many short face warnings:</strong> Repeated “face out of frame” warnings (even if each one is brief) can also lead to auto-submit after too many occurrences.</li>
                            </ul>
                        </div>

                        <div class="about-rules-box about-rules-box--info">
                            <h3>Logged as warnings — try to avoid, but not instant submit on their own</h3>
                            <ul class="about-rules-list">
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Head turns:</strong> Looking far left, right, up, or down is recorded. It does not end the quiz by itself, but examiners can review it.</li>
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Static face:</strong> The system may flag if your face looks unnaturally still (for example a photo on screen).</li>
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Right-click:</strong> Right-clicking is blocked and logged. It does not auto-submit on its own.</li>
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Brief camera issues:</strong> Short glitches, a momentary face loss, or a quick look away may count as warnings — fix your position quickly.</li>
                                <li><span class="about-rule-tag about-rule-tag--warning">Warning</span><strong>Camera disconnected:</strong> If your camera stops working, reconnect as fast as you can and tell your lecturer if needed.</li>
                            </ul>
                        </div>

                        <p class="mt-4"><strong>What happens after auto-submit?</strong> Your answers up to that point are saved and marked. Your lecturer can see violation logs and camera captures. The result may be held for review in serious cases.</p>
                        <p><strong>Network problems:</strong> A short internet drop does <em>not</em> auto-submit your quiz. Answers sync when you reconnect. Do not refresh the page unless your lecturer tells you to.</p>
                    </div>
                </div>

                <div class="about-section">
                    <div class="about-section-head">
                        <div class="about-icon" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">What We Monitor</h2>
                            <p class="text-sm text-slate-500 mt-0.5">A simple summary of proctoring</p>
                        </div>
                    </div>
                    <div class="about-section-body">
                        <ul>
                            <li><strong>Identity photos:</strong> A photo at the start (and usually at the end) to confirm it is you.</li>
                            <li><strong>Live camera:</strong> Your face during the quiz, with snapshots when certain violations happen.</li>
                            <li><strong>Screen focus:</strong> Whether you stay on the quiz tab and in full screen.</li>
                            <li><strong>Exam behaviour:</strong> Copy/paste, screenshots, extra faces, phones, and similar actions.</li>
                            <li><strong>Device &amp; network:</strong> The device and connection used for the attempt.</li>
                        </ul>
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
                    <h2 class="text-lg font-semibold text-blue-900 mb-3">Tips for a smooth quiz</h2>
                    <ul class="text-slate-700 space-y-2 text-sm sm:text-base">
                        <li>Read this page and test your camera before the exam starts.</li>
                        <li>Use full screen and do not resize or minimize the browser.</li>
                        <li>Stay on the quiz tab — do not open WhatsApp, notes, or other websites.</li>
                        <li>Keep your face centered and well lit. Only you should be in the camera.</li>
                        <li>Put your phone away and out of sight before you begin.</li>
                        <li>Do not copy, paste, or screenshot anything during the quiz.</li>
                        <li>Use a stable internet connection on one computer for the whole attempt.</li>
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
