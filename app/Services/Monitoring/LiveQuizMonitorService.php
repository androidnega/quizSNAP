<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringLiveQuizUpdated;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Result;
use App\Services\LiveQuizSessionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveQuizMonitorService
{
    public function snapshot(): array
    {
        return Cache::remember('monitoring:live-quiz:snapshot', 5, fn () => $this->buildSnapshot());
    }

    public function broadcastUpdate(): void
    {
        $payload = $this->buildSnapshot();
        broadcast(new MonitoringLiveQuizUpdated($payload))->toOthers();
    }

    protected function buildSnapshot(): array
    {
        if (! Schema::hasTable('quiz_sessions')) {
            return $this->emptySnapshot();
        }

        $liveService = app(LiveQuizSessionService::class);
        $activeQuery = $liveService->activeSessionsQuery();

        $activeTakers = $liveService->countActive();
        $activeQuizzes = (clone $activeQuery)->distinct('quiz_id')->count('quiz_id');

        $today = now()->startOfDay();
        $completedToday = QuizSession::query()
            ->whereNotNull('ended_at')
            ->where('ended_at', '>=', $today)
            ->count();

        $startedToday = QuizSession::query()
            ->whereNotNull('start_time')
            ->where('start_time', '>=', $today)
            ->count();

        $abandonedToday = QuizSession::query()
            ->whereNotNull('start_time')
            ->whereNull('ended_at')
            ->where('start_time', '>=', $today)
            ->where('last_heartbeat_at', '<', now()->subMinutes(5))
            ->count();

        $submissionsLastMinute = QuizSession::query()
            ->whereNotNull('ended_at')
            ->where('ended_at', '>=', now()->subMinute())
            ->count();

        $avgCompletion = (int) QuizSession::query()
            ->whereNotNull('start_time')
            ->whereNotNull('ended_at')
            ->where('ended_at', '>=', $today)
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, start_time, ended_at)) as avg_seconds')
            ->value('avg_seconds');

        $successRate = $startedToday > 0
            ? round(($completedToday / $startedToday) * 100, 1)
            : 0;

        $leaderboard = Quiz::query()
            ->select('quizzes.id', 'quizzes.title')
            ->join('quiz_sessions', 'quiz_sessions.quiz_id', '=', 'quizzes.id')
            ->whereNotNull('quiz_sessions.start_time')
            ->whereNull('quiz_sessions.ended_at')
            ->where('quiz_sessions.last_heartbeat_at', '>=', now()->subSeconds(LiveQuizSessionService::HEARTBEAT_SECONDS))
            ->groupBy('quizzes.id', 'quizzes.title')
            ->selectRaw('COUNT(quiz_sessions.id) as participants')
            ->orderByDesc('participants')
            ->limit(10)
            ->get()
            ->map(fn ($q) => ['title' => $q->title, 'participants' => (int) $q->participants])
            ->all();

        $feed = QuizSession::query()
            ->with(['quiz:id,title'])
            ->whereNotNull('start_time')
            ->where(function ($q) {
                $q->where('start_time', '>=', now()->subMinutes(30))
                    ->orWhere('ended_at', '>=', now()->subMinutes(30));
            })
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'student' => $s->student_index,
                'quiz' => $s->quiz?->title,
                'status' => $s->ended_at ? 'completed' : 'active',
                'time' => ($s->ended_at ?? $s->start_time)?->toIso8601String(),
            ])
            ->all();

        return [
            'active_quizzes' => $activeQuizzes,
            'active_takers' => $activeTakers,
            'current_participants' => $activeTakers,
            'completed_attempts' => $completedToday,
            'abandoned_attempts' => $abandonedToday,
            'submissions_per_minute' => $submissionsLastMinute,
            'avg_completion_seconds' => $avgCompletion,
            'success_rate' => $successRate,
            'leaderboard' => $leaderboard,
            'feed' => $feed,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function emptySnapshot(): array
    {
        return [
            'active_quizzes' => 0,
            'active_takers' => 0,
            'current_participants' => 0,
            'completed_attempts' => 0,
            'abandoned_attempts' => 0,
            'submissions_per_minute' => 0,
            'avg_completion_seconds' => 0,
            'success_rate' => 0,
            'leaderboard' => [],
            'feed' => [],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
