<?php

namespace App\Services\Intelligence;

use App\Models\AuthAuditLog;
use App\Models\ClassGroupStudent;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IntelligenceStudentService
{
    public function __construct(protected IntelligenceRiskEngine $risk) {}

    public function snapshot(int $days = 90): array
    {
        $since = now()->subDays($days);
        $students = $this->buildStudentProfiles($since);

        return [
            'students' => array_slice($students, 0, 100),
            'top_students' => collect($students)->sortByDesc('performance_score')->take(10)->values()->all(),
            'at_risk_students' => collect($students)->where('risk_level', IntelligenceRiskEngine::LEVEL_HIGH)->merge(
                collect($students)->where('risk_level', IntelligenceRiskEngine::LEVEL_CRITICAL)
            )->take(15)->values()->all(),
            'improving_students' => collect($students)->where('improvement_trend', 'improving')->take(10)->values()->all(),
            'declining_students' => collect($students)->where('improvement_trend', 'declining')->take(10)->values()->all(),
            'period_days' => $days,
        ];
    }

    protected function buildStudentProfiles($since): array
    {
        if (! Schema::hasTable('class_group_students')) {
            return [];
        }

        $roster = ClassGroupStudent::query()->limit(500)->get();
        $profiles = [];

        foreach ($roster as $student) {
            $hash = $student->index_number_hash;
            $index = $student->index_number;

            $sessions = Schema::hasTable('quiz_sessions')
                ? QuizSession::query()->where('student_index', $index)->whereNotNull('start_time')->where('start_time', '>=', $since)->get()
                : collect();

            $results = Schema::hasTable('results')
                ? Result::query()->whereHas('quizSession', fn ($q) => $q->where('student_index', $index))->where('submitted_at', '>=', $since)->get()
                : collect();

            $avgScore = $results->avg('score') ?? 0;
            $examCount = $sessions->count();
            $completed = $sessions->whereNotNull('ended_at')->count();
            $participationRate = $examCount > 0 ? ($completed / $examCount) * 100 : 0;

            $logins = Schema::hasTable('auth_audit_logs')
                ? AuthAuditLog::query()->where('actor_type', 'student')->where('index_number_hash', $hash)->where('created_at', '>=', $since)->count()
                : 0;

            $attendanceScore = min(100, $logins * 5);
            $engagementScore = min(100, ($participationRate * 0.6) + min(40, $logins * 2));
            $performanceScore = (float) $avgScore;

            $scoreTrend = $results->sortBy('submitted_at')->pluck('score')->map(fn ($s) => (float) $s)->all();
            $examTrend = $this->weeklyCounts($sessions, 'start_time');
            $attendanceTrend = $this->weeklyLoginCounts($hash, $since);

            $components = [
                'performance' => $performanceScore,
                'attendance' => $attendanceScore,
                'engagement' => $engagementScore,
                'participation' => $participationRate,
            ];

            $riskScore = $this->risk->riskScoreFromComponents($components);

            $profiles[] = [
                'student_index' => $index,
                'student_name' => $student->student_name,
                'performance_score' => round($performanceScore, 1),
                'attendance_score' => round($attendanceScore, 1),
                'engagement_score' => round($engagementScore, 1),
                'risk_score' => $riskScore,
                'risk_level' => $this->risk->riskFromScore(100 - $riskScore),
                'improvement_trend' => $this->risk->trend($scoreTrend),
                'exam_trend' => $this->risk->trend(array_values($examTrend)),
                'attendance_trend' => $this->risk->trend(array_values($attendanceTrend)),
                'participation_trend' => $participationRate >= 70 ? 'improving' : ($participationRate < 40 ? 'declining' : 'stable'),
                'exams_taken' => $examCount,
                'exams_completed' => $completed,
            ];
        }

        return collect($profiles)->sortByDesc('performance_score')->values()->all();
    }

    protected function weeklyCounts($collection, string $column): array
    {
        return $collection
            ->filter(fn ($item) => $item->{$column})
            ->groupBy(fn ($item) => $item->{$column}->format('Y-W'))
            ->map->count()
            ->all();
    }

    protected function weeklyLoginCounts(?string $hash, $since): array
    {
        if (! $hash || ! Schema::hasTable('auth_audit_logs')) {
            return [];
        }

        return AuthAuditLog::query()
            ->where('actor_type', 'student')
            ->where('index_number_hash', $hash)
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn ($log) => $log->created_at->format('Y-W'))
            ->map->count()
            ->all();
    }
}
