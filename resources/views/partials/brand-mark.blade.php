@php
    $size = $size ?? 'md';
    $variant = $variant ?? 'default';
    $plain = $variant === 'plain';
    $markClass = 'quizsnap-brand-mark quizsnap-brand-mark--' . $size . ($plain ? ' quizsnap-brand-mark--plain' : '');
@endphp
@if($plain)
<span class="{{ $markClass }}" aria-hidden="true">Q</span>
@else
<span class="{{ $markClass }}" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" role="img" aria-label="">
        <rect class="quizsnap-brand-mark__bg" width="32" height="32" rx="8"/>
        <text class="quizsnap-brand-mark__letter" x="16" y="22" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="800" text-anchor="middle">Q</text>
    </svg>
</span>
@endif
