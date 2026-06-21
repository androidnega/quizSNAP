<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\LiveAttendanceMonitorService;
use App\Services\Monitoring\LiveQuizMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MonitoringLiveController extends Controller
{
    public function quizMonitor(LiveQuizMonitorService $service): View
    {
        return view('admin.monitoring.live-quiz.index', [
            'snapshot' => $service->snapshot(),
        ]);
    }

    public function quizLive(LiveQuizMonitorService $service): JsonResponse
    {
        return response()->json($service->snapshot());
    }

    public function attendanceMonitor(LiveAttendanceMonitorService $service): View
    {
        return view('admin.monitoring.live-attendance.index', [
            'snapshot' => $service->snapshot(),
        ]);
    }

    public function attendanceLive(LiveAttendanceMonitorService $service): JsonResponse
    {
        return response()->json($service->snapshot());
    }
}
