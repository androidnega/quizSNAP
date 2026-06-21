<?php

namespace App\Http\Controllers\Admin\Intelligence;

use App\Http\Controllers\Controller;
use App\Services\Intelligence\IntelligenceExecutiveDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class IntelligenceOverviewController extends Controller
{
    public function index(IntelligenceExecutiveDashboardService $dashboard): View
    {
        return view('admin.intelligence.executive.index', [
            'payload' => $dashboard->payload(),
        ]);
    }

    public function live(IntelligenceExecutiveDashboardService $dashboard): JsonResponse
    {
        return response()->json($dashboard->payload());
    }
}
