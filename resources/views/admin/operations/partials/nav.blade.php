@php
    $operationsNav = [
        ['route' => 'dashboard.operations.index', 'label' => 'Command Center', 'icon' => 'fa-tv'],
        ['route' => 'dashboard.operations.live-exams.index', 'label' => 'Live Exams', 'icon' => 'fa-file-alt'],
        ['route' => 'dashboard.operations.attendance.index', 'label' => 'Live Attendance', 'icon' => 'fa-user-check'],
        ['route' => 'dashboard.operations.students.index', 'label' => 'Student Activity', 'icon' => 'fa-users'],
        ['route' => 'dashboard.operations.proctoring.index', 'label' => 'Proctoring Center', 'icon' => 'fa-video'],
        ['route' => 'dashboard.intelligence.academic.index', 'label' => 'Academic Intelligence', 'icon' => 'fa-graduation-cap'],
        ['route' => 'dashboard.operations.incidents.index', 'label' => 'Exam Incidents', 'icon' => 'fa-exclamation-circle'],
        ['route' => 'dashboard.operations.analytics.exams', 'label' => 'Exam Analytics', 'icon' => 'fa-chart-bar'],
        ['route' => 'dashboard.operations.analytics.attendance', 'label' => 'Attendance Analytics', 'icon' => 'fa-chart-line'],
        ['route' => 'dashboard.operations.analytics.faculty', 'label' => 'Faculty Analytics', 'icon' => 'fa-chalkboard-teacher'],
        ['route' => 'dashboard.operations.reports.index', 'label' => 'Reports', 'icon' => 'fa-file-export'],
        ['route' => 'dashboard.operations.wallboard.index', 'label' => 'Wallboard', 'icon' => 'fa-desktop'],
    ];
@endphp

<div class="mb-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Operations Center</p>
            <p class="text-sm text-gray-600">Live exam and academic operations</p>
        </div>
        <span id="operations-live-indicator" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Live
        </span>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($operationsNav as $item)
            <a href="{{ route($item['route']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                <i class="fas {{ $item['icon'] }} text-[10px]"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>
