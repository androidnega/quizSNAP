<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\QuizSession;

final class DashboardQuizState
{
    /**
     * Shared quiz card / mobile panel state for the student dashboard overview.
     *
     * @return array<string, mixed>
     */
    public static function resolve(
        ?Quiz $scheduledQuiz,
        ?QuizSession $scheduledQuizSession,
        ?QuizSession $scheduledOpenSession,
        ?QuizSession $lastQuiz,
    ): array {
        $hasScheduled = $scheduledQuiz !== null;
        $hasScheduledResult = $scheduledQuizSession?->result !== null;
        $scheduledInProgress = $hasScheduled && $scheduledOpenSession !== null;
        $scheduledUpcoming = $hasScheduled
            && ! $scheduledInProgress
            && $scheduledQuiz->starts_at
            && $scheduledQuiz->starts_at->isFuture();
        $scheduledReady = $hasScheduled
            && ! $hasScheduledResult
            && ! $scheduledUpcoming
            && ! $scheduledInProgress;
        $showLastQuiz = $lastQuiz !== null
            && $lastQuiz->result !== null
            && ! $scheduledReady
            && ! $scheduledInProgress
            && ! $scheduledUpcoming;

        $countdownSeconds = ($scheduledUpcoming && $scheduledQuiz->starts_at)
            ? max(0, $scheduledQuiz->starts_at->getTimestamp() - now()->getTimestamp())
            : 0;
        $countdownHours = intdiv($countdownSeconds, 3600);
        $countdownMinutes = intdiv($countdownSeconds % 3600, 60);
        $countdownSecs = $countdownSeconds % 60;
        $countdownInitial = $countdownHours > 0
            ? sprintf('%d:%02d:%02d', $countdownHours, $countdownMinutes, $countdownSecs)
            : sprintf('%d:%02d', $countdownMinutes, $countdownSecs);

        $quizActionHref = route('dashboard.my-quizzes');
        if ($hasScheduled && $hasScheduledResult) {
            $quizActionHref = route('dashboard.my-quizzes.show', ['sessionId' => $scheduledQuizSession->id]);
        } elseif ($scheduledInProgress && $scheduledOpenSession !== null) {
            $quizActionHref = route('dashboard.resume-quiz', ['session' => $scheduledOpenSession->id]);
        } elseif ($scheduledReady) {
            $quizActionHref = route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]);
        } elseif ($scheduledUpcoming) {
            $quizActionHref = route('student.quiz-will-start', ['token' => $scheduledQuiz->link_token]);
        } elseif ($hasScheduled) {
            $quizActionHref = route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]);
        }

        $quizRulesUrl = ($hasScheduled && $scheduledQuiz->link_token)
            ? route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token])
            : null;

        $showMobileQuizBar = ! $showLastQuiz && ($scheduledInProgress || $scheduledUpcoming || $scheduledReady);
        $scheduledQuizCourse = $hasScheduled ? ($scheduledQuiz->course?->name ?? null) : null;
        $scheduledQuizTypeLabel = $hasScheduled ? $scheduledQuiz->getExamTypeLabel() : null;
        $scheduledQuizExamType = $hasScheduled ? ($scheduledQuiz->exam_type ?? Quiz::EXAM_TYPE_QUIZ) : null;

        $quizProgressPercent = 0;
        if ($hasScheduledResult) {
            $quizProgressPercent = 100;
        } elseif ($scheduledInProgress) {
            $quizProgressPercent = 55;
        } elseif ($scheduledReady) {
            $quizProgressPercent = 20;
        } elseif ($scheduledUpcoming) {
            $quizProgressPercent = 8;
        }

        return compact(
            'hasScheduled',
            'hasScheduledResult',
            'scheduledInProgress',
            'scheduledUpcoming',
            'scheduledReady',
            'showLastQuiz',
            'countdownSeconds',
            'countdownHours',
            'countdownMinutes',
            'countdownSecs',
            'countdownInitial',
            'quizActionHref',
            'quizRulesUrl',
            'showMobileQuizBar',
            'scheduledQuizCourse',
            'scheduledQuizTypeLabel',
            'scheduledQuizExamType',
            'quizProgressPercent',
        );
    }
}
