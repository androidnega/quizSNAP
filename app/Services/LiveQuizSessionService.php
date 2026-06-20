<?php

namespace App\Services;

use App\Models\QuizSession;
use Illuminate\Database\Eloquent\Builder;

class LiveQuizSessionService
{
    public const HEARTBEAT_SECONDS = 120;

    public const STARTED_GRACE_MINUTES = 5;

    /** Active quiz takers: started, not ended, recent heartbeat or just started. */
    public function activeSessionsQuery(): Builder
    {
        $heartbeatCutoff = now()->subSeconds(self::HEARTBEAT_SECONDS);
        $startedCutoff = now()->subMinutes(self::STARTED_GRACE_MINUTES);

        return QuizSession::query()
            ->whereNotNull('start_time')
            ->whereNull('ended_at')
            ->where(function ($q) use ($heartbeatCutoff, $startedCutoff) {
                $q->where('last_heartbeat_at', '>=', $heartbeatCutoff)
                    ->orWhere(function ($q2) use ($startedCutoff) {
                        $q2->whereNull('last_heartbeat_at')
                            ->where('start_time', '>=', $startedCutoff);
                    });
            });
    }

    public function countActive(): int
    {
        return (int) $this->activeSessionsQuery()->count();
    }
}
