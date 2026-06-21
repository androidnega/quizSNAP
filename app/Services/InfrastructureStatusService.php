<?php

namespace App\Services;

use App\Services\Monitoring\ServerHealthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class InfrastructureStatusService
{
    public function snapshot(): array
    {
        $health = app(ServerHealthService::class);

        return [
            'cpu_cores' => $this->cpuCores(),
            'cpu_usage' => $health->latest()?->cpu_usage,
            'ram_usage' => $this->ramUsagePercent(),
            'ram_used_mb' => $this->ramUsedMb(),
            'ram_total_mb' => $this->ramTotalMb(),
            'disk_usage' => $this->diskUsagePercent(),
            'disk_free_gb' => $this->diskFreeGb(),
            'redis' => $this->redisStatus(),
            'database' => $this->databaseStatus(),
            'queue_workers' => $this->queueWorkers(),
            'reverb_workers' => $this->reverbWorkers(),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    protected function cpuCores(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/cpuinfo')) {
            $count = substr_count(strtolower((string) file_get_contents('/proc/cpuinfo')), 'processor');

            return $count > 0 ? $count : null;
        }

        if (function_exists('shell_exec')) {
            $nproc = (int) trim((string) shell_exec('nproc 2>/dev/null'));

            return $nproc > 0 ? $nproc : null;
        }

        return null;
    }

    protected function ramUsagePercent(): ?float
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
            $info = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $info, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $info, $available);
            if (($total[1] ?? 0) > 0) {
                $used = (int) $total[1] - (int) ($available[1] ?? 0);

                return round(($used / (int) $total[1]) * 100, 1);
            }
        }

        return app(ServerHealthService::class)->latest()?->ram_usage;
    }

    protected function ramTotalMb(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
            preg_match('/MemTotal:\s+(\d+)/', (string) file_get_contents('/proc/meminfo'), $total);

            return isset($total[1]) ? (int) round((int) $total[1] / 1024) : null;
        }

        return null;
    }

    protected function ramUsedMb(): ?int
    {
        $total = $this->ramTotalMb();
        $percent = $this->ramUsagePercent();
        if ($total !== null && $percent !== null) {
            return (int) round($total * ($percent / 100));
        }

        return null;
    }

    protected function diskUsagePercent(): ?float
    {
        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());
        if ($total && $free !== false) {
            return round((($total - $free) / $total) * 100, 1);
        }

        return app(ServerHealthService::class)->latest()?->disk_usage;
    }

    protected function diskFreeGb(): ?float
    {
        $free = @disk_free_space(base_path());

        return $free !== false ? round($free / 1073741824, 1) : null;
    }

    /** @return array{status: string, label: string} */
    protected function redisStatus(): array
    {
        try {
            if (in_array(config('cache.default'), ['redis'], true) || config('session.driver') === 'redis') {
                Redis::connection()->ping();

                return ['status' => 'online', 'label' => 'Connected'];
            }

            return ['status' => 'offline', 'label' => 'Not configured'];
        } catch (\Throwable) {
            return ['status' => 'offline', 'label' => 'Unavailable'];
        }
    }

    /** @return array{status: string, label: string} */
    protected function databaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            $tablesOk = Schema::hasTable('users');

            return [
                'status' => 'online',
                'label' => $tablesOk ? 'Connected' : 'Connected (schema check partial)',
            ];
        } catch (\Throwable) {
            return ['status' => 'offline', 'label' => 'Connection failed'];
        }
    }

    protected function queueWorkers(): int
    {
        if (PHP_OS_FAMILY === 'Linux' && function_exists('shell_exec')) {
            return (int) trim((string) shell_exec("pgrep -fc 'queue:work'") ?: '0');
        }

        return (int) (app(ServerHealthService::class)->latest()?->queue_workers ?? 0);
    }

    protected function reverbWorkers(): int
    {
        if (PHP_OS_FAMILY === 'Linux' && function_exists('shell_exec')) {
            return (int) trim((string) shell_exec("pgrep -fc 'reverb:start'") ?: '0');
        }

        return 0;
    }
}
