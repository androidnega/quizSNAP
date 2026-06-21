<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringCapacitySnapshot;

class StorageCapacityService
{
    public function collect(): MonitoringCapacitySnapshot
    {
        $paths = [
            'storage' => storage_path(),
            'public' => public_path('storage'),
        ];

        $breakdown = [];
        $totalUsed = 0;

        foreach ($paths as $label => $path) {
            if (! is_dir($path)) {
                continue;
            }
            $size = $this->directorySize($path);
            $totalUsed += $size;
            $breakdown[] = ['name' => $label, 'path' => $path, 'size_bytes' => $size];
        }

        usort($breakdown, fn ($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);

        $diskTotal = @disk_total_space(base_path()) ?: null;
        $diskFree = @disk_free_space(base_path()) ?: null;

        $previous = MonitoringCapacitySnapshot::query()
            ->where('snapshot_type', MonitoringCapacitySnapshot::TYPE_STORAGE)
            ->latest('recorded_at')
            ->first();

        $growthDaily = ($previous && $previous->used_bytes)
            ? (($totalUsed - $previous->used_bytes) / max(1, $previous->used_bytes)) * 100
            : 0;

        $exhaustionDate = null;
        if ($diskFree && $growthDaily > 0) {
            $dailyBytes = ($totalUsed * ($growthDaily / 100));
            if ($dailyBytes > 0) {
                $days = (int) floor($diskFree / $dailyBytes);
                $exhaustionDate = now()->addDays($days)->toDateString();
            }
        }

        return MonitoringCapacitySnapshot::query()->create([
            'snapshot_type' => MonitoringCapacitySnapshot::TYPE_STORAGE,
            'total_bytes' => $diskTotal,
            'used_bytes' => $totalUsed,
            'free_bytes' => $diskFree,
            'growth_rate_daily' => round($growthDaily, 4),
            'breakdown' => ['directories' => $breakdown, 'exhaustion_forecast' => $exhaustionDate],
            'forecast' => [
                '30d' => (int) round($totalUsed * pow(1 + ($growthDaily / 100), 30)),
                '90d' => (int) round($totalUsed * pow(1 + ($growthDaily / 100), 90)),
                '180d' => (int) round($totalUsed * pow(1 + ($growthDaily / 100), 180)),
            ],
            'recorded_at' => now(),
        ]);
    }

    public function latest(): ?MonitoringCapacitySnapshot
    {
        return MonitoringCapacitySnapshot::query()
            ->where('snapshot_type', MonitoringCapacitySnapshot::TYPE_STORAGE)
            ->latest('recorded_at')
            ->first();
    }

    protected function directorySize(string $path): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
