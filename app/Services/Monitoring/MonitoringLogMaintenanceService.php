<?php

namespace App\Services\Monitoring;

use App\Models\ApiRequestLog;
use App\Models\DatabaseQueryLog;
use App\Models\MonitoringBackup;
use App\Models\MonitoringCapacitySnapshot;
use App\Models\MonitoringDeployment;
use App\Models\MonitoringIncident;
use App\Models\MonitoringNotification;
use App\Models\MonitoringReverbMetric;
use App\Models\MonitoringUserSession;
use App\Models\PerformanceLog;
use App\Models\SecurityEvent;
use App\Models\ServerHealthSnapshot;
use App\Models\SystemAuditLog;
use App\Models\SystemError;
use App\Models\SystemErrorOccurrence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitoringLogMaintenanceService
{
    /** @return array<string, string> */
    public function categories(): array
    {
        return [
            'errors' => 'Error logs',
            'activity' => 'Audit / activity logs',
            'api' => 'API request logs',
            'database' => 'Database query logs',
            'performance' => 'Performance logs',
            'security' => 'Security events',
            'sessions' => 'User session tracking',
            'health' => 'Server health snapshots',
            'notifications' => 'Monitoring notifications',
            'websocket' => 'WebSocket / Reverb metrics',
            'capacity' => 'Capacity snapshots',
            'backups' => 'Backup scan history',
            'deployments' => 'Deployment records',
            'incidents' => 'Incident records',
            'failed_jobs' => 'Failed queue jobs',
        ];
    }

    /** @return array<string, int> */
    public function clear(string $category): array
    {
        return match ($category) {
            'errors' => $this->clearErrors(),
            'activity' => $this->clearModel(SystemAuditLog::class),
            'api' => $this->clearModel(ApiRequestLog::class),
            'database' => $this->clearModel(DatabaseQueryLog::class),
            'performance' => $this->clearModel(PerformanceLog::class),
            'security' => $this->clearModel(SecurityEvent::class),
            'sessions' => $this->clearModel(MonitoringUserSession::class),
            'health' => $this->clearModel(ServerHealthSnapshot::class),
            'notifications' => $this->clearModel(MonitoringNotification::class),
            'websocket' => $this->clearModel(MonitoringReverbMetric::class),
            'capacity' => $this->clearModel(MonitoringCapacitySnapshot::class),
            'backups' => $this->clearModel(MonitoringBackup::class),
            'deployments' => $this->clearModel(MonitoringDeployment::class),
            'incidents' => $this->clearModel(MonitoringIncident::class),
            'failed_jobs' => $this->clearFailedJobs(),
            'all' => $this->clearAll(),
            default => ['deleted' => 0],
        };
    }

    /** @return array<string, int> */
    public function clearAll(): array
    {
        $totals = ['deleted' => 0];
        foreach (array_keys($this->categories()) as $category) {
            $result = $this->clear($category);
            $totals['deleted'] += (int) ($result['deleted'] ?? 0);
            if (isset($result['occurrences'])) {
                $totals['occurrences'] = ($totals['occurrences'] ?? 0) + (int) $result['occurrences'];
            }
        }

        return $totals;
    }

    /** @return array<string, int> */
    protected function clearErrors(): array
    {
        $occurrences = 0;
        $errors = 0;

        if (Schema::hasTable('system_error_occurrences')) {
            $occurrences = SystemErrorOccurrence::query()->count();
            SystemErrorOccurrence::query()->delete();
        }
        if (Schema::hasTable('system_errors')) {
            $errors = SystemError::query()->count();
            SystemError::query()->delete();
        }

        return [
            'deleted' => $occurrences + $errors,
            'occurrences' => $occurrences,
            'errors' => $errors,
        ];
    }

    /** @return array<string, int> */
    protected function clearFailedJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return ['deleted' => 0];
        }

        $count = (int) DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->delete();

        return ['deleted' => $count];
    }

    /** @return array<string, int> */
    protected function clearModel(string $modelClass): array
    {
        $model = new $modelClass;
        if (! Schema::hasTable($model->getTable())) {
            return ['deleted' => 0];
        }

        $count = $modelClass::query()->count();
        $modelClass::query()->delete();

        return ['deleted' => $count];
    }
}
