<div class="space-y-4">
    {{-- Quiet summary strip --}}
    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 sm:px-5 shadow-sm">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 sm:divide-x sm:divide-gray-100">
            <div class="sm:pr-6">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Students</p>
                <p class="mt-0.5 text-2xl font-semibold tracking-tight text-gray-900 tabular-nums">{{ $sessionsStats['total_students'] }}</p>
            </div>
            <div class="sm:px-6">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Average</p>
                <p class="mt-0.5 text-2xl font-semibold tracking-tight text-gray-900 tabular-nums">{{ $sessionsStats['average_score'] }}<span class="text-base font-medium text-gray-400">%</span></p>
            </div>
            <div class="sm:px-6">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Score range</p>
                <p class="mt-0.5 text-2xl font-semibold tracking-tight text-gray-900 tabular-nums">
                    @if($sessionsStats['total_students'] > 0)
                        {{ $sessionsStats['lowest_score'] }}–{{ $sessionsStats['highest_score'] }}<span class="text-base font-medium text-gray-400">%</span>
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="sm:pl-6">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Violations</p>
                <p class="mt-0.5 text-2xl font-semibold tracking-tight {{ $sessionsStats['total_violations'] > 0 ? 'text-rose-600' : 'text-gray-900' }} tabular-nums">{{ $sessionsStats['total_violations'] }}</p>
                @if($sessionsStats['students_with_violations'] > 0)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $sessionsStats['students_with_violations'] }} student{{ $sessionsStats['students_with_violations'] === 1 ? '' : 's' }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Results --}}
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="px-4 py-3.5 sm:px-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Completed sessions</h2>
                <p class="text-xs text-gray-500 mt-0.5">Search an index, open a result, or reset an attempt.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none min-w-[12rem]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    <label for="sessions-search-index" class="sr-only">Search by index number</label>
                    <input type="text" id="sessions-search-index" placeholder="Search index…" class="w-full sm:w-52 text-sm py-2 pl-9 pr-3 rounded-xl border border-gray-200 bg-gray-50/80 text-gray-900 placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition" autocomplete="off">
                </div>
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'scores']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </a>
            </div>
        </div>

        @if($sessionsPaginator->isEmpty())
            <div class="px-4 py-16 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-300 ring-1 ring-gray-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600">No completed sessions yet</p>
                <p class="text-xs text-gray-400 mt-1">Results show up here when students finish</p>
            </div>
        @else
            <ul class="divide-y divide-gray-100" id="sessions-table-body" role="list">
                @foreach($sessionsPaginator as $session)
                    @php
                        $index = strtoupper(trim((string) ($session->student_index ?? '')));
                        $name = ($galleryNames ?? [])[$index] ?? null;
                        $violationCount = $session->violations->count();
                        $score = $session->result?->score;
                        $scoreTone = $score === null
                            ? 'bg-gray-100 text-gray-500'
                            : ($score >= 70 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : ($score >= 50 ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-100'));
                    @endphp
                    <li class="sessions-row group" data-student-index="{{ $index }}">
                        <div class="flex items-center gap-3 px-4 sm:px-5 py-3 hover:bg-gray-50/80 transition-colors {{ $violationCount > 0 ? 'bg-rose-50/40' : '' }}">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="text-sm font-semibold text-gray-900 tracking-tight truncate">{{ $session->student_index }}</p>
                                    @if($session->isResultWithheld())
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide bg-rose-100 text-rose-700">On hold</span>
                                    @endif
                                    @if($violationCount > 0)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide bg-rose-100 text-rose-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            {{ $violationCount }} violation{{ $violationCount === 1 ? '' : 's' }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5 truncate">
                                    @if($name)
                                        {{ $name }}
                                        <span class="text-gray-300">·</span>
                                    @endif
                                    @if($session->ended_at)
                                        Finished {{ $session->ended_at->format('M j, g:i A') }}
                                    @else
                                        Completed
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if($session->result)
                                    <div class="hidden sm:flex flex-col items-end mr-1">
                                        <span class="inline-flex items-center justify-center min-w-[3.25rem] px-2 py-1 rounded-lg text-sm font-bold tabular-nums {{ $scoreTone }}">
                                            {{ number_format((float) $score, 1) }}%
                                        </span>
                                        <span class="mt-0.5 text-[11px] text-gray-400 tabular-nums">{{ $session->result->correct_count }}/{{ $session->result->total_questions }}</span>
                                    </div>
                                    <span class="sm:hidden inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold tabular-nums {{ $scoreTone }}">
                                        {{ number_format((float) $score, 0) }}%
                                    </span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif

                                <a href="{{ route('dashboard.quizzes.sessions.show', [$quiz, $session]) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $violationCount > 0 ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-gray-900 text-white hover:bg-gray-800' }} transition shadow-sm">
                                    View
                                </a>

                                <form action="{{ route('dashboard.quizzes.sessions.kill', [$quiz, $session]) }}" method="POST" class="inline" onsubmit="return confirm('Reset this session? The result will be removed and the student can retake the quiz.');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-xl text-xs font-medium text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition opacity-70 group-hover:opacity-100" title="Reset session so the student can retake">
                                        Reset
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p id="sessions-empty-filter" class="hidden px-4 py-10 text-center text-sm text-gray-500">No sessions match that index.</p>
        @endif
    </div>

    {{-- Retake tools: tucked away --}}
    <details class="group rounded-2xl border border-gray-200 bg-white shadow-sm open:shadow-md transition">
        <summary class="cursor-pointer list-none px-4 sm:px-5 py-3.5 flex items-center justify-between gap-3 select-none">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Allow students to retake</p>
                <p class="text-xs text-gray-500 mt-0.5">Clear completed sessions in a time range so they can try again.</p>
            </div>
            <span class="shrink-0 w-8 h-8 rounded-full bg-gray-50 ring-1 ring-gray-100 flex items-center justify-center text-gray-400 group-open:rotate-180 transition-transform">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </summary>
        <div class="px-4 sm:px-5 pb-4 pt-0 border-t border-gray-100">
            <form action="{{ route('dashboard.quizzes.sessions.clear-range', $quiz) }}" method="post" class="pt-4 flex flex-wrap items-end gap-3" onsubmit="return confirm('Delete completed sessions in this date/time range? This removes results for those attempts. Affected students will be able to retake the quiz.');">
                @csrf
                <div>
                    <label for="clear-from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input id="clear-from" type="datetime-local" name="from" required class="text-sm py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="clear-to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input id="clear-to" type="datetime-local" name="to" required class="text-sm py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <button type="submit" class="inline-flex items-center px-3.5 py-2 rounded-xl text-sm font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-100 transition">
                    Clear sessions in range
                </button>
            </form>
        </div>
    </details>
</div>
