<?php

namespace App\Http\Controllers\Admin\Intelligence;

use App\Http\Controllers\Controller;
use App\Services\Intelligence\IntelligenceReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntelligenceReportController extends Controller
{
    public function index(IntelligenceReportExportService $reports): View
    {
        return view('admin.intelligence.reports.index', [
            'summary' => $reports->executiveSummary(),
        ]);
    }

    public function exportJson(IntelligenceReportExportService $reports)
    {
        return $reports->exportJson((int) request('days', 90));
    }

    public function exportCsv(IntelligenceReportExportService $reports, Request $request)
    {
        return $reports->exportCsv((int) $request->query('days', 90));
    }

    public function exportExcel(IntelligenceReportExportService $reports, Request $request)
    {
        return $reports->exportExcel((int) $request->query('days', 90));
    }

    public function exportPdf(IntelligenceReportExportService $reports, Request $request)
    {
        return $reports->exportPdf((int) $request->query('days', 90));
    }
}
