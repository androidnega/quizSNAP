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
       class="sd-sidebar-nav__item {{ $item['active'] ? 'is-active' : '' }}">
        <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
        <span>{{ $item['label'] }}</span>
    </a>
@endforeach
