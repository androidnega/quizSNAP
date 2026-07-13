@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $size = $size ?? 'md';
    $variant = $variant ?? 'default';
    $href = $href ?? null;
    $showMark = $showMark ?? true;
    $showWordmark = $showWordmark ?? ($variant !== 'plain');
    $customLogoUrl = $customLogoUrl ?? \App\Models\Setting::institutionLogoUrl();
    $surfaceClass = $variant === 'default' ? ' quizsnap-brand-logo--surface' : '';
    $plainClass = $variant === 'plain' ? ' quizsnap-brand-logo--plain' : '';
    $class = trim('quizsnap-brand-logo' . $surfaceClass . $plainClass . ' ' . ($class ?? ''));
    $tag = $href ? 'a' : 'span';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" aria-label="{{ $appName }} home" @endif class="{{ $class }}" @if($href && !$showMark) title="{{ $appName }}" @endif>
    @if($showMark)
        @if($customLogoUrl !== '')
            <span class="quizsnap-brand-mark quizsnap-brand-mark--{{ $size }} quizsnap-brand-mark--image" aria-hidden="true">
                <img src="{{ $customLogoUrl }}" alt="" class="quizsnap-brand-mark__image">
            </span>
        @else
            @include('partials.brand-mark', ['size' => $size, 'variant' => $variant])
        @endif
    @endif
    @if($showWordmark)
        @include('partials.brand-wordmark', [
            'appName' => $appName,
            'size' => $size,
            'variant' => $variant,
            'class' => 'quizsnap-brand-logo__wordmark',
        ])
    @endif
</{{ $tag }}>
