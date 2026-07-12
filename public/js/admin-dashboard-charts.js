/**
 * Examiner dashboard charts — curved series, scoped to the logged-in examiner.
 */
(function () {
    'use strict';

    var cfg = window.AdminDashboardChartsConfig || {};
    var charts = {};
    var palette = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'];

    function hexToRgba(hex, alpha) {
        var h = (hex || '#4f46e5').replace('#', '');
        if (h.length === 3) {
            h = h.split('').map(function (c) { return c + c; }).join('');
        }
        var n = parseInt(h, 16);
        var r = (n >> 16) & 255;
        var g = (n >> 8) & 255;
        var b = n & 255;
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    /** Chart.js scriptable context → CanvasRenderingContext2D gradient. */
    function areaFill(scriptableCtx, color) {
        var chart = scriptableCtx && scriptableCtx.chart;
        if (!chart) return hexToRgba(color, 0.12);
        var area = chart.chartArea;
        var canvasCtx = chart.ctx;
        if (!area || !canvasCtx || typeof canvasCtx.createLinearGradient !== 'function') {
            return hexToRgba(color, 0.12);
        }
        var gradient = canvasCtx.createLinearGradient(0, area.top, 0, area.bottom);
        gradient.addColorStop(0, hexToRgba(color, 0.28));
        gradient.addColorStop(0.55, hexToRgba(color, 0.08));
        gradient.addColorStop(1, hexToRgba(color, 0.01));
        return gradient;
    }

    function baseOptions(ySuggestedMax) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.92)',
                    titleFont: { size: 11, weight: '600' },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { maxTicksLimit: 7, font: { size: 10 }, color: '#94a3b8' },
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: ySuggestedMax || undefined,
                    grid: { color: 'rgba(148,163,184,0.16)', drawBorder: false },
                    border: { display: false },
                    ticks: { font: { size: 10 }, color: '#94a3b8', padding: 6 },
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

    function normalizeSeries(labels, values) {
        var labs = Array.isArray(labels) ? labels.slice() : [];
        var vals = Array.isArray(values) ? values.slice() : [];
        if (labs.length === 0) {
            labs = ['—'];
            vals = [0];
        }
        while (vals.length < labs.length) vals.push(0);
        return { labels: labs, values: vals.slice(0, labs.length) };
    }

    function renderCurve(id, labels, values, color, ySuggestedMax) {
        var canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        if (charts[id]) charts[id].destroy();
        var series = normalizeSeries(labels, values);
        var stroke = color || palette[0];
        charts[id] = new Chart(canvas, {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [{
                    data: series.values,
                    borderColor: stroke,
                    backgroundColor: function (ctx) { return areaFill(ctx, stroke); },
                    fill: 'origin',
                    tension: 0.45,
                    cubicInterpolationMode: 'monotone',
                    pointRadius: series.values.length <= 1 ? 3 : 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: stroke,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    borderWidth: 2.5,
                }],
            },
            options: baseOptions(ySuggestedMax),
        });
    }

    function renderBar(id, labels, values, color) {
        var canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        if (charts[id]) charts[id].destroy();
        var series = normalizeSeries(labels, values);
        charts[id] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: series.labels,
                datasets: [{
                    data: series.values,
                    backgroundColor: hexToRgba(color || palette[1], 0.75),
                    borderRadius: 8,
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
        var labs = Array.isArray(labels) && labels.length ? labels : ['No data'];
        var vals = Array.isArray(values) && values.length ? values : [1];
        charts[id] = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labs,
                datasets: [{
                    data: vals,
                    backgroundColor: palette.slice(0, labs.length),
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 }, color: '#64748b', padding: 12 },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.92)',
                        padding: 10,
                        cornerRadius: 8,
                    },
                },
            },
        });
    }

    function renderInsights(list) {
        var el = document.getElementById('dashboard-insights-list');
        if (!el) return;
        el.innerHTML = '';
        (Array.isArray(list) ? list : []).forEach(function (text) {
            var li = document.createElement('li');
            li.textContent = text;
            el.appendChild(li);
        });
    }

    function showChartsError(message) {
        var el = document.getElementById('dashboard-insights-list');
        if (!el) return;
        el.innerHTML = '';
        var li = document.createElement('li');
        li.className = 'text-rose-600 list-none pl-0';
        li.textContent = message || 'Could not load charts.';
        el.appendChild(li);
    }

    function applyData(data) {
        if (!data) return;
        if (data.quiz_activity) renderCurve('chart-quiz-activity', data.quiz_activity.labels, data.quiz_activity.values, palette[0]);
        if (data.exam_submissions) renderCurve('chart-exam-submissions', data.exam_submissions.labels, data.exam_submissions.values, palette[2]);
        if (data.student_growth) renderBar('chart-student-growth', data.student_growth.labels, data.student_growth.values, palette[1]);
        if (data.live_support) renderBar('chart-live-support', data.live_support.labels, data.live_support.values, palette[4]);
        if (data.avg_exam_scores) renderCurve('chart-avg-scores', data.avg_exam_scores.labels, data.avg_exam_scores.values, palette[5], 100);
        if (data.staff_roles) renderPie('chart-staff-roles', data.staff_roles.labels, data.staff_roles.values);
        if (data.quiz_outcomes) renderPie('chart-quiz-outcomes', data.quiz_outcomes.labels, data.quiz_outcomes.values);
        if (data.support_status) renderPie('chart-support-status', data.support_status.labels, data.support_status.values);
        renderInsights(data.insights || []);
    }

    function load(period) {
        var url = (cfg.url || '/dashboard/charts') + '?period=' + encodeURIComponent(period || '30d');
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) {
                return r.json().then(function (res) {
                    return { ok: r.ok, status: r.status, res: res };
                });
            })
            .then(function (payload) {
                if (payload.ok && payload.res && payload.res.success && payload.res.charts) {
                    applyData(payload.res.charts);
                    return;
                }
                var msg = (payload.res && payload.res.message) || ('Charts request failed (' + payload.status + ').');
                showChartsError(msg);
            })
            .catch(function () {
                showChartsError('Network error while loading charts.');
            });
    }

    function whenChartReady(cb, attempts) {
        attempts = attempts || 0;
        if (window.Chart) {
            cb();
            return;
        }
        if (attempts > 40) {
            showChartsError('Chart library failed to load. Refresh the page.');
            return;
        }
        setTimeout(function () { whenChartReady(cb, attempts + 1); }, 50);
    }

    function init() {
        if (!document.getElementById('dashboard-trends-section')) return;
        whenChartReady(function () {
            var select = document.getElementById('dashboard-chart-period');
            var period = select ? select.value : '30d';
            load(period);
            if (select) {
                select.addEventListener('change', function () {
                    destroyAll();
                    load(select.value);
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
