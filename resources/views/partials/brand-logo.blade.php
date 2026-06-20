@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $size = $size ?? 'md';
    $variant = $variant ?? 'default';
    $href = $href ?? null;
    $showMark = $showMark ?? true;
    $surfaceClass = $variant === 'default' ? ' quizsnap-brand-logo--surface' : '';
    $class = trim('quizsnap-brand-logo' . $surfaceClass . ' ' . ($class ?? ''));
    $tag = $href ? 'a' : 'span';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" aria-label="{{ $appName }} home" @endif class="{{ $class }}" @if($href && !$showMark) title="{{ $appName }}" @endif>
    @if($showMark)
        @include('partials.brand-mark', ['size' => $size])
    @endif
    @include('partials.brand-wordmark', [
        'appName' => $appName,
        'size' => $size,
        'variant' => $variant,
        'class' => 'quizsnap-brand-logo__wordmark',
    ])
</{{ $tag }}>
