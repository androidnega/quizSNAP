@php
    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar');
    $compact = $compact ?? false;
    $mobile = $mobile ?? false;
    $pillItems = [
        ['route' => 'dashboard', 'label' => 'Overview', 'icon' => 'fa-home', 'active' => $studentNavHome],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Quizzes', 'icon' => 'fa-clipboard-list', 'active' => request()->routeIs('dashboard.my-quizzes*'), 'student_only' => true],
        ['route' => 'dashboard.calendar', 'label' => 'Calendar', 'icon' => 'fa-calendar-alt', 'active' => request()->routeIs('dashboard.calendar'), 'student_only' => true],
        ['route' => 'dashboard.course-materials', 'label' => 'Materials', 'icon' => 'fa-book', 'active' => request()->routeIs('dashboard.course-materials'), 'student_only' => true, 'desktop_only' => true],
        ['route' => 'dashboard.my-profile', 'label' => 'Profile', 'icon' => 'fa-user', 'active' => request()->routeIs('dashboard.my-profile'), 'desktop_only' => true],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Results', 'icon' => 'fa-file-alt', 'active' => false, 'student_only' => true],
    ];
@endphp
<nav class="sd-segment-nav {{ $class ?? '' }}" aria-label="Dashboard sections">
    <div class="sd-segment-nav__track {{ $compact ? 'sd-segment-nav__track--compact' : '' }}">
        @foreach($pillItems as $item)
            @if(!empty($item['student_only']) && !($student ?? null))
                @continue
            @endif
            @if($mobile && !empty($item['desktop_only']))
                @continue
            @endif
            <a href="{{ route($item['route']) }}"
               class="sd-segment-nav__item {{ $item['active'] ? 'is-active' : '' }}">
                <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
