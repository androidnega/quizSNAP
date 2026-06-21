<?php

namespace App\Http\Controllers\Admin\Intelligence;

use App\Http\Controllers\Controller;
use App\Services\Intelligence\IntelligenceAcademicPerformanceService;
use App\Services\Intelligence\IntelligenceAnomalyDetectionService;
use App\Services\Intelligence\IntelligenceEarlyWarningService;
use App\Services\Intelligence\IntelligenceEngagementService;
use App\Services\Intelligence\IntelligenceLecturerService;
use App\Services\Intelligence\IntelligencePredictiveService;
use App\Services\Intelligence\IntelligenceProctoringAnalyticsService;
use App\Services\Intelligence\IntelligenceRecommendationEngine;
use App\Services\Intelligence\IntelligenceRiskAnalysisService;
use App\Services\Intelligence\IntelligenceStudentService;
use App\Services\Operations\OperationsAcademicIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntelligenceAnalyticsController extends Controller
{
    public function academic(Request $request, OperationsAcademicIntelligenceService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.academic.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function students(Request $request, IntelligenceStudentService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.students.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function lecturers(Request $request, IntelligenceLecturerService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.lecturers.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function risk(Request $request, IntelligenceRiskAnalysisService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.risk.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function proctoring(Request $request, IntelligenceProctoringAnalyticsService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.proctoring.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function predictive(Request $request, IntelligencePredictiveService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.predictive.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function engagement(Request $request, IntelligenceEngagementService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.engagement.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function integrity(Request $request, IntelligenceAcademicPerformanceService $service): View
    {
        $days = (int) $request->query('days', 90);

        return view('admin.intelligence.integrity.index', [
            'data' => $service->snapshot($days),
            'days' => $days,
        ]);
    }

    public function recommendations(IntelligenceRecommendationEngine $engine): View
    {
        return view('admin.intelligence.recommendations.index', [
            'data' => $engine->generate(),
        ]);
    }

    public function warnings(IntelligenceEarlyWarningService $warnings, IntelligenceAnomalyDetectionService $anomalies): View
    {
        return view('admin.intelligence.warnings.index', [
            'warnings' => $warnings->openWarnings(),
            'anomalies' => $anomalies->openAnomalies(),
        ]);
    }
}
