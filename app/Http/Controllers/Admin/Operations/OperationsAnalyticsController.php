<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Services\Operations\OperationsAcademicIntelligenceService;
use App\Services\Operations\OperationsAttendanceAnalyticsService;
use App\Services\Operations\OperationsExamAnalyticsService;
use App\Services\Operations\OperationsFacultyAnalyticsService;
use App\Services\Operations\OperationsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsAnalyticsController extends Controller
{
    public function intelligence(Request $request, OperationsAcademicIntelligenceService $service): View
    {
        $days = (int) $request->query('days', 30);

        return view('admin.operations.intelligence.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function exams(Request $request, OperationsExamAnalyticsService $service): View
    {
        $days = (int) $request->query('days', 30);

        return view('admin.operations.analytics.exams', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function attendance(Request $request, OperationsAttendanceAnalyticsService $service): View
    {
        $days = (int) $request->query('days', 30);

        return view('admin.operations.analytics.attendance', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function faculty(Request $request, OperationsFacultyAnalyticsService $service): View
    {
        $days = (int) $request->query('days', 30);

        return view('admin.operations.analytics.faculty', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function reports(OperationsReportService $reports): View
    {
        return view('admin.operations.reports.index', [
            'summary' => $reports->summary(30),
        ]);
    }

    public function reportsExport(OperationsReportService $reports): JsonResponse
    {
        return response()->json($reports->summary(30));
    }
}
