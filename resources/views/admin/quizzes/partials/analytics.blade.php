{{-- Question analytics: charts first, then scrollable question list --}}
@php
    $questionStats = $questionStats ?? [];
    $totalAnswered = collect($questionStats)->sum('answered');
    $totalCorrect = collect($questionStats)->sum('correct');
    $totalIncorrect = max(0, $totalAnswered - $totalCorrect);
    $overallPct = $totalAnswered > 0 ? round(100.0 * $totalCorrect / $totalAnswered, 1) : 0;
@endphp
<div class="space-y-4">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3.5 sm:px-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Question analytics</h2>
                <p class="text-xs text-gray-500 mt-0.5">Correct vs incorrect across every question</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard.quizzes.analytics.export.pdf.preview', $quiz) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100">
                    Preview PDF
                </a>
                <a href="{{ route('dashboard.quizzes.analytics.export.pdf', $quiz) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50" download>
                    Download PDF
                </a>
            </div>
        </div>

        <div class="px-4 py-3 sm:px-5 border-b border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div>
                <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Questions</p>
                <p class="mt-0.5 text-xl font-semibold text-gray-900 tabular-nums">{{ count($questionStats) }}</p>
            </div>
            <div>
                <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Responses</p>
                <p class="mt-0.5 text-xl font-semibold text-gray-900 tabular-nums">{{ $totalAnswered }}</p>
            </div>
            <div>
                <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Correct</p>
                <p class="mt-0.5 text-xl font-semibold text-gray-900 tabular-nums">{{ $totalCorrect }}</p>
            </div>
            <div>
                <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Overall</p>
                <p class="mt-0.5 text-xl font-semibold text-gray-900 tabular-nums">{{ $overallPct }}%</p>
            </div>
        </div>

        @if(empty($questionStats))
            <div class="px-4 py-14 text-center">
                <p class="text-sm font-medium text-gray-600">No question data yet</p>
                <p class="text-xs text-gray-400 mt-1">Charts appear after students complete the quiz</p>
            </div>
        @else
            <div class="p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-5 gap-4">
                {{-- Clean grouped bars --}}
                <div class="lg:col-span-3 rounded-2xl border border-gray-100 bg-white p-4">
                    <div class="flex items-baseline justify-between gap-2 mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 tracking-tight">Correct vs incorrect by question</h3>
                        <div class="flex items-center gap-3 text-[11px] text-gray-500">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-gray-900"></span>Correct</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-gray-300"></span>Incorrect</span>
                        </div>
                    </div>
                    <div class="relative h-64 sm:h-72">
                        <canvas id="analytics-bar-chart" aria-label="Correct versus incorrect by question"></canvas>
                    </div>
                </div>

                {{-- Overall doughnut --}}
                <div class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white p-4 flex flex-col">
                    <h3 class="text-sm font-semibold text-gray-900 tracking-tight mb-3">Overall: correct vs incorrect</h3>
                    <div class="relative flex-1 min-h-[14rem] flex items-center justify-center">
                        <canvas id="analytics-pie-chart" aria-label="Overall correct versus incorrect"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <p class="text-2xl font-bold tabular-nums text-gray-900">{{ $overallPct }}%</p>
                            <p class="text-[11px] text-gray-400">correct</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-center gap-4 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-gray-900"></span>{{ $totalCorrect }} correct</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-gray-300"></span>{{ $totalIncorrect }} incorrect</span>
                    </div>
                </div>
            </div>

            {{-- Scrollable list --}}
            <div class="border-t border-gray-100">
                <div class="px-4 sm:px-5 py-3 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-900 tracking-tight">Per-question breakdown</h3>
                    <span class="text-[11px] text-gray-400 tabular-nums">{{ count($questionStats) }} questions</span>
                </div>
                <style>
                    .analytics-scroll-hide {
                        max-height: min(22rem, 48vh);
                        overflow-y: auto;
                        overscroll-behavior: contain;
                        -ms-overflow-style: none;
                        scrollbar-width: none;
                    }
                    .analytics-scroll-hide::-webkit-scrollbar {
                        width: 0;
                        height: 0;
                        display: none;
                    }
                </style>
                <div class="analytics-scroll-hide px-4 sm:px-5 pb-4">
                    <ul class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden bg-white" role="list">
                        @foreach($questionStats as $row)
                            @php
                                $incorrect = max(0, (int) $row['answered'] - (int) $row['correct']);
                                $pct = $row['percentage'];
                            @endphp
                            <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50/80 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[13px] font-medium text-gray-900 truncate" title="{{ $row['label'] }}">{{ $row['short_label'] }}</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $row['label'] }}</p>
                                </div>
                                <div class="flex items-center gap-3 shrink-0 text-xs tabular-nums">
                                    <span class="text-gray-500 hidden sm:inline" title="Answered">{{ $row['answered'] }} ans</span>
                                    <span class="font-semibold text-emerald-600">{{ $row['correct'] }}✓</span>
                                    <span class="font-medium text-rose-500">{{ $incorrect }}✗</span>
                                    @if($pct !== null)
                                        <span class="min-w-[2.75rem] text-right font-semibold text-gray-900">{{ $pct }}%</span>
                                    @else
                                        <span class="min-w-[2.75rem] text-right text-gray-300">—</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
</div>

@if(!empty($questionStats))
<script>
(function() {
    var stats = @json($questionStats);
    var chartInstances = [];

    function destroyCharts() {
        chartInstances.forEach(function(c) { try { c.destroy(); } catch (e) {} });
        chartInstances = [];
    }

    function drawCharts() {
        var barCtx = document.getElementById('analytics-bar-chart');
        var pieCtx = document.getElementById('analytics-pie-chart');
        if (!barCtx || !pieCtx || typeof Chart === 'undefined') return;
        destroyCharts();

        var labels = stats.map(function(s) { return s.short_label; });
        var correctData = stats.map(function(s) { return s.correct; });
        var incorrectData = stats.map(function(s) { return Math.max(0, s.answered - s.correct); });

        var barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Correct',
                        data: correctData,
                        backgroundColor: '#111827',
                        borderRadius: 4,
                        borderSkipped: false,
                        barPercentage: 0.68,
                        categoryPercentage: 0.62
                    },
                    {
                        label: 'Incorrect',
                        data: incorrectData,
                        backgroundColor: '#d1d5db',
                        borderRadius: 4,
                        borderSkipped: false,
                        barPercentage: 0.68,
                        categoryPercentage: 0.62
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        stacked: false,
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 14, font: { size: 10 }, color: '#9ca3af' },
                        border: { display: false }
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#9ca3af',
                            font: { size: 10 },
                            callback: function(v) { return Number.isInteger(v) ? v : null; }
                        },
                        grid: { color: 'rgba(15, 23, 42, 0.06)', drawTicks: false },
                        border: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111827',
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 10,
                        displayColors: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        boxPadding: 4
                    }
                }
            }
        });
        chartInstances.push(barChart);

        var totalCorrect = stats.reduce(function(a, s) { return a + s.correct; }, 0);
        var totalIncorrect = stats.reduce(function(a, s) { return a + Math.max(0, s.answered - s.correct); }, 0);
        var pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Correct', 'Incorrect'],
                datasets: [{
                    data: [totalCorrect, totalIncorrect],
                    backgroundColor: ['#111827', '#d1d5db'],
                    borderWidth: 0,
                    hoverOffset: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '74%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111827',
                        padding: 10,
                        cornerRadius: 10
                    }
                }
            }
        });
        chartInstances.push(pieChart);
    }

    if (typeof Chart !== 'undefined') { drawCharts(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
    s.crossOrigin = 'anonymous';
    s.onload = drawCharts;
    document.head.appendChild(s);
})();
</script>
@endif
