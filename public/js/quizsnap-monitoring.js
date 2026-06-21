/**
 * QuizSnap Monitoring Center — real-time dashboard updates via Reverb.
 */
(function () {
    'use strict';

    function monitoringChannel() {
        return window.QuizSnapReverb && window.QuizSnapReverb.monitoringChannel
            ? window.QuizSnapReverb.monitoringChannel()
            : 'private-quizsnap-monitoring';
    }

    function bindMonitoringChannel() {
        if (!window.QuizSnapReverb || !window.QuizSnapReverb.isEnabled()) {
            return;
        }

        var handlers = {
            MonitoringErrorOccurred: prependErrorFeed,
            MonitoringActivityLogged: prependActivity,
            MonitoringHealthChanged: refreshLiveStats,
            MonitoringQueueChanged: refreshLiveStats,
            MonitoringNotificationCreated: refreshNotifications,
            MonitoringSecurityEventOccurred: refreshLiveStats,
            MonitoringSlowQueryDetected: refreshLiveStats,
            MonitoringLiveQuizUpdated: updateLiveQuiz,
            MonitoringLiveAttendanceUpdated: updateLiveAttendance,
            MonitoringCommandCenterUpdated: updateCommandCenter,
        };

        Object.keys(handlers).forEach(function (eventName) {
            window.QuizSnapReverb.bind(monitoringChannel(), eventName, handlers[eventName]);
        });
    }

    function refreshLiveStats() {
        if (!document.querySelector('[data-monitoring-page]')) {
            return;
        }

        fetch('/dashboard/monitoring/live-stats', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(updateStatCards)
            .catch(function () {});
    }

    function updateStatCards(data) {
        var map = {
            'errors-today': data.errors_today,
            'critical-errors': data.critical_errors,
            'failed-jobs': data.failed_jobs,
            'active-users': data.active_users,
            'security-alerts': data.security_alerts,
            'api-requests': data.api_requests_today,
            'live-quiz-takers': data.live_quiz_takers,
            'queue-pending': data.queue ? data.queue.pending : 0,
        };

        Object.keys(map).forEach(function (key) {
            var el = document.querySelector('[data-monitoring-stat="' + key + '"] .text-2xl');
            if (el && map[key] !== undefined) {
                el.textContent = Number(map[key]).toLocaleString();
            }
        });
    }

    function prependErrorFeed(payload) {
        var feed = document.getElementById('monitoring-error-feed') || document.getElementById('monitoring-recent-errors');
        if (!feed || !payload) return;
        var item = document.createElement('div');
        item.className = 'rounded-xl border border-red-200 bg-red-50 p-3 shadow-sm';
        item.innerHTML = '<div class="flex justify-between gap-2"><span class="text-xs font-semibold uppercase text-red-600">' + escapeHtml(payload.severity || 'error') + '</span><span class="text-xs text-gray-500">just now</span></div><p class="mt-1 text-sm text-gray-900">' + escapeHtml(payload.message || '') + '</p>';
        feed.prepend(item);
    }

    function prependActivity(payload) {
        var feed = document.getElementById('monitoring-activity-feed') || document.getElementById('monitoring-live-events');
        if (!feed || !payload) return;
        var item = document.createElement('div');
        item.className = 'rounded-xl border border-gray-200 bg-white p-3 shadow-sm';
        item.innerHTML = '<p class="text-sm"><strong>' + escapeHtml(payload.user_name || 'System') + '</strong> — ' + escapeHtml(payload.action || '') + '</p>';
        feed.prepend(item);
    }

    function refreshNotifications(payload) {
        var badge = document.getElementById('monitoring-notification-badge');
        fetch('/dashboard/monitoring/notifications/recent', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (badge) {
                    badge.textContent = data.unread_count || 0;
                    badge.classList.toggle('hidden', !data.unread_count);
                }
            })
            .catch(function () {});
    }

    function updateLiveQuiz(payload) {
        if (!payload || !document.getElementById('live-quiz-monitor-root')) return;
        Object.keys(payload).forEach(function (key) {
            var el = document.querySelector('[data-live-quiz="' + key + '"]');
            if (el) el.textContent = typeof payload[key] === 'object' ? JSON.stringify(payload[key]) : payload[key];
        });
    }

    function updateLiveAttendance(payload) {
        if (!payload || !document.getElementById('live-attendance-monitor-root')) return;
        Object.keys(payload).forEach(function (key) {
            var el = document.querySelector('[data-live-attendance="' + key + '"]');
            if (el && typeof payload[key] !== 'object') el.textContent = payload[key];
        });
    }

    function updateCommandCenter(payload) {
        if (!payload || !document.getElementById('command-center-root')) return;
        Object.keys(payload).forEach(function (key) {
            var el = document.querySelector('[data-command-center="' + key + '"]');
            if (el && typeof payload[key] !== 'object') el.textContent = payload[key];
        });
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindMonitoringChannel);
    } else {
        bindMonitoringChannel();
    }

    window.QuizSnapMonitoring = { refreshLiveStats: refreshLiveStats, loadCharts: loadCharts };

    function loadCharts(period) {
        if (!window.Chart || !document.getElementById('monitoring-charts-root')) return;
        fetch('/dashboard/monitoring/charts?period=' + encodeURIComponent(period || '24h'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(renderCharts)
            .catch(function () {});
    }

    var chartInstances = {};

    function renderCharts(data) {
        var configs = {
            errorsChart: { key: 'errors', label: 'Errors', color: '#dc2626' },
            requestsChart: { key: 'requests', label: 'Requests', color: '#2563eb' },
            securityChart: { key: 'security', label: 'Security', color: '#9333ea' },
            slowQueriesChart: { key: 'slow_queries', label: 'Slow Queries', color: '#d97706' },
            queueChart: { key: 'queue_jobs', label: 'Queue Jobs', color: '#059669' },
            memoryChart: { key: 'memory', label: 'Memory KB', color: '#0891b2' },
            cpuChart: { key: 'cpu', label: 'CPU %', color: '#ea580c' },
            storageChart: { key: 'storage', label: 'Storage MB', color: '#4b5563' },
            attendanceChart: { key: 'attendance', label: 'Attendance', color: '#16a34a' },
            quizChart: { key: 'quiz', label: 'Quiz Activity', color: '#7c3aed' },
        };

        Object.keys(configs).forEach(function (canvasId) {
            var canvas = document.getElementById(canvasId);
            if (!canvas || !data[configs[canvasId].key]) return;
            if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
            chartInstances[canvasId] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data[configs[canvasId].key].labels || [],
                    datasets: [{
                        label: configs[canvasId].label,
                        data: data[configs[canvasId].key].values || [],
                        borderColor: configs[canvasId].color,
                        backgroundColor: configs[canvasId].color + '33',
                        tension: 0.3,
                        fill: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#6b7280', maxTicksLimit: 8 } },
                        y: { ticks: { color: '#6b7280' }, beginAtZero: true },
                    },
                },
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('monitoring-charts-root') && window.Chart) {
            loadCharts(document.getElementById('monitoring-chart-period')?.value || '24h');
            var periodSelect = document.getElementById('monitoring-chart-period');
            if (periodSelect) {
                periodSelect.addEventListener('change', function () { loadCharts(periodSelect.value); });
            }
        }
    });
})();
