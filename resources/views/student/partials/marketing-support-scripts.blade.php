<script>
(function() {
    var wrap = document.getElementById('qs-support-fab-wrap');
    var fab = document.getElementById('qs-support-fab');
    var toggle = document.getElementById('qs-support-toggle');
    var menu = document.getElementById('qs-support-menu');
    var backdrop = document.getElementById('qs-support-backdrop');
    if (!wrap || !fab || !toggle || !menu) return;

    function setOpen(open) {
        fab.classList.toggle('is-open', open);
        wrap.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close support options' : 'Get in touch with support');
        menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        setOpen(!fab.classList.contains('is-open'));
    });

    if (backdrop) {
        backdrop.addEventListener('click', function() { setOpen(false); });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') setOpen(false);
    });
})();
</script>
