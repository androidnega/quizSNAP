@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $showSplit = $showSplit ?? str_contains(strtolower((string) $appName), 'quiz');
    $size = $size ?? 'md';
    $variant = $variant ?? 'default';
    $sizeClass = $size === 'lg' ? 'quizsnap-wordmark--lg' : ($size === 'sm' ? 'quizsnap-wordmark--sm' : 'quizsnap-wordmark--md');
    $class = trim(($class ?? '') . ' quizsnap-wordmark quizsnap-wordmark--' . $variant . ' ' . $sizeClass . ' font-display no-underline');
@endphp
@if($showSplit)
    <span class="{{ $class }}">
        <span class="quizsnap-wordmark-a theme-wordmark-a">Quiz</span><span class="quizsnap-wordmark-b theme-wordmark-b">Snap</span>
    </span>
@else
    <span class="{{ $class }} theme-wordmark-a">{{ $appName }}</span>
@endif
