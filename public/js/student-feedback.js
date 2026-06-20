/**
 * Plain-language error toasts for students (no technical jargon).
 */
(function () {
    function ensureToastEl() {
        var el = document.getElementById('quizsnap-student-toast');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'quizsnap-student-toast';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.style.cssText = [
            'position:fixed',
            'bottom:max(1rem,env(safe-area-inset-bottom))',
            'left:50%',
            'transform:translateX(-50%)',
            'z-index:99999',
            'max-width:min(92vw,24rem)',
            'padding:0.75rem 1rem',
            'border-radius:0.75rem',
            'background:#991b1b',
            'color:#fff',
            'font-size:0.875rem',
            'font-weight:500',
            'box-shadow:0 8px 24px rgba(0,0,0,0.2)',
            'opacity:0',
            'transition:opacity 0.2s ease',
            'pointer-events:none',
            'text-align:center',
            'line-height:1.35',
        ].join(';');
        document.body.appendChild(el);
        return el;
    }

    window.QuizSnapStudentFeedback = {
        show: function (message, opts) {
            message = message || 'Something went wrong. Please try again.';
            opts = opts || {};
            var code = opts.code ? String(opts.code) : '';
            var el = ensureToastEl();
            el.textContent = code ? message + ' (Ref: ' + code + ')' : message;
            el.style.opacity = '1';
            clearTimeout(el._hideTimer);
            el._hideTimer = setTimeout(function () {
                el.style.opacity = '0';
            }, opts.duration || 6500);
        },
        connectionError: function () {
            this.show('We could not reach the server. Check your connection and try again.', { code: 'NET' });
        },
        saveError: function () {
            this.show('We could not save your answer. It is kept on this device — please try again.', { code: 'SAVE' });
        },
        violationError: function () {
            this.show('We could not record a proctoring alert. Your quiz is still active.', { code: 'PROC' });
        },
    };
})();
