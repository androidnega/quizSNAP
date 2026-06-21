<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\ApiMonitoringService;
use App\Services\Monitoring\PerformanceMonitoringService;
use App\Services\Monitoring\SessionMonitoringService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitorHttpRequests
{
    public function __construct(
        protected ApiMonitoringService $apiMonitoring,
        protected PerformanceMonitoringService $performanceMonitoring,
        protected SessionMonitoringService $sessionMonitoring,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $memoryStart = memory_get_usage(true);

        $response = $next($request);

        try {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $memoryKb = (int) round((memory_get_usage(true) - $memoryStart) / 1024);

            $this->sessionMonitoring->trackRequest($request);

            if ($request->is('api/*') || $request->expectsJson()) {
                $this->apiMonitoring->logRequest($request, $response, $durationMs);
            }

            $this->performanceMonitoring->logRequest($request, $durationMs, $memoryKb);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
