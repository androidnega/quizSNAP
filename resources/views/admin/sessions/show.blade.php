@extends('layouts.dashboard')

@section('title', 'Session – ' . $session->student_index)
@section('dashboard_heading', 'Session – ' . $session->student_index)

@section('dashboard_content')
@php
    use App\Support\ProctoringImageUrl;

    $typeLabels = [
        'blur' => 'Window lost focus',
        'tab_switch' => 'Switched to another tab',
        'window_resize' => 'Window resized or minimized',
        'phone_detected' => 'Phone detected',
        'copy_paste' => 'Copy or paste attempted',
        'right_click' => 'Right-click / context menu',
        'screenshot_attempt' => 'Screenshot key pressed',
        'multiple_ip' => 'Different IP address used',
        'face_mismatch' => 'Face mismatch',
        'no_face_during_quiz' => 'No face during quiz',
        'face_out_of_frame' => 'Face out of frame',
        'multiple_faces_during_quiz' => 'Multiple faces during quiz',
        'multiple_faces_pre_quiz' => 'Multiple faces pre quiz',
        'multiple_faces' => 'Multiple faces detected',
        'head_turn' => 'Head turned away',
        'static_face_detected' => 'Static face detected',
        'other' => 'Other',
    ];

    $preUrl = !empty($session->pre_face_image) ? ProctoringImageUrl::resolve($session->pre_face_image) : null;
    $postUrl = !empty($session->post_face_image) ? ProctoringImageUrl::resolve($session->post_face_image) : null;
    $violationSnapshots = $session->violations->filter(fn ($v) => !empty($v->image_url))->take(5)->values();
    $violationCount = $session->result?->violations_count ?? $session->violations->count();
    $indexKey = strtoupper(trim((string) ($session->student_index ?? '')));
    $initials = $indexKey !== ''
        ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $indexKey) ?: '?', -2))
        : '?';

    $formatViolationDetails = function ($v): string {
        $meta = $v->metadata;
        if (is_string($meta)) {
            $decoded = @json_decode($meta, true);
            $meta = $decoded !== null ? $decoded : $meta;
        }
        if (is_array($meta)) {
            if (isset($meta['expected'], $meta['got'])) {
                return 'Expected IP: ' . $meta['expected'] . ' — Got: ' . $meta['got'];
            }
            $parts = [];
            if (isset($meta['face_count'])) {
                $parts[] = 'Face count: ' . (int) $meta['face_count'];
            }
            if (isset($meta['object'])) {
                $parts[] = 'Object: ' . (string) $meta['object'];
            }
            if (isset($meta['reason'])) {
                $parts[] = 'Reason: ' . (string) $meta['reason'];
            }
            if (isset($meta['warning_count'])) {
                $parts[] = 'Warning count: ' . (int) $meta['warning_count'];
            }
            if (isset($meta['remaining_warnings'])) {
                $parts[] = 'Remaining warnings: ' . (int) $meta['remaining_warnings'];
            }
            $loggedAt = $meta['logged_at'] ?? $meta['captured_at'] ?? $meta['detected_at'] ?? $meta['timestamp'] ?? null;
            if ($loggedAt !== null) {
                $parts[] = 'At ' . (is_numeric($loggedAt) ? date('M d, H:i:s', (int) $loggedAt) : (string) $loggedAt);
            }
            if ($parts === []) {
                $parts[] = implode('; ', array_map(
                    fn ($k, $val) => $k . ': ' . (is_scalar($val) ? $val : json_encode($val)),
                    array_keys($meta),
                    $meta
                ));
            }

            return implode(' · ', array_filter($parts));
        }

        return (string) $meta !== '' ? (string) $meta : '';
    };
@endphp

<div class="w-full space-y-4">
    <nav class="flex flex-wrap items-center gap-x-2 text-xs text-gray-500">
        <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions']) }}" class="hover:text-gray-900 transition">← Sessions</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-600 truncate max-w-[12rem] sm:max-w-none">{{ $quiz->title }}</span>
        <span class="text-gray-300">/</span>
        <span class="font-medium text-gray-900">{{ $session->student_index }}</span>
    </nav>

    {{-- Hero summary --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-4 sm:px-5 sm:py-5 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-full bg-gray-100 ring-1 ring-black/5">
                    @if($preUrl)
                        <img src="{{ $preUrl }}" alt="" class="h-full w-full object-cover object-top">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-sm font-semibold text-gray-400">{{ $initials }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-base sm:text-lg font-semibold tracking-tight text-gray-900 truncate">{{ $session->student_index }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $session->device_label ?? 'Device' }}
                        <span class="text-gray-300">·</span>
                        {{ $session->start_time?->format('M j, g:i A') ?? '—' }}
                        →
                        {{ $session->ended_at?->format('g:i A') ?? '—' }}
                    </p>
                    @if($session->result && $session->isResultWithheld())
                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-rose-600">Result on hold</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($session->result)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-right">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Mark</p>
                        <p class="text-lg font-semibold tabular-nums text-gray-900 leading-tight">{{ $session->result->correct_count }}/{{ $session->result->total_questions }}</p>
                        <p class="text-[11px] text-gray-400 tabular-nums">{{ number_format((float) $session->result->score, 1) }}%</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-right">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Violations</p>
                        <p class="text-lg font-semibold tabular-nums {{ $violationCount > 0 ? 'text-rose-600' : 'text-gray-900' }} leading-tight">{{ $violationCount }}</p>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-1.5">
                    @if($session->result && $session->isResultWithheld())
                        <form method="post" action="{{ route('dashboard.quizzes.sessions.clear-withheld', [$quiz, $session]) }}" onsubmit="return confirm('Release result and allow student to see this score?');">
                            @csrf
                            <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-900 text-white hover:bg-gray-800 transition">Release</button>
                        </form>
                    @endif
                    <form method="post" action="{{ route('dashboard.quizzes.sessions.reset-ip', [$quiz, $session]) }}" onsubmit="return confirm('Reset IP lock?');">
                        @csrf
                        <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition">Reset IP</button>
                    </form>
                    <form method="post" action="{{ route('dashboard.quizzes.sessions.kill', [$quiz, $session]) }}" onsubmit="return confirm('Kill this session? This will remove the result and allow the student to retake the quiz.');">
                        @csrf
                        <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 transition">Kill</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-gray-100 border-t border-gray-100">
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">IP</p>
                <p class="mt-0.5 text-xs font-medium text-gray-900 font-mono truncate" title="{{ $session->ip_address }}">{{ $session->ip_address ?: '—' }}</p>
            </div>
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Device</p>
                <p class="mt-0.5 text-xs font-medium text-gray-900 truncate" title="{{ $session->user_agent ?? '' }}">{{ $session->device_label ?? 'Laptop' }}</p>
            </div>
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Started</p>
                <p class="mt-0.5 text-xs font-medium text-gray-900">{{ $session->start_time?->format('M d, H:i') ?? '—' }}</p>
            </div>
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Ended</p>
                <p class="mt-0.5 text-xs font-medium text-gray-900">{{ $session->ended_at?->format('M d, H:i') ?? '—' }}</p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 min-w-0">
        {{-- Face captures --}}
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4 sm:p-5" id="face-capture">
            <div class="flex items-baseline justify-between gap-2 mb-4">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Face captures</h2>
                <p class="text-xs text-gray-400">Tap to enlarge</p>
            </div>
            <div class="flex flex-wrap gap-5">
                <div class="flex flex-col items-center gap-1.5">
                    <button type="button" @if($preUrl) class="session-img-thumb group" data-session-full-img="{{ $preUrl }}" data-session-img-alt="Face at start" aria-label="View full size" @else disabled @endif>
                        <span class="block h-20 w-20 overflow-hidden rounded-full bg-gray-100 ring-1 ring-black/5 {{ $preUrl ? 'transition group-hover:ring-gray-300' : '' }}">
                            @if($preUrl)
                                <img src="{{ $preUrl }}" alt="Face at start" class="h-full w-full object-cover object-top" loading="lazy">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-xs text-gray-400">No image</span>
                            @endif
                        </span>
                    </button>
                    <span class="text-[11px] text-gray-500">Start</span>
                </div>
                <div class="flex flex-col items-center gap-1.5">
                    <button type="button" @if($postUrl) class="session-img-thumb group" data-session-full-img="{{ $postUrl }}" data-session-img-alt="Face at end" aria-label="View full size" @else disabled @endif>
                        <span class="block h-20 w-20 overflow-hidden rounded-full bg-gray-100 ring-1 ring-black/5 {{ $postUrl ? 'transition group-hover:ring-gray-300' : '' }}">
                            @if($postUrl)
                                <img src="{{ $postUrl }}" alt="Face at end" class="h-full w-full object-cover object-top" loading="lazy">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-xs text-gray-400">No image</span>
                            @endif
                        </span>
                    </button>
                    <span class="text-[11px] text-gray-500">
                        End
                        @if($postUrl && $session->post_face_captured_at)
                            · {{ $session->post_face_captured_at->format('H:i') }}
                        @endif
                    </span>
                </div>
                <div class="flex flex-col items-start gap-1.5 min-w-0">
                    <span class="text-[11px] text-gray-500 mb-0.5">During quiz</span>
                    @if($violationSnapshots->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($violationSnapshots as $snap)
                                @php $imgUrl = ProctoringImageUrl::resolve($snap->image_url); @endphp
                                @if($imgUrl)
                                    <button type="button" class="session-img-thumb group" data-session-full-img="{{ $imgUrl }}" data-session-img-alt="Violation capture {{ $loop->iteration }}" aria-label="View full size">
                                        <span class="block h-14 w-14 overflow-hidden rounded-full bg-gray-100 ring-1 ring-black/5 transition group-hover:ring-gray-300">
                                            <img src="{{ $imgUrl }}" alt="Violation capture {{ $loop->iteration }}" class="h-full w-full object-cover object-top" loading="lazy">
                                        </span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400">No in-quiz captures</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- Violations --}}
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4 sm:p-5 min-w-0 flex flex-col max-h-[28rem]">
            <div class="flex items-baseline justify-between gap-2 mb-3 shrink-0">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Violation log</h2>
                <span class="text-xs tabular-nums text-gray-400">{{ $session->violations->count() }}</span>
            </div>

            @if($session->violations->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-xs text-gray-400">No violations recorded</div>
            @else
                <style>
                    .session-panel-scroll {
                        overflow-y: auto;
                        overscroll-behavior: contain;
                        -ms-overflow-style: none;
                        scrollbar-width: none;
                    }
                    .session-panel-scroll::-webkit-scrollbar { width: 0; height: 0; display: none; }
                </style>
                <div class="session-panel-scroll flex-1 min-h-0 -mx-1">
                    <table class="min-w-full text-xs">
                        <thead class="sticky top-0 bg-white">
                            <tr class="border-b border-gray-100 text-left text-[10px] uppercase tracking-wide text-gray-400">
                                <th scope="col" class="px-2 py-2 font-medium">#</th>
                                <th scope="col" class="px-2 py-2 font-medium">Time</th>
                                <th scope="col" class="px-2 py-2 font-medium">Type</th>
                                <th scope="col" class="px-2 py-2 font-medium">Severity</th>
                                <th scope="col" class="px-2 py-2 font-medium">Details</th>
                                <th scope="col" class="px-2 py-2 font-medium">Img</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($session->violations as $idx => $v)
                                @php
                                    $label = $typeLabels[$v->type] ?? ucfirst(str_replace('_', ' ', $v->type));
                                    $details = $formatViolationDetails($v);
                                @endphp
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-2 py-2 tabular-nums text-gray-400">{{ $idx + 1 }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-600">{{ $v->occurred_at?->format('M d, H:i:s') ?? '—' }}</td>
                                    <td class="px-2 py-2 font-medium text-gray-900">{{ $label }}</td>
                                    <td class="px-2 py-2">
                                        @if($v->severity === 'critical')
                                            <span class="text-rose-600 font-medium">Critical</span>
                                        @else
                                            <span class="text-gray-500">Warning</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-gray-500 max-w-[12rem] break-words">{{ $details !== '' ? $details : '—' }}</td>
                                    <td class="px-2 py-2">
                                        @if(!empty($v->image_url))
                                            @php $imgUrl = ProctoringImageUrl::resolve($v->image_url); @endphp
                                            @if($imgUrl)
                                                <button type="button" class="session-img-thumb" data-session-full-img="{{ $imgUrl }}" data-session-img-alt="Violation image {{ $idx + 1 }}" aria-label="Open violation image">
                                                    <img src="{{ $imgUrl }}" alt="Violation image {{ $idx + 1 }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-black/5" loading="lazy">
                                                </button>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 shrink-0 text-[11px] leading-relaxed text-gray-400">Critical events auto-submit: phone, screenshot, tab switch, multiple faces, resize/fullscreen exit, another window, copy/paste, multiple IP.</p>
            @endif
        </section>
    </div>

    {{-- Question review --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4 sm:p-5 flex flex-col max-h-[min(36rem,70vh)]">
        <h2 class="text-sm font-semibold text-gray-900 tracking-tight mb-3 shrink-0">Question review</h2>
        @php
            $assignedQuestions = $assignedQuestions ?? collect();
            $answersByQuestion = $session->answers->keyBy(fn ($a) => (int) $a->question_id);
            $assignedCorrect = $session->assigned_correct_answers ?? [];
            $shuffledByQuestion = $session->shuffled_question_options ?? [];
        @endphp
        @if($assignedQuestions->isEmpty())
            <p class="text-xs text-gray-400">No assigned question snapshot found for this session.</p>
        @else
            <style>
                .session-panel-scroll {
                    overflow-y: auto;
                    overscroll-behavior: contain;
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }
                .session-panel-scroll::-webkit-scrollbar { width: 0; height: 0; display: none; }
            </style>
            <div class="session-panel-scroll space-y-2 flex-1 min-h-0 pr-0.5">
                @foreach($assignedQuestions as $idx => $question)
                    @php
                        $answer = $answersByQuestion->get((int) $question->id);
                        $studentAnswerRaw = trim((string) ($answer?->student_answer ?? ''));
                        $sessionCorrect = $assignedCorrect[$question->id] ?? $assignedCorrect[(string) $question->id] ?? ($question->correct_answer ?? '');
                        $isAnswered = $studentAnswerRaw !== '';
                        $isCorrect = $isAnswered && strtoupper($studentAnswerRaw) === strtoupper(trim((string) $sessionCorrect));
                        $opts = $shuffledByQuestion[$question->id] ?? $shuffledByQuestion[(string) $question->id] ?? ($question->options ?? []);
                        $studentAnswerText = null;
                        $correctText = null;
                        if (is_array($opts)) {
                            foreach ($opts as $opt) {
                                $k = is_array($opt) ? (string) ($opt['key'] ?? '') : (string) $opt;
                                $t = is_array($opt) ? (string) ($opt['text'] ?? $k) : (string) $opt;
                                if ($k === $studentAnswerRaw) {
                                    $studentAnswerText = $t;
                                }
                                if ($k === trim((string) $sessionCorrect)) {
                                    $correctText = $t;
                                }
                            }
                        }
                        $reason = null;
                        if (!$isAnswered) {
                            $reason = 'Not answered by student.';
                        } elseif (!$isCorrect) {
                            $reason = trim((string) ($question->explanation_wrong ?? '')) !== '' ? $question->explanation_wrong : ($answer?->explanation_wrong ?? null);
                        }
                    @endphp
                    <div class="rounded-xl border border-gray-200 bg-white px-3.5 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[13px] font-medium text-gray-900 leading-snug">
                                <span class="text-gray-400 font-normal tabular-nums mr-1">{{ $idx + 1 }}.</span>
                                {{ $question->text }}
                            </p>
                            <span class="shrink-0 text-[11px] font-semibold tabular-nums {{ $isCorrect ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $isCorrect ? 'Correct' : 'Wrong' }}
                            </span>
                        </div>
                        <div class="mt-2 grid gap-1 text-xs text-gray-600">
                            <p>
                                <span class="text-gray-400">Student</span>
                                @if($isAnswered)
                                    · {{ $studentAnswerRaw }}@if($studentAnswerText !== null) — {{ $studentAnswerText }}@endif
                                @else
                                    · <span class="text-rose-600">Not answered</span>
                                @endif
                            </p>
                            <p>
                                <span class="text-gray-400">Correct</span>
                                · {{ $sessionCorrect }}@if($correctText !== null) — {{ $correctText }}@endif
                            </p>
                            @if($reason)
                                <p class="text-gray-500"><span class="text-gray-400">Note</span> · {{ $reason }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

{{-- Lightbox --}}
<div id="session-img-lightbox" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" aria-label="View image">
    <button type="button" id="session-img-lightbox-close" class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 focus:outline-none" aria-label="Close">×</button>
    <img id="session-img-lightbox-img" src="" alt="" class="max-w-full max-h-[85vh] w-auto h-auto object-contain rounded-2xl">
</div>

<script>
(function() {
    var lightbox = document.getElementById('session-img-lightbox');
    var lightboxImg = document.getElementById('session-img-lightbox-img');
    var closeBtn = document.getElementById('session-img-lightbox-close');
    if (!lightbox || !lightboxImg) return;
    function open(src, alt) { lightboxImg.src = src; lightboxImg.alt = alt || ''; lightbox.classList.remove('hidden'); lightbox.classList.add('flex'); document.body.style.overflow = 'hidden'; }
    function close() { lightbox.classList.add('hidden'); lightbox.classList.remove('flex'); document.body.style.overflow = ''; }
    document.querySelectorAll('.session-img-thumb').forEach(function(btn) {
        btn.addEventListener('click', function() { var s = btn.getAttribute('data-session-full-img'); if (s) open(s, btn.getAttribute('data-session-img-alt')); });
    });
    if (closeBtn) closeBtn.addEventListener('click', close);
    lightbox.addEventListener('click', function(e) { if (e.target === lightbox) close(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') close(); });
})();
</script>
@endsection
