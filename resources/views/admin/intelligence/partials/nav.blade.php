@php
    $intelligenceNav = [
        ['route' => 'dashboard.intelligence.index', 'label' => 'Executive Dashboard', 'icon' => 'fa-chart-pie'],
        ['route' => 'dashboard.intelligence.academic.index', 'label' => 'Academic Intelligence', 'icon' => 'fa-graduation-cap'],
        ['route' => 'dashboard.intelligence.students.index', 'label' => 'Student Intelligence', 'icon' => 'fa-user-graduate'],
        ['route' => 'dashboard.intelligence.lecturers.index', 'label' => 'Lecturer Intelligence', 'icon' => 'fa-chalkboard-teacher'],
        ['route' => 'dashboard.intelligence.risk.index', 'label' => 'Risk Analysis', 'icon' => 'fa-exclamation-triangle'],
        ['route' => 'dashboard.intelligence.proctoring.index', 'label' => 'AI Proctoring', 'icon' => 'fa-video'],
        ['route' => 'dashboard.intelligence.predictive.index', 'label' => 'Predictive Analytics', 'icon' => 'fa-chart-area'],
        ['route' => 'dashboard.intelligence.engagement.index', 'label' => 'Engagement Analytics', 'icon' => 'fa-chart-line'],
        ['route' => 'dashboard.intelligence.integrity.index', 'label' => 'Integrity Analytics', 'icon' => 'fa-shield-alt'],
        ['route' => 'dashboard.intelligence.recommendations.index', 'label' => 'Recommendations', 'icon' => 'fa-lightbulb'],
        ['route' => 'dashboard.intelligence.warnings.index', 'label' => 'Early Warnings', 'icon' => 'fa-bell'],
        ['route' => 'dashboard.intelligence.reports.index', 'label' => 'Executive Reports', 'icon' => 'fa-file-export'],
    ];
@endphp
<div class="mb-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Intelligence Center</p>
            <p class="text-sm text-gray-600">Academic insights, prediction & risk intelligence</p>
        </div>
        <span id="intelligence-live-indicator" class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700"><span class="h-2 w-2 rounded-full bg-violet-500 animate-pulse"></span> Live</span>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($intelligenceNav as $item)
            <a href="{{ route($item['route']) }}" class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'border-violet-300 bg-violet-50 text-violet-700' : 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                <i class="fas {{ $item['icon'] }} text-[10px]"></i>{{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>
