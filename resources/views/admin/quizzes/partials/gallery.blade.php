@php use App\Support\ProctoringImageUrl; @endphp
<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Student gallery</h2>
                <p class="text-xs text-gray-500 mt-0.5">Everyone who completed this quiz. Hover a photo to see the index number.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 tabular-nums">
                    {{ $gallerySessions->count() }} {{ \Illuminate\Support\Str::plural('student', $gallerySessions->count()) }}
                </span>
                <label for="gallery-search-index" class="sr-only">Search by index number</label>
                <input type="text" id="gallery-search-index" placeholder="Search by index…" class="w-44 min-w-0 max-w-xs text-sm py-1.5 px-3 rounded-lg border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="off">
            </div>
        </div>

        @if($gallerySessions->isEmpty())
            <div class="p-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600">No students yet</p>
                <p class="text-xs text-gray-400 mt-1">Faces appear here once students finish the quiz</p>
            </div>
        @else
            <div class="p-4 sm:p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-3 sm:gap-4" id="gallery-grid">
                    @foreach($gallerySessions as $session)
                        @php
                            $index = strtoupper(trim((string) ($session->student_index ?? '')));
                            $name = $galleryNames[$index] ?? null;
                            $facePath = $session->pre_face_image ?: $session->post_face_image;
                            $faceUrl = $facePath ? ProctoringImageUrl::resolve($facePath) : null;
                            $score = $session->result?->score;
                            $scoreClass = $score === null
                                ? 'bg-slate-500/90'
                                : ($score >= 70 ? 'bg-emerald-500/90' : ($score >= 50 ? 'bg-amber-500/90' : 'bg-rose-500/90'));
                            $initials = $index !== ''
                                ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $index) ?: '?', -2))
                                : '?';
                        @endphp
                        <a href="{{ route('dashboard.quizzes.sessions.show', [$quiz, $session]) }}"
                           class="gallery-card group relative block aspect-[3/4] overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-black/5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:ring-primary-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                           data-student-index="{{ $index }}"
                           title="{{ $index }}">
                            @if($faceUrl)
                                <img src="{{ $faceUrl }}" alt="" class="absolute inset-0 h-full w-full object-cover object-top transition duration-500 group-hover:scale-[1.04]" loading="lazy">
                            @else
                                <div class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-slate-200 via-slate-100 to-slate-200">
                                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/80 text-lg font-semibold tracking-wide text-slate-500 shadow-sm ring-1 ring-black/5">{{ $initials }}</span>
                                </div>
                            @endif

                            {{-- Soft bottom fade always present for depth --}}
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/35 to-transparent opacity-60 transition duration-300 group-hover:opacity-0"></div>

                            {{-- Hover reveal: index number --}}
                            <div class="pointer-events-none absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/80 via-black/25 to-transparent opacity-0 transition duration-300 group-hover:opacity-100 group-focus-visible:opacity-100 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-white/70 mb-0.5">Index</p>
                                <p class="text-sm font-bold text-white tracking-tight break-all leading-snug">{{ $index !== '' ? $index : '—' }}</p>
                                @if($name)
                                    <p class="mt-1 text-xs text-white/80 truncate">{{ $name }}</p>
                                @endif
                            </div>

                            @if($score !== null)
                                <span class="absolute top-2 right-2 inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-white shadow-sm backdrop-blur-sm {{ $scoreClass }}">
                                    {{ number_format((float) $score, 0) }}%
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
                <p id="gallery-empty-filter" class="hidden py-10 text-center text-sm text-gray-500">No students match that index.</p>
            </div>
        @endif
    </div>
</div>
