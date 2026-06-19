@php
    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar');
    $navItems = [
        ['route' => 'dashboard', 'label' => 'Overview', 'icon' => 'fa-home', 'active' => $studentNavHome],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Quizzes', 'icon' => 'fa-clipboard-list', 'active' => request()->routeIs('dashboard.my-quizzes*'), 'student_only' => true],
        ['route' => 'dashboard.calendar', 'label' => 'Calendar', 'icon' => 'fa-calendar-alt', 'active' => request()->routeIs('dashboard.calendar'), 'student_only' => true],
        ['route' => 'dashboard.course-materials', 'label' => 'Materials', 'icon' => 'fa-book', 'active' => request()->routeIs('dashboard.course-materials'), 'student_only' => true],
        ['route' => 'dashboard.my-profile', 'label' => 'Profile', 'icon' => 'fa-user', 'active' => request()->routeIs('dashboard.my-profile')],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Results', 'icon' => 'fa-file-alt', 'active' => false, 'student_only' => true],
    ];
@endphp
@foreach($navItems as $item)
    @if(!empty($item['student_only']) && !($student ?? null))
        @continue
    @endif
    <a href="{{ route($item['route']) }}"
       class="flex items-center gap-3 px-3 xl:px-4 py-2.5 text-sm font-medium no-underline transition-colors {{ $item['active'] ? 'bg-amber-100/90 text-slate-900 rounded-r-xl -mr-px border-l-[3px] border-amber-400' : 'text-slate-600 hover:bg-slate-200/50 hover:text-slate-900 rounded-xl mx-1' }}">
        <i class="fas {{ $item['icon'] }} w-5 text-center {{ $item['active'] ? 'text-amber-600' : 'text-slate-500' }}"></i>
        <span>{{ $item['label'] }}</span>
    </a>
@endforeach
