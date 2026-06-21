<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\MonitoringChartsService;
use App\Services\Monitoring\MonitoringOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringOverviewController extends Controller
{
    public function index(MonitoringOverviewService $overview): View
    {
        try {
            return view('admin.monitoring.overview', [
                'stats' => $overview->dashboardStats(),
                'recentErrors' => $overview->recentErrors(),
                'recentActivity' => $overview->recentActivity(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return view('admin.monitoring.overview', [
                'stats' => $overview->fallbackDashboardStats(),
                'recentErrors' => collect(),
                'recentActivity' => collect(),
            ])->with('error', 'Some monitoring metrics are temporarily unavailable.');
        }
    }

    public function liveStats(MonitoringOverviewService $overview): JsonResponse
    {
        try {
            return response()->json($overview->dashboardStats());
        } catch (\Throwable $e) {
            report($e);

            return response()->json($overview->fallbackDashboardStats());
        }
    }

    public function liveEvents(MonitoringOverviewService $overview): View
    {
        return view('admin.monitoring.live-events.index', [
            'recentActivity' => $overview->recentActivity(50),
            'liveQuiz' => $overview->liveQuizStats(),
        ]);
    }

    public function charts(Request $request, MonitoringChartsService $charts): JsonResponse
    {
        $period = $request->query('period', '24h');
        $chart = $request->query('chart');

        if ($chart) {
            return response()->json($charts->chartData($chart, $period));
        }

        return response()->json($charts->allCharts($period));
    }
}
