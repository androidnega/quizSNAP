<?php

namespace App\Console\Commands;

use App\Services\Monitoring\ServerHealthService;
use Illuminate\Console\Command;

class CollectMonitoringHealth extends Command
{
    protected $signature = 'monitoring:collect-health';

    protected $description = 'Collect server health metrics for the monitoring center';

    public function handle(ServerHealthService $health): int
    {
        $snapshot = $health->collect();
        $this->info("Health snapshot recorded: {$snapshot->status}");

        return self::SUCCESS;
    }
}
