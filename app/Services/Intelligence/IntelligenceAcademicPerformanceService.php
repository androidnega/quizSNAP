<?php

namespace App\Services\Intelligence;

use App\Models\Course;
use App\Models\Result;
use App\Models\User;
use App\Services\Operations\OperationsExamAnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IntelligenceAcademicPerformanceService
{
    public function snapshot(int $days = 90): array
    {
        $exam = app(OperationsExamAnalyticsService::class)->snapshot($days);
        $scores = $this->scoreDistribution($days);

        return array_merge($exam, [
            'median_score' => $scores['median'] ?? 0,
            'score_distribution' => $scores['distribution'] ?? [],
            'course_difficulty_index' => $this->courseDifficulty($days),
            'faculty_comparison' => $this->facultyComparison($days),
        ]);
    }

    protected function scoreDistribution(int $days): array
    {
        if (! Schema::hasTable('results')) {
            return ['median' => 0, 'distribution' => []];
        }

        $values = Result::query()
            ->where('submitted_at', '>=', now()->subDays($days))
            ->pluck('score')
            ->map(fn ($s) => (float) $s)
            ->sort()
            ->values();

        if ($values->isEmpty()) {
            return ['median' => 0, 'distribution' => []];
        }

        $mid = (int) floor($values->count() / 2);
        $median = $values->count() % 2
            ? $values[$mid]
            : ($values[$mid - 1] + $values[$mid]) / 2;

        $buckets = ['0-39' => 0, '40-49' => 0, '50-59' => 0, '60-69' => 0, '70-79' => 0, '80-100' => 0];
        foreach ($values as $score) {
            $key = match (true) {
                $score < 40 => '0-39',
                $score < 50 => '40-49',
                $score < 60 => '50-59',
                $score < 70 => '60-69',
                $score < 80 => '70-79',
                default => '80-100',
            };
            $buckets[$key]++;
        }

        return ['median' => round($median, 1), 'distribution' => $buckets];
    }

    protected function courseDifficulty(int $days): array
    {
        if (! Schema::hasTable('results') || ! Schema::hasTable('courses')) {
            return [];
        }

        return Result::query()
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('courses', 'courses.id', '=', 'quizzes.course_id')
            ->where('results.submitted_at', '>=', now()->subDays($days))
            ->groupBy('courses.id', 'courses.name')
            ->selectRaw('courses.name, AVG(results.score) as avg_score, COUNT(*) as attempts')
            ->orderBy('avg_score')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'course' => $r->name,
                'difficulty_index' => max(0, (int) round(100 - (float) $r->avg_score)),
                'avg_score' => round((float) $r->avg_score, 1),
                'attempts' => (int) $r->attempts,
            ])
            ->all();
    }

    protected function facultyComparison(int $days): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('results')) {
            return [];
        }

        return Result::query()
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('users', 'users.id', '=', 'quizzes.examiner_id')
            ->where('results.submitted_at', '>=', now()->subDays($days))
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.name, AVG(results.score) as avg_score, COUNT(*) as total')
            ->orderByDesc('avg_score')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'avg_score' => round((float) $r->avg_score, 1), 'total' => (int) $r->total])
            ->all();
    }
}
