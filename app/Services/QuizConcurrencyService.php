<?php

namespace App\Services;

use App\Models\QuizSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class QuizConcurrencyService
{
    private const PENDING_SET = 'quizsnap:heartbeats:pending';

    public function redisAvailable(): bool
    {
        if (! config('quiz-scale.defer_heartbeat_writes', true)) {
            return false;
        }
        if (! in_array(config('cache.default'), ['redis'], true)
            && config('session.driver') !== 'redis') {
            return false;
        }
        try {
            Redis::connection()->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Record heartbeat timestamp in Redis; MySQL is updated by the flush command.
     * Falls back to immediate DB write when Redis is unavailable.
     */
    public function touchHeartbeat(int $sessionId): void
    {
        if (! $this->redisAvailable()) {
            QuizSession::whereKey($sessionId)->update(['last_heartbeat_at' => now()]);

            return;
        }

        $redis = Redis::connection();
        $redis->setex('quizsnap:heartbeat:ts:' . $sessionId, 7200, (string) now()->timestamp);
        $redis->sadd(self::PENDING_SET, (string) $sessionId);
    }

    /**
     * Flush pending heartbeat timestamps to MySQL in one batch.
     */
    public function flushDeferredHeartbeats(): int
    {
        if (! $this->redisAvailable()) {
            return 0;
        }

        $redis = Redis::connection();
        $ids = $redis->smembers(self::PENDING_SET);
        if ($ids === [] || $ids === false) {
            return 0;
        }

        $pending = [];
        foreach ($ids as $id) {
            $sessionId = (int) $id;
            if ($sessionId < 1) {
                $redis->srem(self::PENDING_SET, $id);

                continue;
            }

            $ts = $redis->get('quizsnap:heartbeat:ts:' . $sessionId);
            if ($ts !== null && $ts !== false && $ts !== '') {
                $pending[$sessionId] = (int) $ts;
            }

            $redis->srem(self::PENDING_SET, $id);
        }

        if ($pending === []) {
            return 0;
        }

        return $this->batchUpdateHeartbeats($pending);
    }

    /**
     * Single-query violation aggregates for hot paths (show, recordViolation).
     *
     * @return array{by_type: array<string, int>, by_severity: array{warning: int, critical: int}}
     */
    public function violationAggregates(QuizSession $session): array
    {
        $rows = $session->violations()
            ->selectRaw('type, severity, COUNT(*) as aggregate')
            ->groupBy('type', 'severity')
            ->get();

        $byType = [];
        $bySeverity = ['warning' => 0, 'critical' => 0];

        foreach ($rows as $row) {
            $count = (int) $row->aggregate;
            $byType[$row->type] = ($byType[$row->type] ?? 0) + $count;
            if (isset($bySeverity[$row->severity])) {
                $bySeverity[$row->severity] += $count;
            }
        }

        return ['by_type' => $byType, 'by_severity' => $bySeverity];
    }

    /**
     * @return array<string, int>
     */
    public function violationCountsByType(QuizSession $session): array
    {
        return $this->violationAggregates($session)['by_type'];
    }

    /**
     * @return array{warning: int, critical: int}
     */
    public function violationCountsBySeverity(QuizSession $session): array
    {
        return $this->violationAggregates($session)['by_severity'];
    }

    /**
     * @param  array<int, int>  $sessionIdToTimestamp
     */
    private function batchUpdateHeartbeats(array $sessionIdToTimestamp): int
    {
        $ids = array_keys($sessionIdToTimestamp);
        $cases = [];
        $bindings = [];

        foreach ($sessionIdToTimestamp as $sessionId => $timestamp) {
            $cases[] = 'WHEN ? THEN FROM_UNIXTIME(?)';
            $bindings[] = $sessionId;
            $bindings[] = $timestamp;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids);

        return DB::update(
            'UPDATE quiz_sessions SET last_heartbeat_at = CASE id '
            . implode(' ', $cases)
            . ' END WHERE id IN (' . $placeholders . ') AND ended_at IS NULL',
            $bindings
        );
    }
}
