<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\Result;
use App\Support\QuestionTypes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdjustQuizSessionResultCommand extends Command
{
    protected $signature = 'quiz:adjust-session-result
        {session : Quiz session id, e.g. 2000}
        {--correct= : New correct answer count}
        {--total= : Total questions (defaults to the existing result total)}
        {--quiz= : Optional quiz id to verify, e.g. 56}
        {--sync-answers : Also rewrite stored answers to match the correct/wrong counts}
        {--dry-run : Show the change without saving}
        {--force : Save without confirmation prompt}';

    protected $description = 'Update a stored quiz session result (score and correct/total counts), optionally syncing answers.';

    public function handle(): int
    {
        $sessionId = (int) $this->argument('session');
        $correct = $this->option('correct');
        $dryRun = (bool) $this->option('dry-run');
        $syncAnswers = (bool) $this->option('sync-answers');

        if ($correct === null || $correct === '') {
            $this->error('Provide --correct= with the new correct count (e.g. --correct=16).');

            return Command::FAILURE;
        }

        $targetCorrect = max(0, (int) $correct);

        $session = QuizSession::query()
            ->with(['result', 'quiz', 'answers'])
            ->find($sessionId);

        if (! $session) {
            $this->error('Session #'.$sessionId.' not found.');

            return Command::FAILURE;
        }

        $quizId = $this->option('quiz');
        if ($quizId !== null && $quizId !== '' && (int) $quizId !== (int) $session->quiz_id) {
            $this->error('Session #'.$sessionId.' belongs to quiz #'.$session->quiz_id.', not #'.$quizId.'.');

            return Command::FAILURE;
        }

        $result = $session->result;
        if (! $result) {
            $this->error('Session #'.$sessionId.' has no result row yet.');

            return Command::FAILURE;
        }

        $totalOption = $this->option('total');
        $total = $totalOption !== null && $totalOption !== ''
            ? max(1, (int) $totalOption)
            : max(1, (int) $result->total_questions);

        if ($targetCorrect > $total) {
            $this->error('Correct count ('.$targetCorrect.') cannot exceed total ('.$total.').');

            return Command::FAILURE;
        }

        $score = round(100 * $targetCorrect / $total, 2);
        $score = min($score, 100.0);

        $this->line('Session: #'.$session->id);
        $this->line('Quiz: #'.$session->quiz_id.' — '.($session->quiz?->title ?? 'n/a'));
        $this->line('Index: '.$session->student_index);
        $this->line('Before: '.$result->correct_count.'/'.$result->total_questions.' ('.round((float) $result->score, 1).'%)');
        $this->line('After:  '.$targetCorrect.'/'.$total.' ('.$score.'%)');
        if ($syncAnswers) {
            $this->line('Answers: will rewrite so Question Review shows '.$targetCorrect.' correct and '.($total - $targetCorrect).' wrong.');
        }

        if ($dryRun) {
            $this->warn('Dry run only — no changes saved.');

            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Save this result change?', true)) {
            $this->warn('Cancelled.');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($result, $targetCorrect, $total, $score, $session, $syncAnswers): void {
            if ($syncAnswers) {
                $this->syncSessionAnswers($session, $targetCorrect, $total);
            }

            Result::query()->whereKey($result->id)->update([
                'correct_count' => $targetCorrect,
                'total_questions' => $total,
                'score' => $score,
            ]);

            $session->touch();
        });

        $result->refresh();
        $this->info('Updated session #'.$session->id.' result to '.$result->correct_count.'/'.$result->total_questions.' ('.round((float) $result->score, 1).'%).');
        if ($syncAnswers) {
            $this->info('Stored answers synced to match that score.');
        }
        $this->line('Admin: /dashboard/quizzes/'.$session->quiz_id.'/sessions/'.$session->id);

        return Command::SUCCESS;
    }

    private function syncSessionAnswers(QuizSession $session, int $targetCorrect, int $total): void
    {
        $lockedIds = array_values(array_map('intval', (array) ($session->assigned_question_ids ?? [])));
        if ($lockedIds === []) {
            $lockedIds = $session->answers->pluck('question_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        }

        if (count($lockedIds) < $total) {
            throw new \RuntimeException('Session has fewer than '.$total.' assigned questions; cannot sync answers.');
        }

        $lockedIds = array_slice($lockedIds, 0, $total);
        $correctSnapshot = (array) ($session->assigned_correct_answers ?? []);
        $shuffledOptions = (array) ($session->shuffled_question_options ?? []);
        $questionsById = Question::whereIn('id', $lockedIds)->get()->keyBy('id');
        $answersByQuestion = $session->answers->keyBy(fn ($a) => (int) $a->question_id);

        Answer::where('quiz_session_id', $session->id)->delete();

        $answerTime = ($session->start_time ?? now()->subHour())->copy();
        foreach ($lockedIds as $offset => $questionId) {
            $question = $questionsById->get($questionId);
            $correctAnswer = (string) ($correctSnapshot[$questionId] ?? $correctSnapshot[(string) $questionId] ?? $question?->correct_answer ?? '');
            $shouldBeCorrect = $offset < $targetCorrect;
            $studentAnswer = $shouldBeCorrect
                ? $correctAnswer
                : $this->wrongAnswerForQuestion(
                    $question,
                    $correctAnswer,
                    $shuffledOptions[$questionId] ?? $shuffledOptions[(string) $questionId] ?? []
                );

            $existing = $answersByQuestion->get($questionId);
            $answerTime = $existing?->answered_at
                ?? $answerTime->copy()->addSeconds(random_int(45, 120));

            Answer::create([
                'quiz_session_id' => $session->id,
                'question_id' => $questionId,
                'student_answer' => $studentAnswer,
                'answered_at' => $answerTime,
            ]);
        }
    }

    /**
     * @param  array<int, array{key?: string, text?: string}|string>  $options
     */
    private function wrongAnswerForQuestion(?Question $question, string $correctAnswer, array $options): string
    {
        $type = QuestionTypes::normalize((string) ($question?->type ?? 'mcq'));

        if ($type === QuestionTypes::TRUE_FALSE) {
            $upper = strtoupper(trim($correctAnswer));

            return match ($upper) {
                'TRUE', 'T', 'A' => 'B',
                'FALSE', 'F', 'B' => 'A',
                default => $upper === 'TRUE' ? 'FALSE' : 'TRUE',
            };
        }

        if ($type === QuestionTypes::FILL_IN) {
            return trim($correctAnswer).'x';
        }

        foreach ($options as $opt) {
            $key = is_array($opt) ? (string) ($opt['key'] ?? '') : (string) $opt;
            if ($key !== '' && strtoupper($key) !== strtoupper(trim($correctAnswer))) {
                return $key;
            }
        }

        return strtoupper(trim($correctAnswer)) === 'A' ? 'B' : 'A';
    }
}
