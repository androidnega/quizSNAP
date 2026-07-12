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
                'avg_exam_scores' => $this->avgScoreSeries($quizIds, $since, $bucket),
                'quiz_outcomes' => $this->quizOutcomeBreakdown($quizIds, $since),
                'insights' => $this->buildInsights($quizIds, $since),
            ];
        });
    }

    /** @return list<int> */
    private function examinerQuizIds(?User $examiner): array
    {
        if (! $examiner || ! $examiner->isExaminer()) {
            return [];
        }

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
            return ['labels' => [], 'values' => []];
        }

        $bucketSql = $this->bucketExpression('start_time', $bucket);
        $rows = DB::table('quiz_sessions')
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
            ->whereIn('quiz_id', $quizIds)
            ->where('start_time', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->map(fn ($v) => (string) $v)->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function resultCountSeries(array $quizIds, $since, string $bucket): array
    {
        if ($quizIds === [] || ! Schema::hasTable('results')) {
            return ['labels' => [], 'values' => []];
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

        return [
            'labels' => $rows->pluck('bucket')->map(fn ($v) => (string) $v)->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** @return array{labels: list<string>, values: list<float>} */
    private function avgScoreSeries(array $quizIds, $since, string $bucket): array
    {
        if ($quizIds === [] || ! Schema::hasTable('results')) {
            return ['labels' => [], 'values' => []];
        }

        $bucketSql = $this->bucketExpression('results.submitted_at', $bucket);
        $rows = DB::table('results')
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->selectRaw("{$bucketSql} as bucket, AVG(results.score) as avg_score")
            ->whereIn('quiz_sessions.quiz_id', $quizIds)
            ->where('results.submitted_at', '>=', $since)
            ->whereNotNull('results.score')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->map(fn ($v) => (string) $v)->all(),
            'values' => $rows->pluck('avg_score')->map(fn ($v) => round((float) $v, 1))->all(),
        ];
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function quizOutcomeBreakdown(array $quizIds, $since): array
    {
        if ($quizIds === [] || ! Schema::hasTable('results')) {
            return ['labels' => ['Pass (≥50%)', 'Below 50%'], 'values' => [0, 0]];
        }

        $base = Result::query()
            ->whereHas('quizSession', fn ($q) => $q->whereIn('quiz_id', $quizIds))
            ->where('submitted_at', '>=', $since);

        $pass = (clone $base)->where('score', '>=', 50)->count();
        $fail = (clone $base)->where('score', '<', 50)->count();

        return [
            'labels' => ['Pass (≥50%)', 'Below 50%'],
            'values' => [(int) $pass, (int) $fail],
        ];
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

        $avg = Result::query()
            ->whereHas('quizSession', fn ($q) => $q->whereIn('quiz_id', $quizIds))
            ->where('submitted_at', '>=', $since)
            ->avg('score');
        if ($avg !== null) {
            $insights[] = 'Your average exam score this period: '.round((float) $avg, 1).'%.';
        }

        $sessions = QuizSession::query()
            ->whereIn('quiz_id', $quizIds)
            ->where('start_time', '>=', $since)
            ->count();
        if ($sessions > 0) {
            $insights[] = "{$sessions} quiz session(s) started in this period.";
        }

        if ($insights === []) {
            $insights[] = 'No recent activity yet — charts will fill as students take your quizzes.';
        }

        return $insights;
    }
}
