<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringBackup;
use App\Models\MonitoringNotification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BackupMonitoringService
{
    public function scan(): MonitoringBackup
    {
        $paths = [
            storage_path('app/backups'),
            base_path('backups'),
            storage_path('backups'),
        ];

        $latestFile = null;
        $latestTime = 0;

        foreach ($paths as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            foreach (File::allFiles($dir) as $file) {
                if ($file->getMTime() > $latestTime) {
                    $latestTime = $file->getMTime();
                    $latestFile = $file;
                }
            }
        }

        $status = MonitoringBackup::STATUS_MISSING;
        $size = null;
        $location = null;

        if ($latestFile) {
            $status = MonitoringBackup::STATUS_SUCCESS;
            $size = $latestFile->getSize();
            $location = $latestFile->getPathname();
        }

        $backup = MonitoringBackup::query()->create([
            'backup_type' => 'database',
            'status' => $status,
            'size_bytes' => $size,
            'location' => $location,
            'retention_days' => (int) (MonitoringSetting::get('backup_retention_days') ?? 30),
            'restore_test_status' => null,
            'backed_up_at' => $latestFile ? now()->createFromTimestamp($latestTime) : now(),
            'meta' => ['scanned_paths' => $paths],
        ]);

        if ($status === MonitoringBackup::STATUS_MISSING) {
            app(MonitoringNotificationService::class)->notify(
                'backup_missing',
                'critical',
                'Backup missing',
                'No recent backup files found in configured backup directories.',
                ['backup_id' => $backup->id]
            );
        }

        return $backup;
    }

    public function latest(): ?MonitoringBackup
    {
        if (! Schema::hasTable('monitoring_backups')) {
            return null;
        }

        return MonitoringBackup::query()->latest('backed_up_at')->first();
    }

    public function history(int $limit = 30)
    {
        return MonitoringBackup::query()->orderByDesc('backed_up_at')->limit($limit)->get();
    }
}
