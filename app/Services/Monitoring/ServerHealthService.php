<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringHealthChanged;
use App\Models\ServerHealthSnapshot;
use Illuminate\Support\Facades\DB;

class ServerHealthService
{
    public function collect(): ServerHealthSnapshot
    {
        $cpu = $this->getCpuUsage();
        $ram = $this->getRamUsage();
        $disk = $this->getDiskUsage();
        $status = $this->determineStatus($cpu, $ram, $disk);

        $snapshot = ServerHealthSnapshot::query()->create([
            'status' => $status,
            'cpu_usage' => $cpu,
            'ram_usage' => $ram,
            'disk_usage' => $disk,
            'disk_free_bytes' => @disk_free_space(base_path()) ?: null,
            'load_average' => $this->getLoadAverage(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'mysql_version' => $this->getMysqlVersion(),
            'queue_workers' => $this->countQueueWorkers(),
            'storage_usage_bytes' => $this->getStorageUsage(),
            'uptime_seconds' => $this->getUptimeSeconds(),
            'network_status' => $this->checkNetworkStatus(),
            'meta' => ['hostname' => gethostname() ?: null],
            'recorded_at' => now(),
        ]);

        broadcast(new MonitoringHealthChanged($snapshot))->toOthers();

        if (in_array($status, [ServerHealthSnapshot::STATUS_CRITICAL, ServerHealthSnapshot::STATUS_WARNING], true)) {
            app(MonitoringNotificationService::class)->notifyForHealth($snapshot);
        }

        return $snapshot;
    }

    public function latest(): ?ServerHealthSnapshot
    {
        return ServerHealthSnapshot::query()->latest('recorded_at')->first();
    }

    protected function determineStatus(?float $cpu, ?float $ram, ?float $disk): string
    {
        $values = array_filter([$cpu, $ram, $disk], fn ($v) => $v !== null);
        if ($values === []) {
            return ServerHealthSnapshot::STATUS_HEALTHY;
        }

        $max = max($values);
        if ($max >= 95) {
            return ServerHealthSnapshot::STATUS_CRITICAL;
        }
        if ($max >= 85) {
            return ServerHealthSnapshot::STATUS_WARNING;
        }

        return ServerHealthSnapshot::STATUS_HEALTHY;
    }

    protected function getCpuUsage(): ?float
    {
        if (PHP_OS_FAMILY !== 'Linux' || ! is_readable('/proc/stat')) {
            return null;
        }

        $stat = file_get_contents('/proc/stat');
        if (! preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/m', $stat, $m)) {
            return null;
        }

        $total = array_sum(array_map('intval', array_slice($m, 1, 4)));
        $idle = (int) $m[4];

        return $total > 0 ? round((1 - ($idle / $total)) * 100, 2) : null;
    }

    protected function getRamUsage(): ?float
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
            $info = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $info, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $info, $available);
            if (($total[1] ?? 0) > 0) {
                $used = (int) $total[1] - (int) ($available[1] ?? 0);

                return round(($used / (int) $total[1]) * 100, 2);
            }
        }

        return null;
    }

    protected function getDiskUsage(): ?float
    {
        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());
        if ($total && $free !== false) {
            return round((($total - $free) / $total) * 100, 2);
        }

        return null;
    }

    protected function getLoadAverage(): ?float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();

            return isset($load[0]) ? round((float) $load[0], 2) : null;
        }

        return null;
    }

    protected function getMysqlVersion(): ?string
    {
        try {
            $result = DB::selectOne('SELECT VERSION() as version');

            return $result->version ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function countQueueWorkers(): int
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $output = shell_exec("pgrep -fc 'queue:work'") ?? '0';

            return (int) trim($output);
        }

        return 0;
    }

    protected function getStorageUsage(): ?int
    {
        $path = storage_path();
        if (! is_dir($path)) {
            return null;
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    protected function getUptimeSeconds(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/uptime')) {
            $parts = explode(' ', trim(file_get_contents('/proc/uptime')));

            return isset($parts[0]) ? (int) $parts[0] : null;
        }

        return null;
    }

    protected function checkNetworkStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'online';
        } catch (\Throwable) {
            return 'degraded';
        }
    }
}
