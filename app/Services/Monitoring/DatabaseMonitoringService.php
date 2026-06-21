<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringSlowQueryDetected;
use App\Models\DatabaseQueryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseMonitoringService
{
    public function registerListeners(): void
    {
        if (! Schema::hasTable('database_query_logs')) {
            return;
        }

        $threshold = app(ErrorMonitoringService::class)->slowQueryThresholdMs();

        DB::listen(function ($query) use ($threshold) {
            try {
                $timeMs = (int) round($query->time);

                if ($timeMs < $threshold) {
                    return;
                }

                $route = request()?->route()?->getName();
                $controller = request()?->route()?->getActionName();
                $userId = auth()->id();

                $log = DatabaseQueryLog::query()->create([
                    'sql' => $query->sql,
                    'bindings' => $this->sanitizeBindings($query->bindings),
                    'execution_time_ms' => $timeMs,
                    'status' => DatabaseQueryLog::STATUS_SLOW,
                    'route' => is_string($route) ? $route : null,
                    'controller' => is_string($controller) ? $controller : null,
                    'user_id' => $userId,
                    'connection' => $query->connectionName,
                    'occurred_at' => now(),
                ]);

                broadcast(new MonitoringSlowQueryDetected($log))->toOthers();
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    public function logFailedQuery(string $sql, array $bindings, string $error, ?string $connection = null): void
    {
        if (! Schema::hasTable('database_query_logs')) {
            return;
        }

        try {
            DatabaseQueryLog::query()->create([
                'sql' => $sql,
                'bindings' => $this->sanitizeBindings($bindings),
                'execution_time_ms' => 0,
                'status' => str_contains(strtolower($error), 'deadlock')
                    ? DatabaseQueryLog::STATUS_DEADLOCK
                    : DatabaseQueryLog::STATUS_FAILED,
                'route' => request()?->route()?->getName(),
                'controller' => request()?->route()?->getActionName(),
                'user_id' => auth()->id(),
                'connection' => $connection,
                'error_message' => $error,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function sanitizeBindings(array $bindings): array
    {
        return array_map(function ($binding) {
            if (is_string($binding) && strlen($binding) > 500) {
                return Str::limit($binding, 500);
            }

            return $binding;
        }, $bindings);
    }
}
