<?php

namespace App\Services\Operations;

use App\Events\Operations\OperationsLiveExamsUpdated;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\QuizViolation;
use App\Models\Result;
use App\Services\LiveQuizSessionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OperationsLiveExamService
{
    public function snapshot(): array
    {
        return Cache::remember('operations:live-exams', 5, fn () => $this->build());
    }

    public function broadcastUpdate(): void
    {
        broadcast(new OperationsLiveExamsUpdated($this->build()))->toOthers();
    }

    public function build(): array
    {
        if (! Schema::hasTable('quiz_sessions')) {
            return ['summary' => $this->emptySummary(), 'exams' => [], 'feed' => []];
        }

        $liveService = app(LiveQuizSessionService::class);
        $activeQuery = $liveService->activeSessionsQuery();
        $heartbeatCutoff = now()->subSeconds(LiveQuizSessionService::HEARTBEAT_SECONDS);

        $activeQuizIds = (clone $activeQuery)->distinct()->pluck('quiz_id');

        $exams = Quiz::query()
            ->with(['course:id,name', 'examiner:id,name', 'classGroup:id,name'])
            ->where(function ($q) use ($activeQuizIds) {
                $q->whereIn('id', $activeQuizIds)
                    ->orWhere(function ($q2) {
                        $q2->where('is_published', true)
                            ->where(function ($q3) {
                                $q3->whereNull('ends_at')->orWhere('ends_at', '>', now());
                            })
                            ->where(function ($q3) {
                                $q3->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                            });
                    });
            })
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get()
            ->map(fn (Quiz $quiz) => $this->mapExam($quiz, $heartbeatCutoff))
            ->values()
            ->all();

        $today = now()->startOfDay();
        $activeTakers = $liveService->countActive();
        $completedToday = QuizSession::query()->whereNotNull('ended_at')->where('ended_at', '>=', $today)->count();
        $disconnected = QuizSession::query()
            ->whereNotNull('start_time')->whereNull('ended_at')
            ->where('start_time', '>=', $today)
            ->where('last_heartbeat_at', '<', now()->subMinutes(5))
            ->count();
        $submissionsPerMinute = QuizSession::query()
            ->whereNotNull('ended_at')->where('ended_at', '>=', now()->subMinute())->count();

        $avgProgress = collect($exams)->avg('completion_percentage') ?: 0;

        $feed = QuizSession::query()
            ->with(['quiz:id,title'])
            ->where(function ($q) {
                $q->where('start_time', '>=', now()->subMinutes(30))
                    ->orWhere('ended_at', '>=', now()->subMinutes(30));
            })
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get()
            ->map(fn ($s) => [
                'type' => 'exam',
                'student' => $s->student_index,
                'exam' => $s->quiz?->title,
                'status' => $s->ended_at ? 'submitted' : ($s->last_heartbeat_at >= now()->subSeconds(90) ? 'writing' : 'disconnected'),
                'time' => ($s->ended_at ?? $s->start_time)?->toIso8601String(),
            ])
            ->all();

        return [
            'summary' => [
                'active_exams' => count(array_filter($exams, fn ($e) => ($e['students_active'] ?? 0) > 0)),
                'students_active' => $activeTakers,
                'students_completed' => $completedToday,
                'students_disconnected' => $disconnected,
                'avg_progress' => round($avgProgress, 1),
                'submissions_per_minute' => $submissionsPerMinute,
            ],
            'exams' => $exams,
            'feed' => $feed,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function mapExam(Quiz $quiz, $heartbeatCutoff): array
    {
        $sessions = QuizSession::query()->where('quiz_id', $quiz->id);
        $assigned = Schema::hasTable('class_group_students') && $quiz->class_group_id
            ? ClassGroupStudent::query()->where('class_group_id', $quiz->class_group_id)->count()
            : $sessions->count();

        $active = (clone $sessions)
            ->whereNotNull('start_time')->whereNull('ended_at')
            ->where('last_heartbeat_at', '>=', $heartbeatCutoff)
            ->count();

        $submitted = (clone $sessions)->whereNotNull('ended_at')->count();
        $completion = $assigned > 0 ? round(($submitted / $assigned) * 100, 1) : 0;

        $avgScore = Result::query()
            ->whereHas('quizSession', fn ($q) => $q->where('quiz_id', $quiz->id))
            ->avg('score');

        $criticalViolations = QuizViolation::query()
            ->whereHas('quizSession', fn ($q) => $q->where('quiz_id', $quiz->id))
            ->where('severity', QuizViolation::SEVERITY_CRITICAL)
            ->where('occurred_at', '>=', now()->subHours(6))
            ->count();

        $risk = match (true) {
            $criticalViolations >= 5 => 'critical',
            $criticalViolations >= 2 => 'high',
            $criticalViolations >= 1 => 'medium',
            default => 'low',
        };

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'course' => $quiz->course?->name ?? $quiz->classGroup?->name,
            'examiner' => $quiz->examiner?->name,
            'starts_at' => $quiz->starts_at?->toIso8601String(),
            'ends_at' => $quiz->ends_at?->toIso8601String(),
            'students_assigned' => $assigned,
            'students_active' => $active,
            'students_submitted' => $submitted,
            'completion_percentage' => $completion,
            'average_score' => $avgScore ? round((float) $avgScore, 1) : null,
            'risk_level' => $risk,
            'is_paused' => (bool) ($quiz->is_paused ?? false),
        ];
    }

    protected function emptySummary(): array
    {
        return [
            'active_exams' => 0,
            'students_active' => 0,
            'students_completed' => 0,
            'students_disconnected' => 0,
            'avg_progress' => 0,
            'submissions_per_minute' => 0,
        ];
    }
}
