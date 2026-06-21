<?php

namespace App\Console\Commands;

use App\Services\Operations\OperationsAttendanceService;
use App\Services\Operations\OperationsCommandCenterService;
use App\Services\Operations\OperationsExamIncidentService;
use App\Services\Operations\OperationsLiveExamService;
use App\Services\Operations\OperationsProctoringService;
use App\Services\Operations\OperationsStudentMonitorService;
use Illuminate\Console\Command;

class CollectOperationsMetrics extends Command
{
    protected $signature = 'operations:collect-metrics';

    protected $description = 'Collect and broadcast Operations Center live metrics';

    public function handle(): int
    {
        app(OperationsExamIncidentService::class)->syncFromRecentViolations();
        app(OperationsLiveExamService::class)->broadcastUpdate();
        app(OperationsStudentMonitorService::class)->broadcastUpdate();
        app(OperationsProctoringService::class)->broadcastUpdate();
        app(OperationsAttendanceService::class)->broadcastUpdate();
        app(OperationsCommandCenterService::class)->broadcast();

        $this->info('Operations metrics collected and broadcast.');

        return self::SUCCESS;
    }
}
