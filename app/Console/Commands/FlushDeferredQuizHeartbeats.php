<?php

namespace App\Console\Commands;

use App\Services\QuizConcurrencyService;
use Illuminate\Console\Command;

class FlushDeferredQuizHeartbeats extends Command
{
    protected $signature = 'quiz:flush-heartbeats';

    protected $description = 'Flush deferred quiz session heartbeats from Redis to MySQL';

    public function handle(QuizConcurrencyService $concurrency): int
    {
        $count = $concurrency->flushDeferredHeartbeats();
        if ($count > 0) {
            $this->line("Flushed {$count} heartbeat(s).");
        }

        return self::SUCCESS;
    }
}
