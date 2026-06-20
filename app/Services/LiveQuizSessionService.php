<?php

namespace App\Services;

use App\Models\QuizSession;
use Illuminate\Database\Eloquent\Builder;

class LiveQuizSessionService
{
    public const HEARTBEAT_SECONDS = QuizConcurrencyService::LIVE_WINDOW_SECONDS;

    /** Active quiz takers: started, not ended, heartbeat within the live window (Redis-backed when available). */
    public function activeSessionsQuery(): Builder
    {
        $heartbeatCutoff = now()->subSeconds(self::HEARTBEAT_SECONDS);

        return QuizSession::query()
            ->whereNotNull('start_time')
            ->whereNull('ended_at')
            ->whereNotNull('last_heartbeat_at')
            ->where('last_heartbeat_at', '>=', $heartbeatCutoff);
    }

    public function countActive(): int
    {
        return app(QuizConcurrencyService::class)->countLiveQuizSessions();
    }
}
