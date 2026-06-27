/**
 * QuizSnap live updates — Reverb WebSocket → partial refreshers only (no full-page reload).
 */
(function () {
    'use strict';

    function isStudentDashboardPage() {
        return !!document.getElementById('student-dashboard-wrap');
    }

    /**
     * True when the user is typing, chatting, or has a modal open — refreshers should defer.
     */
    function isUserInteracting() {
        var el = document.activeElement;
        if (el) {
            var tag = String(el.tagName || '').toUpperCase();
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                return true;
            }
            if (el.isContentEditable) {
                return true;
            }
        }
        if (document.querySelector('.qs-live-support-panel.is-open')) {
            return true;
        }
        if (document.querySelector('#staff-support-fab-wrap.is-open')) {
            return true;
        }
        if (document.querySelector('.qs-support-modal.is-open')) {
            return true;
        }
        if (document.querySelector('[data-quizsnap-user-active="1"]')) {
            return true;
        }

        return false;
    }

    window.QuizSnapLive = window.QuizSnapLive || {
        refreshers: [],
        registerRefresher: function (fn) {
            if (typeof fn === 'function') {
                this.refreshers.push(fn);
            }
        },
        isUserInteracting: isUserInteracting,
        isStudentDashboardPage: isStudentDashboardPage,
    };

    window.addEventListener('quizsnap-data-updated', function (event) {
        var detail = (event && event.detail) ? event.detail : {};
        var type = String(detail.type || '').toLowerCase();

        if (isUserInteracting()) {
            return;
        }

        window.QuizSnapLive.refreshers.forEach(function (fn) {
            try {
                fn(type, detail);
            } catch (e) {
                /* ignore listener errors */
            }
        });
    });
})();
