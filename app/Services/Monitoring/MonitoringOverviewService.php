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
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'active_users' => app(SessionMonitoringService::class)->activeCount(),
            'security_alerts' => $this->countIfTable('security_events', fn ($q) => $q->where('occurred_at', '>=', $today)),
            'api_requests_today' => $this->countIfTable('api_request_logs', fn ($q) => $q->where('occurred_at', '>=', $today)),
            'server_health' => app(ServerHealthService::class)->latest(),
            'queue' => app(QueueMonitoringService::class)->stats(),
            'live_visitors' => app(SitePresenceService::class)->countActive(),
            'live_quiz_takers' => app(LiveQuizSessionService::class)->countActive(),
        ];
    }

    public function errorsByHour(int $hours = 24): array
    {
        if (! Schema::hasTable('system_error_occurrences')) {
            return [];
        }

        return SystemErrorOccurrence::query()
            ->selectRaw('DATE_FORMAT(occurred_at, "%Y-%m-%d %H:00") as hour, COUNT(*) as total')
            ->where('occurred_at', '>=', now()->subHours($hours))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->all();
    }

    public function requestsByHour(int $hours = 24): array
    {
        if (! Schema::hasTable('api_request_logs')) {
            return [];
        }

        return ApiRequestLog::query()
            ->selectRaw('DATE_FORMAT(occurred_at, "%Y-%m-%d %H:00") as hour, COUNT(*) as total')
            ->where('occurred_at', '>=', now()->subHours($hours))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->all();
    }

    public function recentErrors(int $limit = 10)
    {
        if (! Schema::hasTable('system_errors')) {
            return collect();
        }

        return SystemError::query()->orderByDesc('last_seen_at')->limit($limit)->get();
    }

    public function recentActivity(int $limit = 20)
    {
        if (! Schema::hasTable('system_audit_logs')) {
            return collect();
        }

        return SystemAuditLog::query()->orderByDesc('occurred_at')->limit($limit)->get();
    }

    public function liveQuizStats(): array
    {
        $service = app(LiveQuizSessionService::class);

        return [
            'active_takers' => $service->countActive(),
            'active_quizzes' => $service->activeSessionsQuery()->distinct('quiz_id')->count('quiz_id'),
        ];
    }

    protected function countIfTable(string $table, callable $callback): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        $callback($query);

        return (int) $query->count();
    }
}
