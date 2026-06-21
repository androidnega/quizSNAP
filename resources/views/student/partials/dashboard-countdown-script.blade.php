@if($student && isset($scheduledQuiz) && $scheduledQuiz && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
@push('scripts')
<script>
(function() {
    var startsAt = @json($scheduledQuiz->starts_at->toIso8601String());
    var startMs = new Date(startsAt).getTime();
    var countdownNodes = document.querySelectorAll('[data-quiz-countdown="{{ $scheduledQuiz->id }}"]');
    if (!countdownNodes.length) return;
    var mobilePanel = document.querySelector('.glance-mobile-quiz-panel--countdown');
    var mobileCta = mobilePanel && mobilePanel.querySelector('.glance-mobile-quiz-panel__cta');
    var modernCard = document.querySelector('.md-dash__course-card--countdown');
    var featuredCard = document.querySelector('.sd-featured-quiz__card--countdown');
    var cardLink = document.querySelector('.glance-card--emerald .glance-card__body[data-rules-url]')
        || document.querySelector('.glance-card--emerald .glance-card__body');
    var rulesUrl = (featuredCard && featuredCard.getAttribute('data-rules-url'))
        || (modernCard && modernCard.getAttribute('data-rules-url'))
        || (mobilePanel && mobilePanel.getAttribute('data-rules-url'))
        || (cardLink && cardLink.getAttribute('data-rules-url'));

    function formatCountdown(totalSeconds) {
        var h = Math.floor(totalSeconds / 3600);
        var m = Math.floor((totalSeconds % 3600) / 60);
        var s = totalSeconds % 60;
        if (h > 0) {
            return h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function setStartState() {
        var label = 'Start quiz';
        countdownNodes.forEach(function(node) {
            if (node.classList.contains('sd-featured-quiz__countdown')) {
                return;
            }
            node.textContent = label;
            node.classList.remove('glance-card__cta--countdown', 'md-dash__course-cta--countdown');
            node.classList.add('glance-card__cta--start', 'md-dash__course-cta--start');
        });
        if (mobileCta) {
            mobileCta.textContent = label;
            mobileCta.classList.remove('glance-mobile-quiz-panel__cta--countdown');
            mobileCta.classList.add('glance-mobile-quiz-panel__cta--start');
        }
        if (mobilePanel) {
            mobilePanel.classList.add('is-ready');
            mobilePanel.classList.remove('glance-mobile-quiz-panel--countdown');
            mobilePanel.classList.add('glance-mobile-quiz-panel--start');
            if (rulesUrl) {
                mobilePanel.href = rulesUrl;
            }
        }
        if (modernCard) {
            modernCard.classList.add('is-ready');
            modernCard.classList.remove('md-dash__course-card--countdown');
            modernCard.classList.add('md-dash__course-card--ready');
            if (rulesUrl) {
                modernCard.href = rulesUrl;
            }
        }
        if (cardLink && rulesUrl) {
            cardLink.href = rulesUrl;
        }
        if (featuredCard) {
            featuredCard.classList.remove('sd-featured-quiz__card--countdown');
            featuredCard.classList.add('sd-featured-quiz__card--ready', 'is-ready');
            if (rulesUrl) {
                featuredCard.href = rulesUrl;
            }
        }
    }

    function update() {
        var now = Date.now();
        var left = Math.max(0, Math.floor((startMs - now) / 1000));
        if (left <= 0) {
            setStartState();
            return;
        }
        var text = 'Starts in ' + formatCountdown(left);
        var featuredTime = formatCountdown(left);
        countdownNodes.forEach(function(node) {
            if (node.classList.contains('sd-featured-quiz__countdown')) {
                node.textContent = featuredTime;
            } else {
                node.textContent = text;
            }
        });
        if (mobileCta) {
            var mobileCountdown = mobileCta.querySelector('[data-quiz-countdown="{{ $scheduledQuiz->id }}"]');
            if (mobileCountdown) {
                mobileCountdown.textContent = text;
            }
        }
    }

    update();
    setInterval(update, 1000);
})();
</script>
@endpush
@endif
