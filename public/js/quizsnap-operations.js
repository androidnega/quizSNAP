/**
 * QuizSnap Operations Center — real-time updates via Reverb.
 */
(function () {
    'use strict';

    function operationsChannel() {
        return window.QuizSnapReverb && window.QuizSnapReverb.operationsChannel
            ? window.QuizSnapReverb.operationsChannel()
            : 'private-quizsnap-operations';
    }

    function bindOperationsChannel() {
        if (!window.QuizSnapReverb || !window.QuizSnapReverb.isEnabled()) {
            return;
        }

        var handlers = {
            OperationsCommandCenterUpdated: updateCommandCenter,
            OperationsLiveExamsUpdated: updateLiveExams,
            OperationsStudentsUpdated: updateStudents,
            OperationsProctoringUpdated: updateProctoring,
            OperationsAttendanceUpdated: updateAttendance,
            OperationsAlertCreated: refreshCommandCenter,
        };

        Object.keys(handlers).forEach(function (eventName) {
            window.QuizSnapReverb.bind(operationsChannel(), eventName, handlers[eventName]);
        });
    }

    function updateStatElements(prefix, data) {
        Object.keys(data || {}).forEach(function (key) {
            document.querySelectorAll('[data-' + prefix + '="' + key + '"]').forEach(function (el) {
                if (data[key] !== undefined && data[key] !== null) {
                    el.textContent = typeof data[key] === 'number'
                        ? Number(data[key]).toLocaleString()
                        : String(data[key]);
                }
            });
        });
    }

    function updateCommandCenter(payload) {
        if (!document.getElementById('operations-command-center-root') && !document.getElementById('operations-wallboard-root')) {
            return;
        }
        updateStatElements('operations-stat', payload);
    }

    function refreshCommandCenter() {
        var page = document.querySelector('[data-operations-page]');
        if (!page) return;
        var path = page.getAttribute('data-operations-page') === 'wallboard'
            ? '/dashboard/operations/wallboard/live'
            : '/dashboard/operations/live';
        fetch(path, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(updateCommandCenter)
            .catch(function () {});
    }

    function updateLiveExams(payload) {
        if (!document.getElementById('operations-live-exams-root')) return;
        updateStatElements('operations-live-exam', payload.summary || {});
    }

    function updateStudents(payload) {
        if (!document.getElementById('operations-students-root')) return;
        updateStatElements('operations-students', payload.summary || {});
    }

    function updateProctoring(payload) {
        if (!document.getElementById('operations-proctoring-root')) return;
        updateStatElements('operations-proctoring', payload.summary || {});
    }

    function updateAttendance(payload) {
        if (!document.getElementById('operations-attendance-root')) return;
        updateStatElements('operations-attendance', payload);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.OPERATIONS_ACCESS) {
            bindOperationsChannel();
        }
    });

    window.QuizSnapOperations = {
        refresh: refreshCommandCenter,
    };
})();
