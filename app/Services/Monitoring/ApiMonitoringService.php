<?php

namespace App\Services\Monitoring;

use App\Models\ApiRequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiMonitoringService
{
    public function logRequest(Request $request, SymfonyResponse $response, int $durationMs): void
    {
        if (! Schema::hasTable('api_request_logs')) {
            return;
        }

        try {
            ApiRequestLog::query()->create([
                'endpoint' => '/'.ltrim($request->path(), '/'),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $durationMs,
                'request_size' => strlen((string) $request->getContent()),
                'response_size' => strlen((string) $response->getContent()),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function getTopEndpoints(int $limit = 10): array
    {
        return ApiRequestLog::query()
            ->selectRaw('endpoint, COUNT(*) as total, AVG(response_time_ms) as avg_time')
            ->where('occurred_at', '>=', now()->subDay())
            ->groupBy('endpoint')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getSlowEndpoints(int $limit = 10): array
    {
        return ApiRequestLog::query()
            ->selectRaw('endpoint, AVG(response_time_ms) as avg_time, COUNT(*) as total')
            ->where('occurred_at', '>=', now()->subDay())
            ->groupBy('endpoint')
            ->orderByDesc('avg_time')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getFailingEndpoints(int $limit = 10): array
    {
        return ApiRequestLog::query()
            ->selectRaw('endpoint, COUNT(*) as failures')
            ->where('occurred_at', '>=', now()->subDay())
            ->where('status_code', '>=', 500)
            ->groupBy('endpoint')
            ->orderByDesc('failures')
            ->limit($limit)
            ->get()
            ->all();
    }
}
