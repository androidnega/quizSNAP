<?php

namespace App\Console\Commands;

use App\Models\ApiRequestLog;
use App\Models\DatabaseQueryLog;
use App\Models\MonitoringNotification;
use App\Models\MonitoringSetting;
use App\Models\PerformanceLog;
use App\Models\SecurityEvent;
use App\Models\ServerHealthSnapshot;
use App\Models\SystemAuditLog;
use App\Models\SystemErrorOccurrence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PurgeMonitoringLogs extends Command
{
    protected $signature = 'monitoring:purge-old-logs';

    protected $description = 'Purge monitoring logs older than retention setting';

    public function handle(): int
    {
        $days = (int) (MonitoringSetting::get('retention_days') ?? 90);
        $cutoff = now()->subDays($days);

        $tables = [
            SystemErrorOccurrence::class => 'occurred_at',
            DatabaseQueryLog::class => 'occurred_at',
            ApiRequestLog::class => 'occurred_at',
            PerformanceLog::class => 'occurred_at',
            SecurityEvent::class => 'occurred_at',
            SystemAuditLog::class => 'occurred_at',
            ServerHealthSnapshot::class => 'recorded_at',
            MonitoringNotification::class => 'created_at',
        ];

        $total = 0;
        foreach ($tables as $model => $column) {
            if (! Schema::hasTable((new $model)->getTable())) {
                continue;
            }
            $deleted = $model::query()->where($column, '<', $cutoff)->delete();
            $total += $deleted;
        }

        $this->info("Purged {$total} monitoring log rows older than {$days} days.");

        return self::SUCCESS;
    }
}
