@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $showSplit = $showSplit ?? str_contains(strtolower((string) $appName), 'quiz');
    $size = $size ?? 'md';
    $class = trim(($class ?? '') . ' ' . ($size === 'lg' ? 'text-2xl' : ($size === 'sm' ? 'text-lg' : 'text-xl')) . ' font-extrabold tracking-tight font-display no-underline');
@endphp
@if($showSplit)
    <span class="{{ $class }}">
        <span class="theme-wordmark-a">Quiz</span><span class="theme-wordmark-b">Snap</span>
    </span>
@else
    <span class="{{ $class }} theme-wordmark-a">{{ $appName }}</span>
@endif
