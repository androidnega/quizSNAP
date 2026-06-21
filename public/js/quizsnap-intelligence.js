/**
 * QuizSnap Intelligence Center — realtime updates via Reverb.
 */
(function () {
    'use strict';

    function channel() {
        return window.QuizSnapReverb && window.QuizSnapReverb.intelligenceChannel
            ? window.QuizSnapReverb.intelligenceChannel()
            : 'private-quizsnap-intelligence';
    }

    function bindChannel() {
        if (!window.QuizSnapReverb || !window.QuizSnapReverb.isEnabled()) return;

        var handlers = {
            IntelligenceDashboardUpdated: updateDashboard,
            IntelligenceWarningCreated: refreshDashboard,
            IntelligenceRecommendationCreated: refreshDashboard,
            IntelligenceRiskChanged: updateRisk,
        };

        Object.keys(handlers).forEach(function (eventName) {
            window.QuizSnapReverb.bind(channel(), eventName, handlers[eventName]);
        });
    }

    function updateDashboard(payload) {
        if (!document.querySelector('[data-intelligence-page]')) return;
        Object.keys(payload || {}).forEach(function (key) {
            document.querySelectorAll('[data-intelligence-stat="' + key + '"]').forEach(function (el) {
                if (payload[key] !== undefined && payload[key] !== null) {
                    el.textContent = typeof payload[key] === 'number'
                        ? Number(payload[key]).toLocaleString()
                        : String(payload[key]);
                }
            });
        });
    }

    function updateRisk(payload) {
        updateDashboard(payload);
    }

    function refreshDashboard() {
        fetch('/dashboard/intelligence/live', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (r) { return r.json(); }).then(updateDashboard).catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.INTELLIGENCE_ACCESS) bindChannel();
    });

    window.QuizSnapIntelligence = { refresh: refreshDashboard };
})();
