<?php

namespace App\Jobs;

use App\Models\Answer;
use App\Models\QuizSession;
use App\Services\AiQuestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generate AI wrong-answer explanations off the result page request path.
 */
class GenerateWrongAnswerExplanationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public int $quizSessionId,
    ) {}

    public function handle(AiQuestionService $aiService): void
    {
        $session = QuizSession::with(['answers.question'])->find($this->quizSessionId);
        if (! $session) {
            return;
        }

        $assignedCorrect = $session->assigned_correct_answers ?? [];

        foreach ($session->answers as $answer) {
            $question = $answer->question;
            if (! $question) {
                continue;
            }

            $sessionCorrect = $assignedCorrect[$question->id]
                ?? $assignedCorrect[(string) $question->id]
                ?? $question->correct_answer;

            if (trim((string) $answer->student_answer) === trim((string) $sessionCorrect)) {
                continue;
            }

            if (! empty($question->explanation_wrong) || ! empty($answer->explanation_wrong)) {
                continue;
            }

            $generated = $aiService->generateWrongAnswerExplanation($question, (string) $answer->student_answer);
            if ($generated !== null && $generated !== '') {
                Answer::whereKey($answer->id)->update(['explanation_wrong' => $generated]);
            }
        }
    }
}
