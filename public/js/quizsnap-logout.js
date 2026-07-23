(function () {
    'use strict';

    function markSubmitting(form) {
        if (!form || form.dataset.quizsnapLogoutSubmitting === '1') {
            return false;
        }
        form.dataset.quizsnapLogoutSubmitting = '1';

        var btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            var label = btn.querySelector('[data-quizsnap-logout-label]');
            if (!label) {
                label = btn;
            }
            if (!btn.dataset.quizsnapLogoutOriginalText) {
                btn.dataset.quizsnapLogoutOriginalText = label.textContent || '';
            }
            label.textContent = 'Signing out…';
        }

        return true;
    }

    document.addEventListener(
        'click',
        function (e) {
            var btn = e.target && e.target.closest && e.target.closest('[data-quizsnap-logout-form] button[type="submit"]');
            if (!btn) {
                return;
            }
            e.stopPropagation();
        },
        true
    );

    document.addEventListener(
        'submit',
        function (e) {
            var form = e.target;
            if (!form || !form.matches || !form.matches('[data-quizsnap-logout-form]')) {
                return;
            }
            if (form.dataset.quizsnapLogoutSubmitting === '1') {
                e.preventDefault();
                return;
            }
            markSubmitting(form);
        },
        true
    );
})();
