@php
    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar');
    $bottomItems = [
        ['route' => 'dashboard', 'label' => 'Home', 'icon' => 'fa-home', 'active' => $studentNavHome],
        ['route' => 'dashboard.my-quizzes', 'label' => 'Quizzes', 'icon' => 'fa-clipboard-list', 'active' => request()->routeIs('dashboard.my-quizzes*'), 'student_only' => true],
        ['route' => 'dashboard.course-materials', 'label' => 'Materials', 'icon' => 'fa-book', 'active' => request()->routeIs('dashboard.course-materials'), 'student_only' => true],
        ['route' => 'dashboard.my-profile', 'label' => 'Profile', 'icon' => 'fa-user', 'active' => request()->routeIs('dashboard.my-profile')],
    ];
@endphp
<nav class="fixed bottom-0 inset-x-0 z-40 lg:hidden bg-white border-t border-slate-200 shadow-[0_-4px_20px_rgba(15,23,42,0.06)]" aria-label="Mobile bottom navigation" style="padding-bottom: env(safe-area-inset-bottom, 0);">
    <div class="grid grid-cols-4 h-16 max-w-lg mx-auto">
        @foreach($bottomItems as $item)
            @if(!empty($item['student_only']) && !($student ?? null))
                @continue
            @endif
            <a href="{{ route($item['route']) }}"
               class="flex flex-col items-center justify-center gap-1 no-underline transition-colors {{ $item['active'] ? 'text-amber-500' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="fas {{ $item['icon'] }} text-lg {{ $item['active'] ? 'text-amber-500' : 'text-slate-600' }}"></i>
                <span class="text-[10px] font-semibold leading-none">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
