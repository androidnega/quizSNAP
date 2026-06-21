<?php

namespace App\Services\Intelligence;

use App\Models\AuthAuditLog;
use App\Models\IntelligenceAnomaly;
use App\Models\Quiz;
use App\Models\QuizViolation;
use App\Models\Result;
use Illuminate\Support\Facades\Schema;

class IntelligenceAnomalyDetectionService
{
    public function detect(int $days = 30): array
    {
        $since = now()->subDays($days);
        $created = [];

        $created[] = $this->detectMassFailures($since);
        $created[] = $this->detectSuddenScoreDrops($since);
        $created[] = $this->detectUnusualLogins($since);
        $created[] = $this->detectViolationSpikes($since);

        return [
            'detected' => count(array_filter($created)),
            'anomalies' => $this->openAnomalies(),
        ];
    }

    public function openAnomalies()
    {
        if (! Schema::hasTable('intelligence_anomalies')) {
            return collect();
        }

        return IntelligenceAnomaly::query()
            ->where('status', IntelligenceAnomaly::STATUS_OPEN)
            ->orderByDesc('detected_at')
            ->limit(50)
            ->get();
    }

    protected function detectMassFailures($since): ?IntelligenceAnomaly
    {
        if (! Schema::hasTable('results')) {
            return null;
        }

        $recent = Result::query()->where('submitted_at', '>=', now()->subDay())->get();
        if ($recent->count() < 10) {
            return null;
        }

        $failRate = $recent->where('score', '<', 50)->count() / max(1, $recent->count()) * 100;
        if ($failRate < 70) {
            return null;
        }

        return $this->persist(
            'mass_failures',
            'critical',
            'Mass failure event detected',
            round($failRate, 1).'% of recent submissions failed.',
            ['fail_rate' => $failRate, 'sample_size' => $recent->count()]
        );
    }

    protected function detectSuddenScoreDrops($since): ?IntelligenceAnomaly
    {
        if (! Schema::hasTable('results')) {
            return null;
        }

        $thisWeek = Result::query()->where('submitted_at', '>=', now()->subDays(7))->avg('score');
        $lastWeek = Result::query()->whereBetween('submitted_at', [now()->subDays(14), now()->subDays(7)])->avg('score');

        if ($thisWeek === null || $lastWeek === null || ($lastWeek - $thisWeek) < 15) {
            return null;
        }

        return $this->persist(
            'sudden_score_drop',
            'high',
            'Sudden score drop detected',
            'Average score dropped '.round($lastWeek - $thisWeek, 1).' points week-over-week.',
            ['this_week' => round((float) $thisWeek, 1), 'last_week' => round((float) $lastWeek, 1)]
        );
    }

    protected function detectUnusualLogins($since): ?IntelligenceAnomaly
    {
        if (! Schema::hasTable('auth_audit_logs')) {
            return null;
        }

        $lastHour = AuthAuditLog::query()
            ->where('actor_type', 'student')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($lastHour < 50) {
            return null;
        }

        return $this->persist(
            'unusual_login_activity',
            'medium',
            'Unusual login activity',
            "{$lastHour} student logins in the last hour.",
            ['count' => $lastHour]
        );
    }

    protected function detectViolationSpikes($since): ?IntelligenceAnomaly
    {
        if (! Schema::hasTable('quiz_violations')) {
            return null;
        }

        $count = QuizViolation::query()->where('occurred_at', '>=', now()->subHour())->count();
        if ($count < 20) {
            return null;
        }

        return $this->persist(
            'repeated_violations',
            'high',
            'Violation spike detected',
            "{$count} proctoring violations in the last hour.",
            ['count' => $count]
        );
    }

    protected function persist(string $type, string $severity, string $title, string $description, array $metrics): ?IntelligenceAnomaly
    {
        if (! Schema::hasTable('intelligence_anomalies')) {
            return null;
        }

        $exists = IntelligenceAnomaly::query()
            ->where('anomaly_type', $type)
            ->where('status', IntelligenceAnomaly::STATUS_OPEN)
            ->where('detected_at', '>=', now()->subHours(6))
            ->exists();

        if ($exists) {
            return null;
        }

        return IntelligenceAnomaly::query()->create([
            'anomaly_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
            'metrics' => $metrics,
            'status' => IntelligenceAnomaly::STATUS_OPEN,
            'detected_at' => now(),
        ]);
    }
}
