@php
    $isExaminer = session('admin_role') === 'examiner';
    $accent = $g->accent_classes ?? ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-800'];
    $levelLabel = $g->level ? 'L' . (int) $g->level->value : null;
    $hasLiveSessions = isset($classGroupIdsWithLiveSessions) && in_array($g->id, $classGroupIdsWithLiveSessions);
    $palette = [
        'sky' => ['chip' => 'bg-sky-100 text-sky-800', 'bar' => 'bg-sky-500'],
        'emerald' => ['chip' => 'bg-emerald-100 text-emerald-800', 'bar' => 'bg-emerald-500'],
        'amber' => ['chip' => 'bg-amber-100 text-amber-800', 'bar' => 'bg-amber-500'],
        'violet' => ['chip' => 'bg-violet-100 text-violet-800', 'bar' => 'bg-violet-500'],
        'rose' => ['chip' => 'bg-rose-100 text-rose-800', 'bar' => 'bg-rose-500'],
        'teal' => ['chip' => 'bg-teal-100 text-teal-800', 'bar' => 'bg-teal-500'],
        'indigo' => ['chip' => 'bg-indigo-100 text-indigo-800', 'bar' => 'bg-indigo-500'],
        'slate' => ['chip' => 'bg-slate-100 text-slate-800', 'bar' => 'bg-slate-500'],
    ];
    $accentKey = $g->accent_color && isset($palette[$g->accent_color]) ? $g->accent_color : array_keys($palette)[((int) $g->id) % count($palette)];
    $tone = $palette[$accentKey];
@endphp
<article class="qs-reveal group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition duration-300 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-[0_12px_28px_rgba(15,23,42,0.08)]">
    <div class="h-1.5 w-full {{ $tone['bar'] }}"></div>
    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <a href="{{ route('dashboard.class-groups.show', $g) }}" class="min-w-0 flex-1 no-underline">
                <h3 class="text-[15px] font-semibold tracking-tight text-slate-900 line-clamp-2 group-hover:text-slate-700" title="{{ $g->name }}">{{ $g->name }}</h3>
                @if($levelLabel)
                    <span class="mt-2 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $tone['chip'] }}">{{ $levelLabel }}</span>
                @endif
            </a>
            <div class="flex flex-shrink-0 items-center gap-1" onclick="event.stopPropagation();">
                @if($hasLiveSessions)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-semibold text-white" title="Students are live writing a quiz">
                        <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>Live
                    </span>
                @endif
                <a href="{{ route('dashboard.class-groups.show', $g) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="View"><i class="fas fa-eye text-xs"></i></a>
                @if(!$isExaminer)
                <a href="{{ route('dashboard.class-groups.edit', $g) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Edit"><i class="fas fa-pen text-xs"></i></a>
                <form action="{{ route('dashboard.class-groups.destroy', $g) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($g->display_name) }}\'?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Delete"><i class="fas fa-trash-alt text-xs"></i></button>
                </form>
                @endif
            </div>
        </div>

        <a href="{{ route('dashboard.class-groups.show', $g) }}" class="mt-4 grid grid-cols-3 gap-2 no-underline">
            @if($isExaminer && isset($g->my_courses) && $g->my_courses->isNotEmpty())
                <div class="col-span-3 rounded-xl bg-slate-50 px-3 py-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Your courses</p>
                    <p class="mt-0.5 text-xs font-medium text-slate-800 line-clamp-2">{{ $g->my_courses->pluck('name')->join(', ') }}</p>
                    <p class="mt-1 text-[11px] text-slate-500">{{ $g->my_quizzes_count ?? 0 }} quizzes</p>
                </div>
            @else
                <div class="rounded-xl bg-slate-50 px-2.5 py-2 text-center">
                    <p class="text-base font-semibold tabular-nums text-slate-900">{{ $g->students_count ?? 0 }}</p>
                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Students</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-2.5 py-2 text-center">
                    <p class="text-base font-semibold tabular-nums text-slate-900">{{ $g->courses_count ?? 0 }}</p>
                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Courses</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-2.5 py-2 text-center">
                    <p class="text-base font-semibold tabular-nums text-slate-900">{{ $g->quizzes_count ?? 0 }}</p>
                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Quizzes</p>
                </div>
            @endif
        </a>
    </div>
</article>
