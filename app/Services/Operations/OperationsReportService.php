<?php

namespace App\Services\Operations;

class OperationsReportService
{
    public function summary(int $days = 30): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'period_days' => $days,
            'command_center' => app(OperationsCommandCenterService::class)->payload(),
            'exam_analytics' => app(OperationsExamAnalyticsService::class)->snapshot($days),
            'attendance_analytics' => app(OperationsAttendanceAnalyticsService::class)->snapshot($days),
            'faculty_analytics' => app(OperationsFacultyAnalyticsService::class)->snapshot($days),
            'academic_intelligence' => app(OperationsAcademicIntelligenceService::class)->snapshot($days),
        ];
    }
}
