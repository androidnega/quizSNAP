<?php

namespace App\Console\Commands;

use App\Models\QuizSession;
use App\Models\Result;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdjustQuizSessionResultCommand extends Command
{
    protected $signature = 'quiz:adjust-session-result
        {session : Quiz session id, e.g. 2000}
        {--correct= : New correct answer count}
        {--total= : Total questions (defaults to the existing result total)}
        {--quiz= : Optional quiz id to verify, e.g. 56}
        {--dry-run : Show the change without saving}
        {--force : Save without confirmation prompt}';

    protected $description = 'Update a stored quiz session result (score and correct/total counts).';

    public function handle(): int
    {
        $sessionId = (int) $this->argument('session');
        $correct = $this->option('correct');
        $dryRun = (bool) $this->option('dry-run');

        if ($correct === null || $correct === '') {
            $this->error('Provide --correct= with the new correct count (e.g. --correct=16).');

            return Command::FAILURE;
        }

        $targetCorrect = max(0, (int) $correct);

        $session = QuizSession::query()
            ->with(['result', 'quiz'])
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

        if ($dryRun) {
            $this->warn('Dry run only — no changes saved.');

            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Save this result change?', true)) {
            $this->warn('Cancelled.');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($result, $targetCorrect, $total, $score, $session): void {
            Result::query()->whereKey($result->id)->update([
                'correct_count' => $targetCorrect,
                'total_questions' => $total,
                'score' => $score,
            ]);

            $session->touch();
        });

        $result->refresh();
        $this->info('Updated session #'.$session->id.' result to '.$result->correct_count.'/'.$result->total_questions.' ('.round((float) $result->score, 1).'%).');
        $this->line('Admin: /dashboard/quizzes/'.$session->quiz_id.'/sessions/'.$session->id);

        return Command::SUCCESS;
    }
}
