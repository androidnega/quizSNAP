<?php

namespace App\Services\Monitoring;

use App\Models\PerformanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PerformanceMonitoringService
{
    protected static int $queryTimeMs = 0;
    protected static int $queryCount = 0;

    public static function addQueryTime(float $timeMs): void
    {
        self::$queryTimeMs += (int) round($timeMs);
        self::$queryCount++;
    }

    public function logRequest(Request $request, int $durationMs, int $memoryKb): void
    {
        if (! Schema::hasTable('performance_logs')) {
            return;
        }

        if ($request->is('dashboard/monitoring/*') && $durationMs < 2000) {
            return;
        }

        try {
            $cacheHits = (int) Cache::get('monitoring:cache_hits', 0);
            $cacheMisses = (int) Cache::get('monitoring:cache_misses', 0);

            PerformanceLog::query()->create([
                'route' => $request->route()?->getName(),
                'controller' => $request->route()?->getActionName(),
                'page_load_time_ms' => $durationMs,
                'controller_time_ms' => $durationMs,
                'memory_usage_kb' => $memoryKb,
                'query_time_ms' => self::$queryTimeMs,
                'request_duration_ms' => $durationMs,
                'response_duration_ms' => $durationMs,
                'cache_hits' => $cacheHits,
                'cache_misses' => $cacheMisses,
                'user_id' => auth()->id(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
