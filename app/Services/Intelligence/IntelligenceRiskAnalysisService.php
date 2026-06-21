<?php

namespace App\Services\Intelligence;

use App\Services\Operations\OperationsAcademicIntelligenceService;
use App\Services\Operations\OperationsAttendanceAnalyticsService;
use App\Services\Operations\OperationsExamAnalyticsService;

class IntelligenceRiskAnalysisService
{
    public function __construct(protected IntelligenceRiskEngine $risk) {}

    public function snapshot(int $days = 90): array
    {
        $students = app(IntelligenceStudentService::class)->snapshot($days);
        $exam = app(OperationsExamAnalyticsService::class)->snapshot($days);
        $attendance = app(OperationsAttendanceAnalyticsService::class)->snapshot($days);

        $distribution = [
            IntelligenceRiskEngine::LEVEL_LOW => 0,
            IntelligenceRiskEngine::LEVEL_MEDIUM => 0,
            IntelligenceRiskEngine::LEVEL_HIGH => 0,
            IntelligenceRiskEngine::LEVEL_CRITICAL => 0,
        ];

        foreach ($students['students'] ?? [] as $student) {
            $level = $student['risk_level'] ?? IntelligenceRiskEngine::LEVEL_LOW;
            $distribution[$level] = ($distribution[$level] ?? 0) + 1;
        }

        $factors = [
            'attendance' => $attendance['attendance_rate'] ?? 0,
            'exam_performance' => $exam['average_score'] ?? 0,
            'pass_rate' => $exam['pass_rate'] ?? 0,
            'participation' => collect($students['students'] ?? [])->avg('engagement_score') ?? 0,
            'missed_assessments' => max(0, 100 - ($exam['pass_rate'] ?? 0)),
        ];

        $overallRisk = $this->risk->riskScoreFromComponents([
            'performance' => $factors['exam_performance'],
            'attendance' => $factors['attendance'],
            'engagement' => $factors['participation'],
            'participation' => 100 - $factors['missed_assessments'],
        ]);

        return [
            'overall_risk_score' => $overallRisk,
            'overall_risk_level' => $this->risk->riskFromScore(100 - $overallRisk),
            'distribution' => $distribution,
            'factors' => $factors,
            'interventions' => $this->interventions($students, $exam, $attendance),
            'at_risk_count' => count($students['at_risk_students'] ?? []),
            'period_days' => $days,
        ];
    }

    protected function interventions(array $students, array $exam, array $attendance): array
    {
        $items = [];

        if (($attendance['attendance_rate'] ?? 100) < 60) {
            $items[] = ['priority' => 'high', 'action' => 'Schedule attendance review sessions for affected cohorts.'];
        }
        if (($exam['failure_rate'] ?? 0) > 40) {
            $items[] = ['priority' => 'critical', 'action' => 'Review course materials and schedule remedial sessions.'];
        }
        if (count($students['at_risk_students'] ?? []) > 5) {
            $items[] = ['priority' => 'high', 'action' => 'Assign academic advisors to at-risk students in intervention queue.'];
        }

        return $items;
    }
}
