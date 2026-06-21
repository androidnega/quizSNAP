<?php

namespace App\Services\Monitoring;

use App\Models\ApiRequestLog;
use App\Models\DatabaseQueryLog;
use App\Models\MonitoringUserSession;
use App\Models\PerformanceLog;
use App\Models\SecurityEvent;
use App\Models\SystemAuditLog;
use App\Models\SystemError;
use App\Models\SystemErrorOccurrence;
use App\Services\LiveQuizSessionService;
use App\Services\SitePresenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitoringOverviewService
{
    public function dashboardStats(): array
    {
        $today = now()->startOfDay();

        return [
            'errors_today' => $this->countIfTable('system_errors', fn ($q) => $q->where('last_seen_at', '>=', $today)),
            'critical_errors' => $this->countIfTable('system_errors', fn ($q) => $q->whereIn('severity', ['critical', 'fatal'])->where('resolution_status', 'open')),
            'failed_jobs' => $this->countIfTable('failed_jobs'),
            'active_users' => app(SessionMonitoringService::class)->activeCount(),
            'security_alerts' => $this->countIfTable('security_events', fn ($q) => $q->where('occurred_at', '>=', $today)),
            'api_requests_today' => $this->countIfTable('api_request_logs', fn ($q) => $q->where('occurred_at', '>=', $today)),
            'server_health' => app(ServerHealthService::class)->latest(),
            'queue' => app(QueueMonitoringService::class)->stats(),
            'live_visitors' => app(SitePresenceService::class)->countActive(),
            'live_quiz_takers' => app(LiveQuizSessionService::class)->countActive(),
        ];
    }

    /** Safe defaults when monitoring tables or metrics are unavailable. */
    public function fallbackDashboardStats(): array
    {
        return [
            'errors_today' => 0,
            'critical_errors' => 0,
            'failed_jobs' => 0,
            'active_users' => 0,
            'security_alerts' => 0,
            'api_requests_today' => 0,
            'server_health' => null,
            'queue' => ['pending' => 0, 'failed' => 0, 'recent_failed' => 0, 'workers' => 0],
            'live_visitors' => 0,
            'live_quiz_takers' => 0,
        ];
    }

    public function errorsByHour(int $hours = 24): array
    {
        if (! Schema::hasTable('system_error_occurrences')) {
            return [];
        }

        try {
            $bucket = $this->hourBucketExpression('occurred_at');

            return SystemErrorOccurrence::query()
                ->selectRaw("{$bucket} as hour, COUNT(*) as total")
                ->where('occurred_at', '>=', now()->subHours($hours))
                ->groupByRaw($bucket)
                ->orderBy('hour')
                ->pluck('total', 'hour')
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    public function requestsByHour(int $hours = 24): array
    {
        if (! Schema::hasTable('api_request_logs')) {
            return [];
        }

        try {
            $bucket = $this->hourBucketExpression('occurred_at');

            return ApiRequestLog::query()
                ->selectRaw("{$bucket} as hour, COUNT(*) as total")
                ->where('occurred_at', '>=', now()->subHours($hours))
                ->groupByRaw($bucket)
                ->orderBy('hour')
                ->pluck('total', 'hour')
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    public function recentErrors(int $limit = 10)
    {
        if (! Schema::hasTable('system_errors')) {
            return collect();
        }

        try {
            return SystemError::query()->orderByDesc('last_seen_at')->limit($limit)->get();
        } catch (\Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function recentActivity(int $limit = 20)
    {
        if (! Schema::hasTable('system_audit_logs')) {
            return collect();
        }

        try {
            return SystemAuditLog::query()->orderByDesc('occurred_at')->limit($limit)->get();
        } catch (\Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function liveQuizStats(): array
    {
        $service = app(LiveQuizSessionService::class);

        return [
            'active_takers' => $service->countActive(),
            'active_quizzes' => $service->activeSessionsQuery()->distinct('quiz_id')->count('quiz_id'),
        ];
    }

    protected function countIfTable(string $table, ?callable $callback = null): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        try {
            $query = DB::table($table);
            if ($callback) {
                $callback($query);
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    protected function hourBucketExpression(string $column): string
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return "strftime('%Y-%m-%d %H:00', {$column})";
        }

        return "DATE_FORMAT({$column}, '%Y-%m-%d %H:00')";
    }
}
