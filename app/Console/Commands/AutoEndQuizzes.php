<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use Illuminate\Console\Command;

class AutoEndQuizzes extends Command
{
    protected $signature = 'quizzes:auto-end';

    protected $description = 'End quizzes when their scheduled Ends At time is reached';

    public function handle(): int
    {
        $now = now();
        $endedByTime = 0;

        $pastEnd = Quiz::where('is_active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($pastEnd as $quiz) {
            $quiz->update(['is_active' => false]);
            $endedByTime++;
            $this->info("Ended quiz (time passed): {$quiz->title} (ID: {$quiz->id})");
        }

        if ($endedByTime === 0) {
            $this->info('No quizzes to auto-end at this time.');
        } else {
            $this->info("Ended {$endedByTime} quiz(es) by scheduled end time.");
        }

        return Command::SUCCESS;
    }
}
