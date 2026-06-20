@extends('layouts.student')

@section('title', 'Identity check - ' . $quiz->title)
@section('body_class', 'bg-offwhite theme-bg')

@section('content')
<div id="proctoring-capture-root" class="min-h-[100dvh] w-full flex flex-col">
    <div id="proctoring-capture-main" class="flex flex-1 flex-col items-center justify-start overflow-auto min-h-0 w-full px-4 py-6 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.25rem,env(safe-area-inset-bottom))]">
        <div class="max-w-md mx-auto w-full space-y-4">
            <div class="text-center">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-1">{{ $quiz->course->name ?? 'Quiz' }}</p>
                <h1 class="text-lg font-display font-bold text-gray-900">Quick face check</h1>
                <p class="text-gray-600 text-sm mt-1">Tap <span class="font-semibold text-gray-800">Start camera</span> and keep your face inside the circle.</p>
            </div>

            {{-- Inline verified state (not a modal) --}}
            <div id="face-verified-panel" class="hidden rounded-xl border border-primary-200 bg-primary-50/70 px-4 py-3.5 flex items-center gap-3" role="status" aria-live="polite">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="min-w-0 text-left">
                    <p id="face-verified-title" class="text-sm font-semibold text-gray-900">Face verified</p>
                    <p class="text-xs text-gray-600">Starting your quiz…</p>
                </div>
            </div>

            <div id="capture-guidance" class="rounded-lg border border-primary-100 bg-primary-50/50 p-3">
                <p class="text-xs text-gray-700 flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 text-primary-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span><span class="font-semibold text-gray-900">Allow camera access</span> when your browser asks. This takes only a moment.</span>
                </p>
            </div>

            <div id="capture-card" class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                <p class="text-xs text-primary-600 font-medium mb-2">Camera preview</p>
                <div id="video-container" class="relative bg-gray-900 rounded-xl overflow-hidden border-2 border-gray-200 mx-auto transition-all duration-300" style="max-width: 280px; aspect-ratio: 4/3; min-height: 200px;">
                    <video id="camera-video" autoplay playsinline muted class="w-full h-full object-contain"></video>
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute inset-0 border-2 border-primary-500/20 rounded-xl"></div>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                            <div class="w-32 h-40 border-2 border-primary-400/40 rounded-full"></div>
                        </div>
                    </div>
                    <div id="camera-loading" class="absolute inset-0 hidden items-center justify-center bg-gray-900">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-white mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-white text-xs">Starting camera...</p>
                        </div>
                    </div>
                </div>
                <canvas id="capture-canvas" class="hidden"></canvas>
            </div>

            <div id="capture-error" class="hidden">
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 flex items-start gap-2">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-red-800 text-sm mb-0.5">Camera error</h4>
                        <p id="capture-error-text" class="text-xs text-red-700"></p>
                    </div>
                </div>
            </div>

            <div id="face-check-status" class="face-status-pending rounded-xl border px-3 py-2.5">
                <p id="face-check-status-text" class="text-xs">Getting camera ready…</p>
            </div>

            <div id="capture-actions">
                <button type="button" class="btn btn-primary w-full py-2.5 px-4 text-sm font-semibold text-white border-0" id="capture-btn">
                    <span id="capture-btn-text">Start camera</span>
                </button>
            </div>

            <p class="text-center text-[11px] text-gray-500">
                <svg class="w-3.5 h-3.5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                Your photo is used only for this exam and stored securely.
            </p>
        </div>
    </div>
</div>

<style>
#proctoring-capture-root .face-status-pending {
    border-color: #fecaca;
    background-color: #fef2f2;
    color: #991b1b;
}
#proctoring-capture-root .face-status-ok {
    border-color: #86efac;
    background-color: #f0fdf4;
    color: #166534;
}
#proctoring-capture-root .face-status-error {
    border-color: #fecaca;
    background-color: #fef2f2;
    color: #991b1b;
}
#proctoring-capture-root #video-container.face-frame-ok {
    border-color: #22c55e !important;
    border-width: 3px;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.25);
}
#proctoring-capture-root #video-container.face-frame-error {
    border-color: #ef4444 !important;
    border-width: 3px;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}
#proctoring-capture-root #capture-btn.capture-btn--ready {
    background-color: #16a34a !important;
    border-color: #15803d !important;
    color: #fff !important;
}
#proctoring-capture-root #capture-btn.capture-btn--ready:hover:not(:disabled) {
    background-color: #15803d !important;
}
#proctoring-capture-root #capture-btn.capture-btn--waiting {
    background-color: #dc2626 !important;
    border-color: #b91c1c !important;
    color: #fff !important;
    opacity: 0.92;
}
#proctoring-capture-root #capture-btn.capture-btn--neutral {
    background-color: var(--theme-primary-600, #2563eb);
    border-color: transparent;
    color: #fff;
}
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.10.0/dist/tf.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.1.0/dist/blazeface.min.umd.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/proctoring-capture.js') }}?v={{ filemtime(public_path('js/proctoring-capture.js')) }}" defer></script>
<script>
window.QuizSnapProctoring = {
    quizId: {{ $quiz->id }},
    indexNumber: "{{ $indexNumber }}",
    storeUrl: "{{ route('student.proctoring.store') }}",
    csrfToken: "{{ csrf_token() }}"
};
</script>
@endpush
@endsection
