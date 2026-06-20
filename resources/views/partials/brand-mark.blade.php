@php
    $size = $size ?? 'md';
    $markClass = 'quizsnap-brand-mark quizsnap-brand-mark--' . $size;
@endphp
<span class="{{ $markClass }}" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" role="img" aria-label="">
        <rect width="32" height="32" rx="8" fill="currentColor"/>
        <text x="16" y="22" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="800" fill="#fff" text-anchor="middle">Q</text>
    </svg>
</span>
