<?php

namespace App\Services;

use App\Models\QuizSession;
use App\Models\Result;
use App\Models\Student;
use App\Models\SupportSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardChartsService
{
    public function dashboardCharts(string $period = '30d'): array
    {
        return Cache::remember('admin_dashboard_charts:'.$period, 120, function () use ($period) {
            [$since, $bucket] = $this->resolveRange($period);

            return [
                'period' => $period,
                'quiz_activity' => $this->countSeries('quiz_sessions', 'start_time', $since, $bucket),
                'exam_submissions' => $this->countSeries('results', 'submitted_at', $since, $bucket),
                'student_growth' => $this->countSeries('students', 'created_at', $since, $bucket),
                'live_support' => Schema::hasTable('support_sessions')
                    ? $this->countSeries('support_sessions', 'created_at', $since, $bucket)
                    : ['labels' => [], 'values' => []],
                'avg_exam_scores' => $this->avgScoreSeries($since, $bucket),
                'staff_roles' => $this->staffRoleBreakdown(),
                'quiz_outcomes' => $this->quizOutcomeBreakdown($since),
                'support_status' => $this->supportStatusBreakdown(),
                'insights' => $this->buildInsights($since),
            ];
        });
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
    private function countSeries(string $table, string $column, $since, string $bucket): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return ['labels' => [], 'values' => []];
        }

        $bucketSql = $this->bucketExpression($column, $bucket);
        $rows = DB::table($table)
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
            ->where($column, '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->map(fn ($v) => (string) $v)->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** @return array{labels: list<string>, values: list<float>} */
    private function avgScoreSeries($since, string $bucket): array
    {
        if (! Schema::hasTable('results') || ! Schema::hasColumn('results', 'submitted_at')) {
            return ['labels' => [], 'values' => []];
        }

        $bucketSql = $this->bucketExpression('submitted_at', $bucket);
        $rows = DB::table('results')
            ->selectRaw("{$bucketSql} as bucket, AVG(score) as avg_score")
            ->where('submitted_at', '>=', $since)
            ->whereNotNull('score')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->map(fn ($v) => (string) $v)->all(),
            'values' => $rows->pluck('avg_score')->map(fn ($v) => round((float) $v, 1))->all(),
        ];
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function staffRoleBreakdown(): array
    {
        if (! Schema::hasTable('users')) {
            return ['labels' => [], 'values' => []];
        }

        $rows = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('role')->map(fn ($r) => ucwords(str_replace('_', ' ', (string) $r)))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function quizOutcomeBreakdown($since): array
    {
        if (! Schema::hasTable('results')) {
            return ['labels' => ['Pass', 'Fail'], 'values' => [0, 0]];
        }

        $pass = Result::query()->where('submitted_at', '>=', $since)->where('score', '>=', 50)->count();
        $fail = Result::query()->where('submitted_at', '>=', $since)->where('score', '<', 50)->count();

        return [
            'labels' => ['Pass (≥50%)', 'Below 50%'],
            'values' => [(int) $pass, (int) $fail],
        ];
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function supportStatusBreakdown(): array
    {
        if (! Schema::hasTable('support_sessions')) {
            return ['labels' => [], 'values' => []];
        }

        $rows = SupportSession::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return [
            'labels' => $rows->pluck('status')->map(fn ($s) => ucfirst((string) $s))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** @return list<string> */
    private function buildInsights($since): array
    {
        $insights = [];

        if (Schema::hasTable('support_sessions')) {
            $waiting = SupportSession::query()->where('status', SupportSession::STATUS_WAITING)->count();
            if ($waiting > 0) {
                $insights[] = "{$waiting} live support chat(s) waiting for an agent — assign staff or enable alerts.";
            }
        }

        if (Schema::hasTable('quiz_sessions')) {
            $activeQuizzes = QuizSession::query()
                ->whereNull('ended_at')
                ->where('start_time', '>=', now()->subHours(2))
                ->count();
            if ($activeQuizzes > 0) {
                $insights[] = "{$activeQuizzes} quiz session(s) may still be in progress — check Operations Center.";
            }
        }

        if (Schema::hasTable('results')) {
            $recent = Result::query()->where('submitted_at', '>=', $since)->avg('score');
            if ($recent !== null) {
                $insights[] = 'Average exam score in this period: '.round((float) $recent, 1).'%.';
            }
        }

        if (Schema::hasTable('students')) {
            $newStudents = Student::query()->where('created_at', '>=', $since)->count();
            if ($newStudents > 0) {
                $insights[] = "{$newStudents} new student record(s) added in this period.";
            }
        }

        if ($insights === []) {
            $insights[] = 'Platform activity looks steady. Use the charts below to spot trends early.';
        }

        return $insights;
    }
}
