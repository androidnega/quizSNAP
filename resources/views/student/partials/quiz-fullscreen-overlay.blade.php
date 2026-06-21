@php
    $mode = $mode ?? 'quiz';
    $isReady = $mode === 'ready';
    $overlayId = $isReady ? 'quiz-fs-gate' : 'resize-blur-overlay';
    $titleTag = $isReady ? 'h2' : 'h4';
    $titleId = $isReady ? null : 'resize-blur-title';
    $messageId = $isReady ? null : 'resize-blur-message';
    $btnId = $isReady ? 'quiz-fs-gate-btn' : 'resize-blur-enter-fs-btn';
    $overlayClasses = $isReady
        ? 'fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-95 px-4'
        : 'hidden fixed inset-0 z-[100] items-center justify-center bg-gray-900/95 px-4 pointer-events-auto';
    $cardClasses = $isReady
        ? 'max-w-md w-full bg-white border border-gray-200 rounded-xl p-6 shadow-lg text-center'
        : 'bg-white border border-gray-200 rounded-2xl shadow-xl p-6 max-w-md w-full text-center';
    $readyMessage = 'Before you start, your browser must be in <strong>full screen mode</strong> (tabs and address bar hidden). Click the button below and allow full screen when prompted.';
    $quizMessage = 'Your quiz runs in browser full screen so tabs and the address bar are hidden. Click below and choose <strong>Allow</strong> when your browser asks.';
@endphp

<div id="{{ $overlayId }}" class="{{ $overlayClasses }}" aria-hidden="{{ $isReady ? 'false' : 'true' }}">
    <div class="{{ $cardClasses }}">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full {{ $isReady ? 'bg-sky-50' : 'bg-primary-50' }} flex items-center justify-center">
            <i class="fas fa-expand text-2xl {{ $isReady ? 'text-sky-600' : 'text-primary-600' }}" aria-hidden="true"></i>
        </div>
        @if($titleId)
            <{{ $titleTag }} id="{{ $titleId }}" class="text-lg font-bold text-gray-900 mb-2">Full screen required</{{ $titleTag }}>
        @else
            <{{ $titleTag }} class="text-lg font-bold text-gray-900 mb-2">Full screen required</{{ $titleTag }}>
        @endif
        @if($messageId)
            <p id="{{ $messageId }}" class="text-sm text-gray-600 mb-5">{!! $quizMessage !!}</p>
        @else
            <p class="text-sm text-gray-600 mb-5">{!! $readyMessage !!}</p>
        @endif
        <button type="button" id="{{ $btnId }}" class="btn btn-primary w-full py-2.5 px-5 text-sm font-semibold text-white border-0 mb-3">
            Enter full screen
        </button>
        @unless($isReady)
            <p id="resize-blur-warning" class="text-sm font-medium text-amber-700 mb-3 hidden">Repeated violations will result in auto-submission of your quiz.</p>
            <p id="resize-blur-final-warning" class="text-sm font-bold text-red-600 mb-3 hidden">One more resize or exit from full screen will auto-submit your quiz.</p>
        @endunless
        @if($isReady)
            <p id="quiz-fs-gate-hint" class="mt-3 text-xs text-gray-500 hidden">Full screen active. You can start the quiz below.</p>
        @endif
    </div>
</div>
