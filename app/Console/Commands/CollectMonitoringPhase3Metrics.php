<?php

namespace App\Console\Commands;

use App\Services\Monitoring\BackupMonitoringService;
use App\Services\Monitoring\CommandCenterService;
use App\Services\Monitoring\DatabaseCapacityService;
use App\Services\Monitoring\LiveAttendanceMonitorService;
use App\Services\Monitoring\LiveQuizMonitorService;
use App\Services\Monitoring\ReverbAnalyticsService;
use App\Services\Monitoring\StorageCapacityService;
use App\Support\SafeBroadcast;
use Illuminate\Console\Command;
use Throwable;

class CollectMonitoringPhase3Metrics extends Command
{
    protected $signature = 'monitoring:collect-phase3';

    protected $description = 'Collect Phase 3 monitoring metrics (capacity, reverb, live feeds, command center)';

    public function handle(): int
    {
        foreach ([
            [ReverbAnalyticsService::class, 'collectAndPersist'],
            [DatabaseCapacityService::class, 'collect'],
            [StorageCapacityService::class, 'collect'],
            [LiveQuizMonitorService::class, 'broadcastUpdate'],
            [LiveAttendanceMonitorService::class, 'broadcastUpdate'],
            [CommandCenterService::class, 'broadcast'],
        ] as [$service, $method]) {
            try {
                app($service)->{$method}();
            } catch (Throwable $e) {
                report($e);
            }
        }

        $this->info('Phase 3 monitoring metrics collected.');

        return self::SUCCESS;
    }
}
