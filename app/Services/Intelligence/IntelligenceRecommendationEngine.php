<?php

namespace App\Services\Intelligence;

use App\Events\Intelligence\IntelligenceRecommendationCreated;
use App\Models\IntelligenceRecommendation;
use Illuminate\Support\Facades\Schema;

class IntelligenceRecommendationEngine
{
    public function generate(int $days = 90): array
    {
        $students = app(IntelligenceStudentService::class)->snapshot($days);
        $performance = app(IntelligenceAcademicPerformanceService::class)->snapshot($days);
        $proctoring = app(IntelligenceProctoringAnalyticsService::class)->snapshot($days);

        $recommendations = [];

        if (($performance['failure_rate'] ?? 0) > 35) {
            $recommendations[] = $this->persist(
                'academic',
                'warning',
                'Course performance declining',
                'Failure rate exceeds 35%. Recommend review sessions for underperforming courses.',
                ['failure_rate' => $performance['failure_rate']]
            );
        }

        foreach (array_slice($students['declining_students'] ?? [], 0, 5) as $student) {
            $recommendations[] = $this->persist(
                'student',
                'warning',
                'Student attendance/performance dropping',
                "Recommend intervention for {$student['student_index']}.",
                ['student_index' => $student['student_index']],
                'student',
                $student['student_index']
            );
        }

        if (($proctoring['integrity_score'] ?? 100) < 60) {
            $recommendations[] = $this->persist(
                'integrity',
                'critical',
                'High cheating indicators',
                'Integrity score is low. Recommend proctoring investigation.',
                ['integrity_score' => $proctoring['integrity_score']]
            );
        }

        foreach ($students['at_risk_students'] ?? [] as $student) {
            if (count($recommendations) >= 20) {
                break;
            }
            $recommendations[] = $this->persist(
                'intervention',
                'high',
                'At-risk student identified',
                "Schedule support session for {$student['student_index']}.",
                ['risk_score' => $student['risk_score']],
                'student',
                $student['student_index']
            );
        }

        return [
            'generated' => count(array_filter($recommendations)),
            'recommendations' => $this->recent(30),
        ];
    }

    public function recent(int $limit = 30)
    {
        if (! Schema::hasTable('intelligence_recommendations')) {
            return collect();
        }

        return IntelligenceRecommendation::query()->orderByDesc('created_at')->limit($limit)->get();
    }

    protected function persist(
        string $category,
        string $severity,
        string $title,
        string $message,
        ?array $meta = null,
        ?string $subjectType = null,
        ?string $subjectKey = null
    ): ?IntelligenceRecommendation {
        if (! Schema::hasTable('intelligence_recommendations')) {
            return null;
        }

        $exists = IntelligenceRecommendation::query()
            ->where('title', $title)
            ->where('subject_key', $subjectKey)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($exists) {
            return null;
        }

        $rec = IntelligenceRecommendation::query()->create([
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'meta' => $meta,
            'subject_type' => $subjectType,
            'subject_key' => $subjectKey,
        ]);

        try {
            broadcast(new IntelligenceRecommendationCreated([
                'id' => $rec->id,
                'category' => $rec->category,
                'severity' => $rec->severity,
                'title' => $rec->title,
                'message' => $rec->message,
            ]))->toOthers();
        } catch (\Throwable) {
            // ignore
        }

        return $rec;
    }
}
