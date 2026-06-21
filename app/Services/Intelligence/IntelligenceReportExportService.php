<?php

namespace App\Services\Intelligence;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntelligenceReportExportService
{
    public function executiveSummary(int $days = 90): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'period_days' => $days,
            'executive_dashboard' => app(IntelligenceExecutiveDashboardService::class)->payload($days),
            'students' => app(IntelligenceStudentService::class)->snapshot($days),
            'risk' => app(IntelligenceRiskAnalysisService::class)->snapshot($days),
            'predictive' => app(IntelligencePredictiveService::class)->snapshot($days),
            'recommendations' => app(IntelligenceRecommendationEngine::class)->recent(20),
            'warnings' => app(IntelligenceEarlyWarningService::class)->openWarnings(),
            'anomalies' => app(IntelligenceAnomalyDetectionService::class)->openAnomalies(),
        ];
    }

    public function exportJson(int $days = 90)
    {
        return Response::json($this->executiveSummary($days));
    }

    public function exportCsv(int $days = 90): StreamedResponse
    {
        $summary = $this->executiveSummary($days);
        $dashboard = $summary['executive_dashboard'] ?? [];

        return Response::streamDownload(function () use ($dashboard, $summary) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Metric', 'Value']);
            foreach ([
                'institution_health_score', 'academic_health_score', 'student_success_score',
                'attendance_score', 'integrity_score', 'risk_score', 'risk_level', 'at_risk_count',
            ] as $key) {
                fputcsv($out, [$key, $dashboard[$key] ?? '']);
            }
            fputcsv($out, []);
            fputcsv($out, ['Top At-Risk Students']);
            fputcsv($out, ['Index', 'Risk Score', 'Performance']);
            foreach ($summary['students']['at_risk_students'] ?? [] as $student) {
                fputcsv($out, [$student['student_index'] ?? '', $student['risk_score'] ?? '', $student['performance_score'] ?? '']);
            }
            fclose($out);
        }, 'intelligence-executive-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(int $days = 90)
    {
        return Excel::download(
            new \App\Exports\IntelligenceExecutiveExport($this->executiveSummary($days)),
            'intelligence-executive-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportPdf(int $days = 90)
    {
        $summary = $this->executiveSummary($days);

        return Pdf::loadView('admin.intelligence.reports.export-pdf', [
            'summary' => $summary,
            'dashboard' => $summary['executive_dashboard'] ?? [],
        ])->download('intelligence-executive-'.now()->format('Y-m-d').'.pdf');
    }
}
