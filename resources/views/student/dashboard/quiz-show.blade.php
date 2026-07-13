@extends('layouts.student-dashboard')

@section('title', isset($session->quiz->title) ? $session->quiz->title : 'Quiz result')
@php
    $dashboardTitle = 'Past quiz';
@endphp

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full">
@php
    $quiz = $session->quiz ?? null;
    $classGroupLabel = $quiz?->classGroup?->display_name ?? $quiz?->classGroup?->name ?? $quiz?->academicClass?->display_label ?? null;
    $hasScore = isset($session->result) && $session->result && $quiz && $quiz->canShowScore();
    $isWithheld = $session->isResultWithheld();
    $canShowFull = $quiz && $quiz->canShowFullReview();
    $reviewWindowOpen = isset($showFullReview) && $showFullReview;
    $reviewQuestions = $reviewQuestions ?? collect();
    $answersByQuestion = $session->answers->keyBy(fn ($a) => (int) $a->question_id);
@endphp

<a href="{{ route('dashboard.my-quizzes') }}" class="text-sm text-slate-500 font-medium hover:text-slate-800 inline-block mb-4">← My quizzes</a>

<header class="flex flex-wrap items-start justify-between gap-4 mb-6 min-w-0">
    <div class="min-w-0 flex-1">
        <h1 class="text-xl font-semibold text-slate-800 tracking-tight break-words">{{ isset($quiz->title) ? $quiz->title : 'Quiz' }}</h1>
        <p class="text-sm text-slate-500 mt-1">
            Taken {{ $session->created_at ? $session->created_at->format('M j, Y g:i A') : 'Date not available' }}
            @if($reviewWindowOpen)
            · Question review available for 21 days
            @endif
        </p>
        @if(!empty($classGroupLabel))
        <p class="text-sm text-slate-500 mt-1">Class group: {{ $classGroupLabel }}</p>
        @endif
    </div>
    @if($hasScore && !$isWithheld)
        <a href="{{ route('dashboard.my-quizzes.download-pdf', ['sessionId' => $session->id]) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 shrink-0" title="Download PDF">
            <i class="fas fa-file-pdf"></i> Download PDF
        </a>
    @endif
</header>

@if($hasScore)
<section class="mb-8 min-w-0 max-w-full" aria-label="Result">
        @if($isWithheld)
        <div class="bg-white rounded-xl border border-red-200 p-4 sm:p-5">
            <p class="text-sm font-semibold text-red-800">Result under review — will be released after review</p>
        </div>
        @else
        @php
            $score = round($session->result->score, 0);
            $correctCount = $session->result->correct_count;
            $totalQuestions = $session->result->total_questions;
            $scoreBg = 'bg-red-50 border-red-200';
            $scoreText = 'text-red-800';
            $label = 'Keep trying';
            if ($score >= 80) {
                $scoreBg = 'bg-green-50 border-green-200';
                $scoreText = 'text-green-800';
                $label = 'Excellent';
            } elseif ($score >= 60) {
                $scoreBg = 'bg-blue-50 border-blue-200';
                $scoreText = 'text-blue-800';
                $label = 'Good';
            } elseif ($score >= 40) {
                $scoreBg = 'bg-amber-50 border-amber-200';
                $scoreText = 'text-amber-800';
                $label = 'Average';
            }
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5">
            <h2 class="text-sm font-medium text-slate-700 mb-4">Your result</h2>
            <div class="flex flex-wrap items-center gap-4">
                <div class="rounded-lg border min-w-[6rem] px-6 py-4 flex flex-col items-center justify-center {{ $scoreBg }}">
                    <span class="text-2xl font-medium tabular-nums {{ $scoreText }}">{{ $score }}%</span>
                    <span class="text-xs font-medium {{ $scoreText }} mt-1">{{ $label }}</span>
                </div>
                <div class="rounded-lg border min-w-[6rem] px-6 py-4 flex flex-col items-center justify-center {{ $scoreBg }}">
                    <span class="text-xl font-medium tabular-nums {{ $scoreText }}">{{ $correctCount }} / {{ $totalQuestions }}</span>
                    <span class="text-xs font-medium {{ $scoreText }} mt-1">Correct</span>
                </div>
                <p class="text-sm text-slate-500 self-center">{{ $totalQuestions }} questions on your attempt</p>
            </div>
    </div>
        @endif
</section>
@endif

@if(!$isWithheld && $canShowFull && $reviewWindowOpen && $reviewQuestions->isNotEmpty())
<section class="mb-8 min-w-0 max-w-full overflow-hidden" aria-label="Questions and answers">
    <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 min-w-0 max-w-full">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                <h2 class="text-sm font-medium text-slate-700">Your questions</h2>
                <span class="text-xs text-slate-400 tabular-nums">{{ $reviewQuestions->count() }} assigned</span>
            </div>
            <p class="text-sm text-slate-500 mb-4">Only the questions assigned to your attempt are shown. Available for 21 days.</p>
            <div class="space-y-3 min-w-0 max-w-full max-h-[min(36rem,65vh)] overflow-y-auto dashboard-list-scroll">
                @foreach($reviewQuestions as $idx => $question)
                    @php
                        $answer = $answersByQuestion->get((int) $question->id);
                        $studentAnswerValue = trim((string) ($answer?->student_answer ?? ''));
                        $assignedCorrect = is_array($session->assigned_correct_answers) ? $session->assigned_correct_answers : [];
                        $sessionCorrect = $assignedCorrect[$question->id] ?? $assignedCorrect[(string) $question->id] ?? ($question->correct_answer ?? '');
                        $isAnswered = $studentAnswerValue !== '';
                        $correct = $isAnswered && strtoupper($studentAnswerValue) === strtoupper(trim((string) $sessionCorrect));

                        $shuffledOpts = null;
                        if (is_array($session->shuffled_question_options)) {
                            $shuffledOpts = $session->shuffled_question_options[$question->id] ?? $session->shuffled_question_options[(string) $question->id] ?? null;
                        }

                        $yourText = null;
                        $correctText = null;
                        if (is_array($shuffledOpts) && !empty($shuffledOpts)) {
                            foreach ($shuffledOpts as $o) {
                                $k = $o['key'] ?? $o;
                                $t = $o['text'] ?? $o;
                                if ((string) $k === $studentAnswerValue) {
                                    $yourText = $t;
                                }
                                if ((string) $k === trim((string) $sessionCorrect)) {
                                    $correctText = $t;
                                }
                            }
                        }

                        if (($yourText === null || $correctText === null) && isset($question->options) && is_array($question->options)) {
                            foreach ($question->options as $opt) {
                                if (!is_array($opt)) {
                                    continue;
                                }
                                $optKey = $opt['key'] ?? '';
                                $optText = $opt['text'] ?? '';
                                if ($yourText === null && (string) $optKey === $studentAnswerValue) {
                                    $yourText = $optText;
                                }
                                if ($correctText === null && (string) $optKey === trim((string) $sessionCorrect)) {
                                    $correctText = $optText;
                                }
                            }
                        }
                    @endphp

                    <div class="rounded-xl border p-3.5 min-w-0 w-full max-w-full {{ $correct ? 'border-emerald-200 bg-emerald-50/30' : 'border-red-200 bg-red-50/30' }}">
                        <p class="text-sm font-medium text-slate-800 mb-2 break-words">{{ $idx + 1 }}. {{ $question->text ?? 'Question not available' }}</p>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm mt-2 min-w-0">
                            <span class="text-slate-500 break-words min-w-0">Your answer: <strong class="text-slate-800 font-medium">{{ $isAnswered ? ($yourText !== null ? $studentAnswerValue . '. ' . $yourText : $studentAnswerValue) : 'Not answered' }}</strong></span>
                            <span class="text-green-700 break-words min-w-0">Correct: <strong class="font-medium">{{ $correctText !== null ? $sessionCorrect . '. ' . $correctText : ($sessionCorrect ?: '—') }}</strong></span>
                        </div>

                        @if(!$correct)
                            @php
                                $whyWrong = ! $isAnswered
                                    ? 'This question was not answered.'
                                    : ($question->explanation_wrong ?? $answer?->explanation_wrong ?? null);
                            @endphp
                            @if(!empty($whyWrong))
                                <div class="mt-3 pt-3 border-t border-slate-200 text-sm break-words min-w-0">
                                    <p class="text-red-700 text-xs"><strong>Reason:</strong> {{ $whyWrong }}</p>
                                </div>
                            @endif
                        @endif
                    </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!$isWithheld && isset($showFullReview) && !$showFullReview)
<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5">
        <p class="text-sm text-slate-500">Detailed review (questions and answers) is no longer available. It is kept for 21 days. Your score above is kept forever.</p>
    </div>
</section>
@endif

@if(!$isWithheld && $quiz && $quiz->canShowScore() && !$quiz->canShowFullReview())
<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5">
        <p class="text-sm text-slate-500">Answer review is not shown for this quiz.</p>
    </div>
</section>
@endif

@if(!$isWithheld && $canShowFull && $reviewWindowOpen && $reviewQuestions->isEmpty())
<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5">
        <p class="text-sm text-slate-500">No assigned questions found for this quiz session.</p>
    </div>
</section>
@endif
</div>
@endsection
