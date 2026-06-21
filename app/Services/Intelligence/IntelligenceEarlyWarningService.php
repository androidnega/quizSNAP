<?php

namespace App\Services\Intelligence;

use App\Events\Intelligence\IntelligenceWarningCreated;
use App\Models\IntelligenceWarning;
use App\Services\Monitoring\MonitoringNotificationService;
use Illuminate\Support\Facades\Schema;

class IntelligenceEarlyWarningService
{
    public function scan(int $days = 90): array
    {
        $students = app(IntelligenceStudentService::class)->snapshot($days);
        $proctoring = app(IntelligenceProctoringAnalyticsService::class)->snapshot($days);
        $performance = app(IntelligenceAcademicPerformanceService::class)->snapshot($days);

        $queue = [];

        foreach ($students['at_risk_students'] ?? [] as $student) {
            $queue[] = $this->raise(
                'at_risk_student',
                'high',
                'At-risk student: '.$student['student_index'],
                'Risk score '.$student['risk_score'].' requires intervention.',
                'student',
                $student['student_index']
            );
        }

        if (($performance['failure_rate'] ?? 0) > 50) {
            $queue[] = $this->raise(
                'repeated_failures',
                'critical',
                'Repeated failures detected',
                'Institution failure rate is '.($performance['failure_rate'] ?? 0).'%.',
            );
        }

        if (($proctoring['integrity_score'] ?? 100) < 50) {
            $queue[] = $this->raise(
                'proctoring_violations',
                'critical',
                'High proctoring violations',
                'Integrity score dropped to '.($proctoring['integrity_score'] ?? 0).'.',
            );
        }

        foreach ($students['declining_students'] ?? [] as $student) {
            $queue[] = $this->raise(
                'academic_decline',
                'medium',
                'Academic decline: '.$student['student_index'],
                'Performance trend is declining.',
                'student',
                $student['student_index']
            );
        }

        return [
            'intervention_queue' => $this->openWarnings(),
            'generated' => count(array_filter($queue)),
        ];
    }

    public function openWarnings()
    {
        if (! Schema::hasTable('intelligence_warnings')) {
            return collect();
        }

        return IntelligenceWarning::query()
            ->where('status', IntelligenceWarning::STATUS_OPEN)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    protected function raise(
        string $type,
        string $severity,
        string $title,
        string $message,
        ?string $subjectType = null,
        ?string $subjectKey = null
    ): ?IntelligenceWarning {
        if (! Schema::hasTable('intelligence_warnings')) {
            return null;
        }

        $exists = IntelligenceWarning::query()
            ->where('warning_type', $type)
            ->where('subject_key', $subjectKey)
            ->where('status', IntelligenceWarning::STATUS_OPEN)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($exists) {
            return null;
        }

        $warning = IntelligenceWarning::query()->create([
            'warning_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject_type' => $subjectType,
            'subject_key' => $subjectKey,
            'status' => IntelligenceWarning::STATUS_OPEN,
        ]);

        $payload = [
            'id' => $warning->id,
            'type' => $warning->warning_type,
            'severity' => $warning->severity,
            'title' => $warning->title,
        ];

        try {
            broadcast(new IntelligenceWarningCreated($payload))->toOthers();
        } catch (\Throwable) {
            // ignore
        }

        try {
            app(MonitoringNotificationService::class)->notify(
                'intelligence_'.$type,
                $severity === 'critical' ? 'critical' : 'warning',
                $title,
                $message
            );
        } catch (\Throwable) {
            // ignore
        }

        return $warning;
    }
}
