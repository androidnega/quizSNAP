<?php

namespace App\Services\Intelligence;

use App\Events\Intelligence\IntelligenceDashboardUpdated;
use App\Events\Intelligence\IntelligenceRiskChanged;
use App\Models\IntelligenceSnapshot;
use App\Services\Operations\OperationsAttendanceAnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class IntelligenceExecutiveDashboardService
{
    public function payload(int $days = 90): array
    {
        return Cache::remember('intelligence:executive-dashboard', 300, fn () => $this->build($days));
    }

    public function broadcast(int $days = 90): void
    {
        $payload = $this->build($days);
        try {
            broadcast(new IntelligenceDashboardUpdated($payload))->toOthers();
            broadcast(new IntelligenceRiskChanged([
                'risk_score' => $payload['risk_score'],
                'risk_level' => $payload['risk_level'],
            ]))->toOthers();
        } catch (\Throwable) {
            // Reverb may be offline in dev/CI
        }
    }

    protected function build(int $days): array
    {
        $performance = app(IntelligenceAcademicPerformanceService::class)->snapshot($days);
        $students = app(IntelligenceStudentService::class)->snapshot($days);
        $proctoring = app(IntelligenceProctoringAnalyticsService::class)->snapshot($days);
        $risk = app(IntelligenceRiskAnalysisService::class)->snapshot($days);
        $attendance = app(OperationsAttendanceAnalyticsService::class)->snapshot($days);
        $lecturers = app(IntelligenceLecturerService::class)->snapshot($days);

        $academicHealth = (int) round((($performance['average_score'] ?? 0) * 0.5) + (($performance['pass_rate'] ?? 0) * 0.5));
        $studentSuccess = (int) round(collect($students['students'] ?? [])->avg('performance_score') ?? 0);
        $attendanceScore = (int) round($attendance['attendance_rate'] ?? 0);
        $integrityScore = (int) ($proctoring['integrity_score'] ?? 100);
        $institutionHealth = (int) round(($academicHealth + $studentSuccess + $attendanceScore + $integrityScore) / 4);

        $payload = [
            'institution_health_score' => $institutionHealth,
            'academic_health_score' => $academicHealth,
            'student_success_score' => $studentSuccess,
            'attendance_score' => $attendanceScore,
            'integrity_score' => $integrityScore,
            'risk_score' => $risk['overall_risk_score'] ?? 0,
            'risk_level' => $risk['overall_risk_level'] ?? IntelligenceRiskEngine::LEVEL_LOW,
            'department_rankings' => collect($performance['department_comparison'] ?? [])->take(10)->values()->all(),
            'course_rankings' => collect($performance['course_comparison'] ?? [])->take(10)->values()->all(),
            'faculty_rankings' => collect($lecturers['top_performers'] ?? [])->take(10)->values()->all(),
            'at_risk_count' => count($students['at_risk_students'] ?? []),
            'open_warnings' => app(IntelligenceEarlyWarningService::class)->openWarnings()->count(),
            'timestamp' => now()->toIso8601String(),
        ];

        if (Schema::hasTable('intelligence_snapshots')) {
            IntelligenceSnapshot::query()->create([
                'snapshot_type' => 'executive_dashboard',
                'payload' => $payload,
                'recorded_at' => now(),
            ]);
        }

        return $payload;
    }
}
