<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Models\DatabaseQueryLog;
use App\Models\PerformanceLog;
use App\Services\Monitoring\ApiMonitoringService;
use App\Services\Monitoring\ServerHealthService;
use App\Services\Monitoring\WebSocketMonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringInfrastructureController extends Controller
{
    public function api(Request $request, ApiMonitoringService $api): View
    {
        $query = ApiRequestLog::query()->orderByDesc('occurred_at');
        if ($endpoint = $request->query('endpoint')) {
            $query->where('endpoint', 'like', "%{$endpoint}%");
        }

        return view('admin.monitoring.api.index', [
            'logs' => $query->paginate(30)->withQueryString(),
            'topEndpoints' => $api->getTopEndpoints(),
            'slowEndpoints' => $api->getSlowEndpoints(),
            'failingEndpoints' => $api->getFailingEndpoints(),
        ]);
    }

    public function database(Request $request): View
    {
        $query = DatabaseQueryLog::query()->orderByDesc('occurred_at');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $stats = [
            'avg_time' => (int) DatabaseQueryLog::query()->where('occurred_at', '>=', now()->subDay())->avg('execution_time_ms'),
            'peak_time' => (int) DatabaseQueryLog::query()->where('occurred_at', '>=', now()->subDay())->max('execution_time_ms'),
            'slow_count' => DatabaseQueryLog::query()->where('status', 'slow')->where('occurred_at', '>=', now()->subDay())->count(),
            'failed_count' => DatabaseQueryLog::query()->where('status', 'failed')->where('occurred_at', '>=', now()->subDay())->count(),
        ];

        return view('admin.monitoring.database.index', [
            'logs' => $query->paginate(30)->withQueryString(),
            'stats' => $stats,
        ]);
    }

    public function performance(Request $request): View
    {
        $query = PerformanceLog::query()->orderByDesc('occurred_at');

        return view('admin.monitoring.performance.index', [
            'logs' => $query->paginate(30)->withQueryString(),
            'avgDuration' => (int) PerformanceLog::query()->where('occurred_at', '>=', now()->subDay())->avg('request_duration_ms'),
            'avgMemory' => (int) PerformanceLog::query()->where('occurred_at', '>=', now()->subDay())->avg('memory_usage_kb'),
        ]);
    }

    public function serverHealth(ServerHealthService $health): View
    {
        return view('admin.monitoring.server-health.index', [
            'latest' => $health->latest(),
            'history' => \App\Models\ServerHealthSnapshot::query()->orderByDesc('recorded_at')->limit(48)->get(),
        ]);
    }

    public function websocket(WebSocketMonitoringService $websocket): View
    {
        $analytics = app(\App\Services\Monitoring\ReverbAnalyticsService::class)->snapshot();

        return view('admin.monitoring.websocket.index', [
            'status' => $websocket->status(),
            'analytics' => $analytics,
            'history' => \App\Models\MonitoringReverbMetric::query()->orderByDesc('recorded_at')->limit(24)->get(),
        ]);
    }
}
