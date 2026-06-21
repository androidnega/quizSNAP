<?php

namespace App\Services\Operations;

use App\Events\Operations\OperationsCommandCenterUpdated;
use App\Models\MonitoringUserSession;
use App\Models\OperationsExamIncident;
use App\Models\OperationsAlert;
use App\Models\SecurityEvent;
use App\Models\ServerHealthSnapshot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OperationsCommandCenterService
{
    public function payload(): array
    {
        return Cache::remember('operations:command-center', 5, fn () => $this->build());
    }

    public function broadcast(): void
    {
        broadcast(new OperationsCommandCenterUpdated($this->build()))->toOthers();
    }

    protected function build(): array
    {
        $liveExams = app(OperationsLiveExamService::class)->snapshot();
        $attendance = app(OperationsAttendanceService::class)->snapshot();
        $proctoring = app(OperationsProctoringService::class)->snapshot();

        $health = Schema::hasTable('server_health_snapshots')
            ? ServerHealthSnapshot::query()->orderByDesc('recorded_at')->first()
            : null;

        $openIncidents = Schema::hasTable('operations_exam_incidents')
            ? OperationsExamIncident::query()->where('status', '!=', OperationsExamIncident::STATUS_RESOLVED)->count()
            : 0;

        $unreadAlerts = app(OperationsAlertService::class)->unreadCount();

        $onlineUsers = Schema::hasTable('monitoring_user_sessions')
            ? MonitoringUserSession::query()->where('is_active', true)->where('last_activity_at', '>=', now()->subMinutes(15))->count()
            : 0;

        $securityAlerts = Schema::hasTable('security_events')
            ? SecurityEvent::query()->where('occurred_at', '>=', now()->subDay())->count()
            : 0;

        return [
            'active_exams' => $liveExams['summary']['active_exams'] ?? 0,
            'students_writing' => $liveExams['summary']['students_active'] ?? 0,
            'students_completed' => $liveExams['summary']['students_completed'] ?? 0,
            'students_disconnected' => $liveExams['summary']['students_disconnected'] ?? 0,
            'avg_progress' => $liveExams['summary']['avg_progress'] ?? 0,
            'submissions_per_minute' => $liveExams['summary']['submissions_per_minute'] ?? 0,
            'suspicious_activities' => $proctoring['summary']['flagged_students'] ?? 0,
            'attendance_rate' => $attendance['attendance_rate'] ?? 0,
            'open_incidents' => $openIncidents,
            'unread_alerts' => $unreadAlerts,
            'users_online' => $onlineUsers,
            'security_alerts' => $securityAlerts,
            'system_health' => $health ? [
                'status' => $health->status,
                'cpu' => $health->cpu_usage,
                'ram' => $health->ram_usage,
                'disk' => $health->disk_usage,
            ] : null,
            'feed' => array_slice(array_merge(
                $liveExams['feed'] ?? [],
                $attendance['activity_feed'] ?? [],
                $proctoring['feed'] ?? []
            ), 0, 30),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
