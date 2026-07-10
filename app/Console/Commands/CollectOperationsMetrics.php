<?php

namespace App\Console\Commands;

use App\Services\Operations\OperationsAttendanceService;
use App\Services\Operations\OperationsCommandCenterService;
use App\Services\Operations\OperationsExamIncidentService;
use App\Services\Operations\OperationsLiveExamService;
use App\Services\Operations\OperationsProctoringService;
use App\Services\Operations\OperationsStudentMonitorService;
use Illuminate\Console\Command;
use Throwable;

class CollectOperationsMetrics extends Command
{
    protected $signature = 'operations:collect-metrics';

    protected $description = 'Collect and broadcast Operations Center live metrics';

    public function handle(): int
    {
        try {
            app(OperationsExamIncidentService::class)->syncFromRecentViolations();
        } catch (Throwable $e) {
            report($e);
        }

        foreach ([
            [OperationsLiveExamService::class, 'broadcastUpdate'],
            [OperationsStudentMonitorService::class, 'broadcastUpdate'],
            [OperationsProctoringService::class, 'broadcastUpdate'],
            [OperationsAttendanceService::class, 'broadcastUpdate'],
            [OperationsCommandCenterService::class, 'broadcast'],
        ] as [$service, $method]) {
            try {
                app($service)->{$method}();
            } catch (Throwable $e) {
                report($e);
            }
        }

        $this->info('Operations metrics collected and broadcast.');

        return self::SUCCESS;
    }
}
