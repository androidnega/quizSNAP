<?php

namespace App\Jobs;

use App\Events\DataUpdated;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AiQuestionService;
use App\Services\AiQuizGenerationProgress;
use App\Services\AiQuizTokenService;
use App\Services\QuizBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate AI quiz questions in the background to avoid HTTP timeouts.
 */
class GenerateQuizQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    /**
     * @param  array<int, array{name: string}>  $topics
     */
    public function __construct(
        public int $quizId,
        public array $topics,
        public int $target,
        public int $userId,
    ) {}

    public function handle(AiQuestionService $aiService): void
    {
        $quiz = Quiz::find($this->quizId);
        if (! $quiz) {
            return;
        }

        if (! $aiService->hasApiKey()) {
            AiQuizGenerationProgress::fail($this->quizId, 'DeepSeek API key not configured. Add a key in Settings → AI.');

            return;
        }

        $topicList = ! empty($this->topics) ? $this->topics : [['name' => 'General knowledge']];
        $target = max(1, $this->target);
        $sourceText = (string) ($quiz->script_text ?? '');
        if (mb_strlen($sourceText) > 50000) {
            $sourceText = mb_substr($sourceText, 0, 50000) . "\n[... truncated ...]";
        }

        $batchSize = 5;
        $maxBatches = (int) ceil($target / $batchSize) + 3;
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            for ($i = 0; $i < $maxBatches; $i++) {
                $poolCount = $quiz->questionPools()->count();
                $remaining = max(0, $target - $poolCount);
                if ($remaining <= 0) {
                    break 2;
                }
                $toGenerate = min($batchSize, $remaining);
                $aiService->generatePoolAndStore($quiz, $topicList, $toGenerate, $sourceText ?: null);
                $quiz->refresh();
                AiQuizGenerationProgress::update($this->quizId, $quiz->questionPools()->count());

                if ($quiz->questionPools()->count() === 0 && $i >= 2) {
                    break;
                }
            }
            if ($quiz->questionPools()->count() >= 1) {
                break;
            }
        }

        $generatedCount = $quiz->questionPools()->count();

        if ($generatedCount < 1) {
            $apiError = $aiService->getLastApiError();
            $msg = $apiError
                ? 'Question generation failed. ' . $apiError
                : 'Question generation failed. Check the DeepSeek API key and account balance in Settings → AI.';
            AiQuizGenerationProgress::fail($this->quizId, $msg);
            Log::warning('GenerateQuizQuestionsJob: no questions generated', [
                'quiz_id' => $this->quizId,
                'error' => $apiError,
            ]);

            return;
        }

        $user = User::find($this->userId);
        if ($user && $this->userId > 0) {
            app(AiQuizTokenService::class)->consume($user);
        }

        AiQuizGenerationProgress::complete($this->quizId, $generatedCount);

        try {
            broadcast(new DataUpdated('quizzes'))->toOthers();
        } catch (\Exception $e) {
            // Ignore broadcast errors
        }

        try {
            QuizBackupService::sendIfConfigured($quiz);
        } catch (\Throwable $e) {
            // Do not fail the job if backup send fails
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateQuizQuestionsJob failed', [
            'quiz_id' => $this->quizId,
            'message' => $e->getMessage(),
        ]);
        AiQuizGenerationProgress::fail(
            $this->quizId,
            'Question generation failed unexpectedly. Try again from the quiz overview.'
        );
    }
}
