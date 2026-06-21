<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringCommandCenterUpdated;
use App\Models\MonitoringCapacitySnapshot;

class CommandCenterService
{
    public function payload(): array
    {
        $overview = app(MonitoringOverviewService::class);
        $stats = $overview->dashboardStats();
        $health = $stats['server_health'];
        $reverb = app(ReverbAnalyticsService::class)->snapshot();
        $quiz = app(LiveQuizMonitorService::class)->snapshot();
        $attendance = app(LiveAttendanceMonitorService::class)->snapshot();

        return [
            'critical_errors' => $stats['critical_errors'] ?? 0,
            'errors_today' => $stats['errors_today'] ?? 0,
            'active_users' => $stats['active_users'] ?? 0,
            'live_quiz_takers' => $stats['live_quiz_takers'] ?? 0,
            'quiz' => $quiz,
            'attendance' => $attendance,
            'cpu' => $health?->cpu_usage,
            'ram' => $health?->ram_usage,
            'disk' => $health?->disk_usage,
            'storage_usage' => $health?->storage_usage_bytes,
            'queue' => $stats['queue'] ?? [],
            'security_alerts' => $stats['security_alerts'] ?? 0,
            'websocket' => $reverb,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcast(): void
    {
        broadcast(new MonitoringCommandCenterUpdated($this->payload()))->toOthers();
    }
}
