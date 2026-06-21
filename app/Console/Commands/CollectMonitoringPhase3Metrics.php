<?php

namespace App\Console\Commands;

use App\Services\Monitoring\BackupMonitoringService;
use App\Services\Monitoring\CommandCenterService;
use App\Services\Monitoring\DatabaseCapacityService;
use App\Services\Monitoring\LiveAttendanceMonitorService;
use App\Services\Monitoring\LiveQuizMonitorService;
use App\Services\Monitoring\ReverbAnalyticsService;
use App\Services\Monitoring\StorageCapacityService;
use Illuminate\Console\Command;

class CollectMonitoringPhase3Metrics extends Command
{
    protected $signature = 'monitoring:collect-phase3';

    protected $description = 'Collect Phase 3 monitoring metrics (capacity, reverb, live feeds, command center)';

    public function handle(): int
    {
        app(ReverbAnalyticsService::class)->collectAndPersist();
        app(DatabaseCapacityService::class)->collect();
        app(StorageCapacityService::class)->collect();
        app(LiveQuizMonitorService::class)->broadcastUpdate();
        app(LiveAttendanceMonitorService::class)->broadcastUpdate();
        app(CommandCenterService::class)->broadcast();

        $this->info('Phase 3 monitoring metrics collected.');

        return self::SUCCESS;
    }
}
