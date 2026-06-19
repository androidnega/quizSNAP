@extends('layouts.student')

@section('title', 'Ready to start')
@section('body_class', 'bg-offwhite')

@section('content')
<div id="quiz-fs-gate" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-95 px-4">
    <div class="max-w-md w-full bg-white border border-gray-200 rounded-xl p-6 shadow-lg text-center">
        <h2 class="text-lg font-bold text-gray-900 mb-2">Full screen required</h2>
        <p class="text-sm text-gray-600 mb-5">Before you start the quiz, your browser must be in full screen. This helps prevent cheating and is required for proctoring.</p>
        <button type="button" id="quiz-fs-gate-btn" class="btn btn-action w-full py-2.5 text-sm font-semibold bg-sky-600 hover:bg-sky-700 text-white border-0">
            Enter full screen
        </button>
        <p id="quiz-fs-gate-hint" class="mt-3 text-xs text-gray-500 hidden">Full screen active. You can start the quiz below.</p>
    </div>
</div>

<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-6 w-full max-w-full">
    <div class="max-w-md w-full max-w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h1 class="text-xl font-bold text-gray-800 mb-1">Quiz Summary</h1>
            <p class="text-gray-600 text-sm mb-4">Your identity photo is verified. Review details and start when ready.</p>

            <div class="space-y-3 mb-4">
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Course</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $courseName }}</p>
                </div>
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Duration</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $durationMinutes }} min</p>
                </div>
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Questions</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $questionCount }}</p>
                </div>
                <div class="border border-danger-200 bg-danger-50 rounded-lg p-3">
                    <p class="text-xs font-semibold text-danger-800 uppercase tracking-wide mb-0.5">Warning</p>
                    <p class="text-xs text-danger-700">Stay in frame throughout the quiz. First and second violations show warnings; the third out-of-frame violation auto-submits your quiz.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('student.quiz.session.start') }}" id="quiz-start-form">
                @csrf
                <button type="submit" id="start-quiz-btn" disabled class="btn btn-action w-full py-2.5 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white border-0 disabled:opacity-50 disabled:cursor-not-allowed">
                    Start Quiz
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quiz-window-state.js') }}"></script>
<script>
(function() {
    var gate = document.getElementById('quiz-fs-gate');
    var gateBtn = document.getElementById('quiz-fs-gate-btn');
    var gateHint = document.getElementById('quiz-fs-gate-hint');
    var startBtn = document.getElementById('start-quiz-btn');
    var startForm = document.getElementById('quiz-start-form');

    function isFullscreenOrMaximized() {
        if (window.QuizSnapWindowState && window.QuizSnapWindowState.isFullscreenOrMaximized) {
            return window.QuizSnapWindowState.isFullscreenOrMaximized();
        }
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            return true;
        }
        if (!window.screen) return false;
        var tol = 100;
        return window.outerWidth >= window.screen.availWidth - tol && window.outerHeight >= window.screen.availHeight - tol;
    }

    function requestFullscreen() {
        var el = document.documentElement;
        var fn = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
        if (!fn) {
            return Promise.reject(new Error('unsupported'));
        }
        return fn.call(el);
    }

    function unlockStart() {
        if (startBtn) {
            startBtn.disabled = false;
        }
        if (gate) {
            gate.classList.add('hidden');
            gate.setAttribute('aria-hidden', 'true');
        }
        if (gateHint) {
            gateHint.classList.remove('hidden');
        }
    }

    function syncGate() {
        if (isFullscreenOrMaximized()) {
            unlockStart();
        }
    }

    if (gateBtn) {
        gateBtn.addEventListener('click', function() {
            requestFullscreen().then(syncGate).catch(function() {
                alert('Could not enter full screen. Use F11 or your browser\'s full screen control, then try again.');
                syncGate();
            });
        });
    }

    if (startForm) {
        startForm.addEventListener('submit', function(e) {
            if (!isFullscreenOrMaximized()) {
                e.preventDefault();
                if (gate) {
                    gate.classList.remove('hidden');
                    gate.setAttribute('aria-hidden', 'false');
                }
                alert('Please enter full screen before starting the quiz.');
            }
        });
    }

    document.addEventListener('fullscreenchange', syncGate);
    document.addEventListener('webkitfullscreenchange', syncGate);
    window.addEventListener('resize', syncGate);
    syncGate();
})();
</script>
@endpush
