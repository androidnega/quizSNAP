<?php

namespace App\Console\Commands;

use App\Services\Monitoring\BackupMonitoringService;
use Illuminate\Console\Command;

class ScanMonitoringBackups extends Command
{
    protected $signature = 'monitoring:scan-backups';

    protected $description = 'Scan backup directories and record status';

    public function handle(BackupMonitoringService $backups): int
    {
        $result = $backups->scan();
        $this->info('Backup scan: '.$result->status);

        return self::SUCCESS;
    }
}
