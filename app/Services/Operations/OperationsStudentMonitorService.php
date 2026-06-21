<?php

namespace App\Services\Operations;

use App\Events\Operations\OperationsStudentsUpdated;
use App\Models\MonitoringUserSession;
use App\Models\QuizSession;
use App\Services\LiveQuizSessionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OperationsStudentMonitorService
{
    public function snapshot(): array
    {
        return Cache::remember('operations:students', 5, fn () => $this->build());
    }

    public function broadcastUpdate(): void
    {
        broadcast(new OperationsStudentsUpdated($this->build()))->toOthers();
    }

    protected function build(): array
    {
        if (! Schema::hasTable('quiz_sessions')) {
            return ['students' => [], 'summary' => ['online' => 0, 'in_exam' => 0, 'disconnected' => 0]];
        }

        $heartbeatCutoff = now()->subSeconds(LiveQuizSessionService::HEARTBEAT_SECONDS);

        $sessions = QuizSession::query()
            ->with(['quiz:id,title'])
            ->whereNotNull('start_time')
            ->whereNull('ended_at')
            ->orderByDesc('last_heartbeat_at')
            ->limit(100)
            ->get();

        $students = $sessions->map(function (QuizSession $session) use ($heartbeatCutoff) {
            $online = $session->last_heartbeat_at && $session->last_heartbeat_at >= $heartbeatCutoff;
            $parsed = QuizSession::parseUserAgent($session->user_agent ?? '');
            $duration = $session->start_time ? $session->start_time->diffInMinutes(now()) : 0;

            $monitoring = Schema::hasTable('monitoring_user_sessions')
                ? MonitoringUserSession::query()
                    ->where('session_id', $session->session_token)
                    ->where('is_active', true)
                    ->first()
                : null;

            return [
                'session_id' => $session->id,
                'student_index' => $session->student_index,
                'exam' => $session->quiz?->title,
                'quiz_id' => $session->quiz_id,
                'current_page' => $monitoring?->current_page ?? '/quiz',
                'device' => $session->device_name ?? $parsed['device_name'] ?? $session->device_type,
                'browser' => $parsed['browser'] ?? null,
                'location' => $monitoring?->location,
                'ip_address' => $session->ip_address,
                'session_duration_minutes' => $duration,
                'last_activity_at' => $session->last_heartbeat_at?->toIso8601String(),
                'connection_quality' => $online ? 'good' : 'disconnected',
                'tab_visible' => $online,
                'online_status' => $online ? 'online' : 'disconnected',
                'violations' => ($session->minor_violations ?? 0) + ($session->major_violations ?? 0),
            ];
        })->values()->all();

        $online = collect($students)->where('online_status', 'online')->count();
        $disconnected = collect($students)->where('online_status', 'disconnected')->count();

        return [
            'students' => $students,
            'summary' => [
                'online' => $online,
                'in_exam' => count($students),
                'disconnected' => $disconnected,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
