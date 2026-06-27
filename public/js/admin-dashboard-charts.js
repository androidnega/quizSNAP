/**
 * Super Admin dashboard — platform trend charts (Chart.js).
 */
(function () {
    'use strict';

    var cfg = window.AdminDashboardChartsConfig || {};
    var charts = {};
    var palette = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'];

    function baseOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 8, font: { size: 10 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148,163,184,0.2)' },
                    ticks: { font: { size: 10 } },
                },
            },
        };
    }

    function destroyAll() {
        Object.keys(charts).forEach(function (key) {
            if (charts[key]) {
                charts[key].destroy();
                charts[key] = null;
            }
        });
    }

    function renderLine(id, labels, values, color) {
        var canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: color || palette[0],
                    backgroundColor: (color || palette[0]) + '22',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    borderWidth: 2,
                }],
            },
            options: baseOptions(),
        });
    }

    function renderBar(id, labels, values, color) {
        var canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: (color || palette[1]) + 'cc',
                    borderRadius: 6,
                    maxBarThickness: 28,
                }],
            },
            options: baseOptions(),
        });
    }

    function renderPie(id, labels, values) {
        var canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: palette.slice(0, labels.length),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 } },
                    },
                },
            },
        });
    }

    function renderInsights(list) {
        var el = document.getElementById('dashboard-insights-list');
        if (!el || !Array.isArray(list)) return;
        el.innerHTML = '';
        list.forEach(function (text) {
            var li = document.createElement('li');
            li.textContent = text;
            el.appendChild(li);
        });
    }

    function applyData(data) {
        if (!data) return;
        renderLine('chart-quiz-activity', data.quiz_activity.labels, data.quiz_activity.values, palette[0]);
        renderLine('chart-exam-submissions', data.exam_submissions.labels, data.exam_submissions.values, palette[2]);
        renderBar('chart-student-growth', data.student_growth.labels, data.student_growth.values, palette[1]);
        renderBar('chart-live-support', data.live_support.labels, data.live_support.values, palette[4]);
        renderLine('chart-avg-scores', data.avg_exam_scores.labels, data.avg_exam_scores.values, palette[5]);
        renderPie('chart-staff-roles', data.staff_roles.labels, data.staff_roles.values);
        renderPie('chart-quiz-outcomes', data.quiz_outcomes.labels, data.quiz_outcomes.values);
        renderPie('chart-support-status', data.support_status.labels, data.support_status.values);
        renderInsights(data.insights);
    }

    function load(period) {
        var url = (cfg.url || '/dashboard/charts') + '?period=' + encodeURIComponent(period || '30d');
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success && res.charts) applyData(res.charts);
            })
            .catch(function () {});
    }

    function init() {
        var select = document.getElementById('dashboard-chart-period');
        var period = select ? select.value : '30d';
        load(period);
        if (select) {
            select.addEventListener('change', function () {
                destroyAll();
                load(select.value);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
