<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard charts — examiner-only, scoped to that examiner's quizzes.
 */
class AdminDashboardChartsService
{
    public function dashboardCharts(string $period = '30d', ?User $examiner = null): array
    {
        $examinerId = $examiner?->id ?? 0;
        $cacheKey = 'examiner_dashboard_charts:'.$examinerId.':'.$period;

        return Cache::remember($cacheKey, 120, function () use ($period, $examiner) {
            [$since, $bucket] = $this->resolveRange($period);
            $quizIds = $this->examinerQuizIds($examiner);

            return [
                'period' => $period,
                'quiz_activity' => $this->sessionSeries($quizIds, $since, $bucket),
                'exam_submissions' => $this->resultCountSeries($quizIds, $since, $bucket),
                'insights' => $this->buildInsights($quizIds, $since),
            ];
        });
    }

    /** @return list<int> */
    private function examinerQuizIds(?User $examiner): array
    {
        if (! $examiner) {
            return [];
        }

        // Scope strictly by examiner_id so charts never mix other examiners' quizzes.
        return Quiz::query()
            ->where('examiner_id', $examiner->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array{0: \Illuminate\Support\Carbon, 1: string} */
    private function resolveRange(string $period): array
    {
        return match ($period) {
            '7d' => [now()->subDays(7)->startOfDay(), 'day'],
            '90d' => [now()->subDays(90)->startOfDay(), 'day'],
            default => [now()->subDays(30)->startOfDay(), 'day'],
        };
    }

    private function bucketExpression(string $column, string $bucket): string
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return $bucket === 'hour'
                ? "strftime('%Y-%m-%d %H:00', {$column})"
                : "strftime('%Y-%m-%d', {$column})";
        }

        $format = $bucket === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

        return "DATE_FORMAT({$column}, '{$format}')";
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function sessionSeries(array $quizIds, $since, string $bucket): array
    {
        if ($quizIds === [] || ! Schema::hasTable('quiz_sessions')) {
            return $this->padDailySeries([], $since);
        }

        $bucketSql = $this->bucketExpression('start_time', $bucket);
        $rows = DB::table('quiz_sessions')
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
            ->whereIn('quiz_id', $quizIds)
            ->where('start_time', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $this->padDailySeries($rows->pluck('total', 'bucket')->all(), $since);
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function resultCountSeries(array $quizIds, $since, string $bucket): array
    {
        if ($quizIds === [] || ! Schema::hasTable('results')) {
            return $this->padDailySeries([], $since);
        }

        $bucketSql = $this->bucketExpression('results.submitted_at', $bucket);
        $rows = DB::table('results')
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
            ->whereIn('quiz_sessions.quiz_id', $quizIds)
            ->where('results.submitted_at', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $this->padDailySeries($rows->pluck('total', 'bucket')->all(), $since);
    }

    /**
     * Fill every day from $since through today so curve charts always have a continuous axis.
     *
     * @param  array<string, int|float>  $byBucket
     * @return array{labels: list<string>, values: list<int|float>}
     */
    private function padDailySeries(array $byBucket, $since, bool $asFloat = false): array
    {
        $labels = [];
        $values = [];
        $cursor = $since->copy()->startOfDay();
        $end = now()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $key;
            $raw = $byBucket[$key] ?? 0;
            $values[] = $asFloat ? round((float) $raw, 1) : (int) $raw;
            $cursor->addDay();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return list<string> */
    private function buildInsights(array $quizIds, $since): array
    {
        $insights = [];

        if ($quizIds === []) {
            return ['Create and publish quizzes to see activity charts for your classes.'];
        }

        $active = QuizSession::query()
            ->whereIn('quiz_id', $quizIds)
            ->whereNull('ended_at')
            ->where('start_time', '>=', now()->subHours(2))
            ->count();
        if ($active > 0) {
            $insights[] = "{$active} of your quiz session(s) may still be in progress.";
        }

        $sessions = QuizSession::query()
            ->whereIn('quiz_id', $quizIds)
            ->where('start_time', '>=', $since)
            ->count();
        if ($sessions > 0) {
            $insights[] = "{$sessions} quiz session(s) started in this period.";
        }

        $submissions = Result::query()
            ->whereHas('quizSession', fn ($q) => $q->whereIn('quiz_id', $quizIds))
            ->where('submitted_at', '>=', $since)
            ->count();
        if ($submissions > 0) {
            $insights[] = "{$submissions} exam submission(s) in this period.";
        }

        if ($insights === []) {
            $insights[] = 'No recent activity yet — charts will fill as students take your quizzes.';
        }

        return $insights;
    }
}
