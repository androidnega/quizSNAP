@php
    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar');
    $compact = $compact ?? false;
    $pillItems = [
        ['route' => 'dashboard', 'label' => 'Overview', 'icon' => 'fa-home', 'active' => $studentNavHome],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Quizzes', 'icon' => 'fa-clipboard-list', 'active' => request()->routeIs('dashboard.my-quizzes*'), 'student_only' => true],
        ['route' => 'dashboard.calendar', 'label' => 'Calendar', 'icon' => 'fa-calendar-alt', 'active' => request()->routeIs('dashboard.calendar'), 'student_only' => true],
        ['route' => 'dashboard.course-materials', 'label' => 'Materials', 'icon' => 'fa-book', 'active' => request()->routeIs('dashboard.course-materials'), 'student_only' => true],
        ['route' => 'dashboard.my-profile', 'label' => 'Profile', 'icon' => 'fa-user', 'active' => request()->routeIs('dashboard.my-profile')],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Results', 'icon' => 'fa-file-alt', 'active' => false, 'student_only' => true],
    ];
    $pillBase = $compact
        ? 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold border transition-colors whitespace-nowrap no-underline'
        : 'inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border transition-colors whitespace-nowrap no-underline';
    $pillActive = 'theme-pill-active shadow-sm';
    $pillInactive = 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300';
    $iconMr = $compact ? 'text-[10px]' : 'mr-1.5 text-xs';
@endphp
<nav class="{{ $class ?? '' }}" aria-label="Dashboard sections">
    <div class="flex flex-wrap items-center {{ $compact ? 'gap-1.5' : 'gap-2.5' }}">
        @foreach($pillItems as $item)
            @if(!empty($item['student_only']) && !($student ?? null))
                @continue
            @endif
            <a href="{{ route($item['route']) }}"
               class="{{ $pillBase }} {{ $item['active'] ? $pillActive : $pillInactive }}">
                <i class="fas {{ $item['icon'] }} {{ $iconMr }} shrink-0"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
