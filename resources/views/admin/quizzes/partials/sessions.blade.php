@php use App\Support\ProctoringImageUrl; @endphp
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
        <div class="px-4 py-3 sm:px-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2.5">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Completed sessions</h2>
                <p class="text-xs text-gray-500 mt-0.5">Search an index, open a result, or reset an attempt.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none min-w-[11rem]">
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    <label for="sessions-search-index" class="sr-only">Search by index number</label>
                    <input type="text" id="sessions-search-index" placeholder="Search index…" class="w-full sm:w-48 text-sm py-1.5 pl-8 pr-3 rounded-lg border border-gray-200 bg-gray-50/80 text-gray-900 placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition" autocomplete="off">
                </div>
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'scores']) }}" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-800 px-2.5 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </a>
            </div>
        </div>

        @if($sessionsPaginator->isEmpty())
            <div class="px-4 py-14 text-center">
                <p class="text-sm font-medium text-gray-600">No completed sessions yet</p>
                <p class="text-xs text-gray-400 mt-1">Results show up here when students finish</p>
            </div>
        @else
            <style>
                .sessions-scroll-hide {
                    max-height: min(28rem, 58vh);
                    overflow-y: auto;
                    overscroll-behavior: contain;
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }
                .sessions-scroll-hide::-webkit-scrollbar {
                    width: 0;
                    height: 0;
                    display: none;
                }
                .sessions-face {
                    width: 2rem;
                    height: 2rem;
                    border-radius: 9999px;
                }
                .sessions-face img {
                    transition: transform 0.35s ease;
                }
                .sessions-row:hover .sessions-face img {
                    transform: scale(1.06);
                }
            </style>
            <div class="sessions-scroll-hide bg-white">
                <ul class="divide-y divide-gray-100 bg-white" id="sessions-table-body" role="list">
                    @foreach($sessionsPaginator as $session)
                        @php
                            $index = strtoupper(trim((string) ($session->student_index ?? '')));
                            $name = ($galleryNames ?? [])[$index] ?? null;
                            $violationCount = $session->violations->count();
                            $score = $session->result?->score;
                            $faceUrl = !empty($session->pre_face_image)
                                ? ProctoringImageUrl::resolve($session->pre_face_image)
                                : null;
                            $initials = $index !== ''
                                ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $index) ?: '?', -2))
                                : '?';
                        @endphp
                        <li class="sessions-row group" data-student-index="{{ $index }}">
                            <div class="flex items-center gap-2.5 px-3 sm:px-4 py-1.5 bg-white hover:bg-gray-50 transition-colors">
                                <div class="sessions-face shrink-0 overflow-hidden bg-gray-100 ring-1 ring-black/5">
                                    @if($faceUrl)
                                        <img src="{{ $faceUrl }}" alt="" class="h-full w-full object-cover object-top" loading="lazy">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[10px] font-semibold tracking-wide text-slate-400">{{ $initials }}</span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1 flex items-center gap-2">
                                    <span class="text-[13px] font-medium text-gray-900 truncate">{{ $session->student_index }}</span>
                                    @if($name)
                                        <span class="hidden md:inline text-xs text-gray-400 truncate max-w-[10rem]">{{ $name }}</span>
                                    @endif
                                    @if($session->isResultWithheld())
                                        <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-gray-500">Hold</span>
                                    @endif
                                    @if($violationCount > 0)
                                        <span class="shrink-0 text-[10px] font-semibold tabular-nums text-rose-600" title="{{ $violationCount }} violation{{ $violationCount === 1 ? '' : 's' }}">{{ $violationCount }}v</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    @if($session->result)
                                        <span class="text-[13px] font-semibold tabular-nums text-gray-900 min-w-[2.75rem] text-right">{{ $session->result->correct_count }}/{{ $session->result->total_questions }}</span>
                                        <span class="text-[11px] text-gray-400 tabular-nums min-w-[2.75rem] text-right">{{ number_format((float) $score, 0) }}%</span>
                                    @else
                                        <span class="text-xs text-gray-300 min-w-[2.75rem] text-right">—</span>
                                    @endif

                                    <a href="{{ route('dashboard.quizzes.sessions.show', [$quiz, $session]) }}"
                                       class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                        View
                                    </a>
                                    <form action="{{ route('dashboard.quizzes.sessions.kill', [$quiz, $session]) }}" method="POST" class="inline" onsubmit="return confirm('Reset this session? The result will be removed and the student can retake the quiz.');">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-gray-300 hover:text-rose-600 transition" title="Reset session so the student can retake">
                                            Reset
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p id="sessions-empty-filter" class="hidden px-4 py-8 text-center text-sm text-gray-500 bg-white">No sessions match that index.</p>
            </div>
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
