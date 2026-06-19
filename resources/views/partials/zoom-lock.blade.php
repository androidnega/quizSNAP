{{-- Prevent pinch/double-tap/keyboard zoom across the app --}}
<style id="quizsnap-zoom-lock">
    html {
        touch-action: manipulation;
        -ms-touch-action: manipulation;
        text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
    }
</style>
<script>
(function () {
    var block = function (event) { event.preventDefault(); };
    document.addEventListener('gesturestart', block, { passive: false });
    document.addEventListener('gesturechange', block, { passive: false });
    document.addEventListener('gestureend', block, { passive: false });
    document.addEventListener('wheel', function (event) {
        if (event.ctrlKey) event.preventDefault();
    }, { passive: false });
    document.addEventListener('keydown', function (event) {
        if (!(event.ctrlKey || event.metaKey)) return;
        var key = event.key;
        if (key === '+' || key === '-' || key === '=' || key === '0' || key === '_') {
            event.preventDefault();
        }
    });
})();
</script>
