/**
 * QuizSnap live updates — listens for Reverb WebSocket events.
 * Full-page reload only on list/dashboard pages; monitoring uses quizsnap-monitoring.js.
 */
(function () {
    'use strict';

    var reloadTimer = null;

    function scheduleReload(delayMs) {
        if (reloadTimer) return;
        reloadTimer = setTimeout(function () {
            reloadTimer = null;
            window.location.reload();
        }, delayMs || 350);
    }

    function isStudentDashboardPage() {
        return !!document.getElementById('student-dashboard-wrap');
    }

    function pageConfig() {
        var path = String(window.location.pathname || '');

        // Enterprise centers use their own Reverb listeners — avoid full-page reload loops here.
        if (/\/dashboard\/(monitoring|operations|intelligence)(\/|$)/.test(path)) {
            return null;
        }

        // Quiz create/edit and in-page AI generation: never auto-reload (would interrupt generation UI).
        if (/\/dashboard\/quizzes\/(create|edit)(\/|$)/.test(path)) {
            return null;
        }
        if (document.querySelector('[data-quizsnap-skip-live-reload]')) {
            return null;
        }

        // Quiz detail tabs (overview, sessions, scores): never full-page reload.
        // Examiners stay on the sessions table; live updates belong on monitoring dashboards.
        if (/\/dashboard\/quizzes\/\d+(\/|$)/.test(path)) {
            return null;
        }

        // Student dashboard (home, quizzes, profile, etc.): never full-page reload.
        // Partial updates are handled in layouts/student-dashboard via registerRefresher.
        if (isStudentDashboardPage()) {
            return null;
        }
        if (/\/dashboard\/quizzes\/?$/.test(path)) {
            return { reloadTypes: ['quizzes', 'dashboard'] };
        }
        if (/\/dashboard\/class-groups/.test(path)) {
            return { reloadTypes: ['class-groups', 'dashboard'] };
        }
        if (/\/dashboard\/?$/.test(path)) {
            return { reloadTypes: ['dashboard', 'quizzes', 'class-groups'] };
        }
        if (path.indexOf('/dashboard') === 0) {
            return { reloadTypes: ['dashboard', 'quizzes', 'class-groups'] };
        }

        return null;
    }

    window.QuizSnapLive = window.QuizSnapLive || {
        refreshers: [],
        registerRefresher: function (fn) {
            if (typeof fn === 'function') {
                this.refreshers.push(fn);
            }
        },
    };

    window.addEventListener('quizsnap-data-updated', function (event) {
        var detail = (event && event.detail) ? event.detail : {};
        var type = String(detail.type || '').toLowerCase();
        var config = pageConfig();

        if (config && config.reloadTypes.indexOf(type) !== -1) {
            scheduleReload(350);
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
