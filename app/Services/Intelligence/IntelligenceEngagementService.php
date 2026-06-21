<?php

namespace App\Services\Intelligence;

use App\Models\AuthAuditLog;
use App\Models\QuizSession;
use App\Services\Operations\OperationsAcademicIntelligenceService;
use Illuminate\Support\Facades\Schema;

class IntelligenceEngagementService
{
    public function snapshot(int $days = 90): array
    {
        $since = now()->subDays($days);

        $daily = $this->activitySeries($since, 'day');
        $weekly = $this->activitySeries($since, 'week');
        $monthly = $this->activitySeries($since, 'month');

        $examParticipation = Schema::hasTable('quiz_sessions')
            ? QuizSession::query()->whereNotNull('start_time')->where('start_time', '>=', $since)->count()
            : 0;

        $loginActivity = Schema::hasTable('auth_audit_logs')
            ? AuthAuditLog::query()->where('actor_type', 'student')->where('created_at', '>=', $since)->count()
            : 0;

        return [
            'daily_activity' => $daily,
            'weekly_activity' => $weekly,
            'monthly_activity' => $monthly,
            'exam_participation' => $examParticipation,
            'attendance_participation' => $loginActivity,
            'course_participation' => app(OperationsAcademicIntelligenceService::class)->snapshot($days)['course_participation'] ?? [],
            'rankings' => $this->rankings($since),
            'period_days' => $days,
        ];
    }

    protected function activitySeries($since, string $granularity): array
    {
        if (! Schema::hasTable('auth_audit_logs')) {
            return [];
        }

        $format = match ($granularity) {
            'month' => 'Y-m',
            'week' => 'Y-W',
            default => 'Y-m-d',
        };

        $logins = AuthAuditLog::query()
            ->where('actor_type', 'student')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn ($l) => $l->created_at->format($format))
            ->map->count();

        $exams = Schema::hasTable('quiz_sessions')
            ? QuizSession::query()->whereNotNull('start_time')->where('start_time', '>=', $since)->get()
                ->groupBy(fn ($s) => $s->start_time->format($format))
                ->map->count()
            : collect();

        $keys = $logins->keys()->merge($exams->keys())->unique()->sort();

        return $keys->map(fn ($k) => [
            'period' => $k,
            'total' => ($logins[$k] ?? 0) + ($exams[$k] ?? 0),
        ])->values()->all();
    }

    protected function rankings($since): array
    {
        return collect(app(IntelligenceStudentService::class)->snapshot(min(90, now()->diffInDays($since) ?: 90))['students'] ?? [])
            ->sortByDesc('engagement_score')
            ->take(20)
            ->map(fn ($s) => ['student_index' => $s['student_index'], 'score' => $s['engagement_score']])
            ->values()
            ->all();
    }
}
