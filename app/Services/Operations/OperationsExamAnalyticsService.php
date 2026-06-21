<?php

namespace App\Services\Operations;

use App\Models\Course;
use App\Models\Department;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsExamAnalyticsService
{
    public function snapshot(int $days = 30): array
    {
        $since = now()->subDays($days);

        if (! Schema::hasTable('results')) {
            return $this->empty();
        }

        $results = Result::query()->where('submitted_at', '>=', $since);
        $avgScore = (clone $results)->avg('score');
        $total = (clone $results)->count();
        $passing = (clone $results)->where('score', '>=', 50)->count();

        return [
            'average_score' => $avgScore ? round((float) $avgScore, 1) : 0,
            'pass_rate' => $total > 0 ? round(($passing / $total) * 100, 1) : 0,
            'failure_rate' => $total > 0 ? round((($total - $passing) / $total) * 100, 1) : 0,
            'total_submissions' => $total,
            'completion_times' => $this->completionTimes($since),
            'performance_trends' => $this->performanceTrends($since),
            'course_comparison' => $this->courseComparison($since),
            'department_comparison' => $this->departmentComparison($since),
            'question_difficulty' => $this->questionDifficulty($since),
            'period_days' => $days,
        ];
    }

    protected function completionTimes($since): array
    {
        if (! Schema::hasTable('quiz_sessions')) {
            return [];
        }

        return QuizSession::query()
            ->whereNotNull('start_time')
            ->whereNotNull('ended_at')
            ->where('ended_at', '>=', $since)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, start_time, ended_at)) as avg_minutes')
            ->selectRaw('MIN(TIMESTAMPDIFF(MINUTE, start_time, ended_at)) as min_minutes')
            ->selectRaw('MAX(TIMESTAMPDIFF(MINUTE, start_time, ended_at)) as max_minutes')
            ->first()
            ?->only(['avg_minutes', 'min_minutes', 'max_minutes']) ?? [];
    }

    protected function performanceTrends($since): array
    {
        return Result::query()
            ->selectRaw('DATE(submitted_at) as day, AVG(score) as avg_score, COUNT(*) as submissions')
            ->where('submitted_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => $r->day, 'avg_score' => round((float) $r->avg_score, 1), 'submissions' => (int) $r->submissions])
            ->all();
    }

    protected function courseComparison($since): array
    {
        return Result::query()
            ->select('courses.name', DB::raw('AVG(results.score) as avg_score'), DB::raw('COUNT(*) as total'))
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('courses', 'courses.id', '=', 'quizzes.course_id')
            ->where('results.submitted_at', '>=', $since)
            ->groupBy('courses.id', 'courses.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'avg_score' => round((float) $r->avg_score, 1), 'total' => (int) $r->total])
            ->all();
    }

    protected function departmentComparison($since): array
    {
        if (! Schema::hasTable('departments') || ! Schema::hasTable('users')) {
            return [];
        }

        return Result::query()
            ->select('departments.name', DB::raw('AVG(results.score) as avg_score'), DB::raw('COUNT(*) as total'))
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('users', 'users.id', '=', 'quizzes.examiner_id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->where('results.submitted_at', '>=', $since)
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'avg_score' => round((float) $r->avg_score, 1), 'total' => (int) $r->total])
            ->all();
    }

    protected function questionDifficulty($since): array
    {
        if (! Schema::hasTable('answers') || ! Schema::hasColumn('answers', 'is_correct')) {
            return [];
        }

        return DB::table('answers')
            ->select('question_id', DB::raw('AVG(is_correct) as success_rate'), DB::raw('COUNT(*) as attempts'))
            ->where('created_at', '>=', $since)
            ->groupBy('question_id')
            ->orderBy('success_rate')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'question_id' => $r->question_id,
                'success_rate' => round(((float) $r->success_rate) * 100, 1),
                'attempts' => (int) $r->attempts,
            ])
            ->all();
    }

    protected function empty(): array
    {
        return [
            'average_score' => 0,
            'pass_rate' => 0,
            'failure_rate' => 0,
            'total_submissions' => 0,
            'completion_times' => [],
            'performance_trends' => [],
            'course_comparison' => [],
            'department_comparison' => [],
            'question_difficulty' => [],
            'period_days' => 30,
        ];
    }
}
