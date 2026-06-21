<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringCapacitySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseCapacityService
{
    public function collect(): MonitoringCapacitySnapshot
    {
        $database = config('database.connections.'.config('database.default').'.database');
        $sizeBytes = 0;
        $tables = [];

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $rows = DB::select('
                SELECT table_name AS name,
                       ROUND((data_length + index_length)) AS size_bytes,
                       table_rows AS row_count
                FROM information_schema.TABLES
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 50
            ', [$database]);

            foreach ($rows as $row) {
                $sizeBytes += (int) $row->size_bytes;
                $tables[] = [
                    'name' => $row->name,
                    'size_bytes' => (int) $row->size_bytes,
                    'rows' => (int) $row->row_count,
                ];
            }
        }

        $previous = MonitoringCapacitySnapshot::query()
            ->where('snapshot_type', MonitoringCapacitySnapshot::TYPE_DATABASE)
            ->latest('recorded_at')
            ->first();

        $growthDaily = ($previous && $previous->used_bytes)
            ? (($sizeBytes - $previous->used_bytes) / max(1, $previous->used_bytes)) * 100
            : 0;

        return MonitoringCapacitySnapshot::query()->create([
            'snapshot_type' => MonitoringCapacitySnapshot::TYPE_DATABASE,
            'total_bytes' => $sizeBytes,
            'used_bytes' => $sizeBytes,
            'free_bytes' => null,
            'growth_rate_daily' => round($growthDaily, 4),
            'breakdown' => ['tables' => $tables],
            'forecast' => $this->forecast($sizeBytes, $growthDaily),
            'recorded_at' => now(),
        ]);
    }

    public function latest(): ?MonitoringCapacitySnapshot
    {
        return MonitoringCapacitySnapshot::query()
            ->where('snapshot_type', MonitoringCapacitySnapshot::TYPE_DATABASE)
            ->latest('recorded_at')
            ->first();
    }

    protected function forecast(int $currentBytes, float $dailyGrowthPercent): array
    {
        return [
            '30d' => (int) round($currentBytes * pow(1 + ($dailyGrowthPercent / 100), 30)),
            '90d' => (int) round($currentBytes * pow(1 + ($dailyGrowthPercent / 100), 90)),
            '180d' => (int) round($currentBytes * pow(1 + ($dailyGrowthPercent / 100), 180)),
        ];
    }
}
