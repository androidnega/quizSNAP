<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringQueueChanged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueMonitoringService
{
    public function stats(): array
    {
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $recentFailed = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())->count()
            : 0;

        return [
            'pending' => $pending,
            'failed' => $failed,
            'recent_failed' => $recentFailed,
            'workers' => app(ServerHealthService::class)->latest()?->queue_workers ?? 0,
        ];
    }

    public function failedJobs(int $perPage = 25)
    {
        if (! Schema::hasTable('failed_jobs')) {
            return collect();
        }

        return DB::table('failed_jobs')->orderByDesc('failed_at')->paginate($perPage);
    }

    public function retry(string $uuid): bool
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        broadcast(new MonitoringQueueChanged('retried', $uuid))->toOthers();

        return true;
    }

    public function retryAll(): int
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        broadcast(new MonitoringQueueChanged('retry_all'))->toOthers();

        return (int) DB::table('failed_jobs')->count();
    }

    public function deleteFailed(string $uuid): bool
    {
        $deleted = DB::table('failed_jobs')->where('uuid', $uuid)->delete();
        if ($deleted) {
            broadcast(new MonitoringQueueChanged('deleted', $uuid))->toOthers();
        }

        return (bool) $deleted;
    }

    public function deleteAllFailed(): int
    {
        $count = DB::table('failed_jobs')->count();
        Artisan::call('queue:flush');
        broadcast(new MonitoringQueueChanged('flush_all'))->toOthers();

        return $count;
    }
}
