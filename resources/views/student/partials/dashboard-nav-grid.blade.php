@php
    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar');
    $gridItems = [
        ['route' => 'dashboard', 'label' => 'Overview', 'icon' => 'fa-home', 'active' => $studentNavHome],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Quizzes', 'icon' => 'fa-clipboard-list', 'active' => request()->routeIs('dashboard.my-quizzes*'), 'student_only' => true],
        ['route' => 'dashboard.calendar', 'label' => 'Calendar', 'icon' => 'fa-calendar-alt', 'active' => request()->routeIs('dashboard.calendar'), 'student_only' => true],
        ['route' => 'dashboard.course-materials', 'label' => 'Materials', 'icon' => 'fa-book', 'active' => request()->routeIs('dashboard.course-materials'), 'student_only' => true],
        ['route' => 'dashboard.my-profile', 'label' => 'Profile', 'icon' => 'fa-user', 'active' => request()->routeIs('dashboard.my-profile')],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Class results', 'icon' => 'fa-file-alt', 'active' => false, 'student_only' => true],
    ];
@endphp
<nav class="lg:hidden mb-5" aria-label="Dashboard sections">
    <div class="grid grid-cols-3 gap-2.5">
        @foreach($gridItems as $item)
            @if(!empty($item['student_only']) && !($student ?? null))
                @continue
            @endif
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-2 rounded-full border px-3 py-2.5 min-h-[44px] no-underline transition-colors
                      {{ $item['active'] ? 'bg-amber-50 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-800 hover:bg-slate-50 hover:border-slate-300' }}">
                <i class="fas {{ $item['icon'] }} text-xs shrink-0 {{ $item['active'] ? 'text-amber-600' : 'text-slate-600' }}"></i>
                <span class="text-xs font-semibold leading-tight">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
