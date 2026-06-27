@php
    $supportContext = $supportContext ?? [];
    if (! empty($supportPage ?? null)) {
        $supportContext['page'] = $supportPage;
    }
    $fabWrapClass = trim('qs-support-fab-wrap ' . ($fabWrapClass ?? ''));
@endphp
<div class="{{ $fabWrapClass }}" id="qs-support-fab-wrap">
    <button type="button" class="qs-support-live-toggle" id="qs-support-live-toggle" aria-label="Open live chat with support">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span class="qs-support-live-toggle__label">Live chat</span>
    </button>
</div>
<script>
(function() {
    var btn = document.getElementById('qs-support-live-toggle');
    if (!btn) return;
    var cfg = window.QuizSnapSupportConfig || {};
    var ctx = cfg.defaultContext || {};
    btn.addEventListener('click', function() {
        if (!window.QuizSnapLiveSupport) return;
        window.QuizSnapLiveSupport.open({
            student_index: ctx.index_number || null,
            student_name: ctx.name || null,
            student_phone: ctx.phone || null,
            student_email: ctx.email || null,
            page_url: ctx.page || window.location.pathname,
            issue_category: 'general',
        });
    });
})();
</script>
