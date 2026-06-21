<?php

namespace App\Services\Operations;

use App\Events\Operations\OperationsProctoringUpdated;
use App\Models\QuizSession;
use App\Models\QuizViolation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsProctoringService
{
    protected array $typeGroups = [
        'face_verification_failures' => ['face_mismatch', 'no_face', 'no_face_during_quiz', 'face_out_of_frame'],
        'multiple_faces' => ['multiple_faces', 'multiple_faces_pre_quiz', 'multiple_faces_during_quiz'],
        'no_face' => ['no_face', 'no_face_during_quiz', 'face_lost_repeatedly'],
        'phone_detected' => ['phone_detected'],
        'tab_switching' => ['tab_switch'],
        'copy_paste' => ['copy_paste'],
        'window_blur' => ['blur'],
        'suspicious_browser' => ['screenshot_attempt', 'window_resize', 'right_click', 'multiple_ip'],
    ];

    public function snapshot(): array
    {
        return Cache::remember('operations:proctoring', 5, fn () => $this->build());
    }

    public function broadcastUpdate(): void
    {
        broadcast(new OperationsProctoringUpdated($this->build()))->toOthers();
    }

    protected function build(): array
    {
        if (! Schema::hasTable('quiz_violations')) {
            return ['summary' => [], 'flagged_students' => [], 'feed' => []];
        }

        $since = now()->subHours(6);
        $counts = [];
        foreach ($this->typeGroups as $key => $types) {
            $counts[$key] = QuizViolation::query()
                ->whereIn('type', $types)
                ->where('occurred_at', '>=', $since)
                ->count();
        }

        $flagged = QuizViolation::query()
            ->select('quiz_session_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(CASE WHEN severity = "critical" THEN 1 ELSE 0 END) as has_critical'))
            ->where('occurred_at', '>=', $since)
            ->groupBy('quiz_session_id')
            ->orderByDesc('violation_count')
            ->limit(25)
            ->get()
            ->map(function ($row) {
                $session = QuizSession::query()->find($row->quiz_session_id);

                return [
                    'student_index' => $session?->student_index,
                    'quiz_id' => $session?->quiz_id,
                    'violation_count' => (int) $row->violation_count,
                    'risk_score' => ((int) $row->has_critical) ? 90 : min(85, 20 + ((int) $row->violation_count * 10)),
                    'severity' => ((int) $row->has_critical) ? 'critical' : 'warning',
                ];
            })
            ->filter(fn ($s) => $s['student_index'])
            ->values()
            ->all();

        $feed = QuizViolation::query()
            ->with(['quizSession:id,student_index,quiz_id', 'quizSession.quiz:id,title'])
            ->where('occurred_at', '>=', now()->subMinutes(30))
            ->orderByDesc('occurred_at')
            ->limit(30)
            ->get()
            ->map(fn ($v) => [
                'type' => 'proctoring',
                'violation_type' => $v->type,
                'label' => QuizViolation::labelForType($v->type),
                'severity' => $v->severity,
                'student' => $v->quizSession?->student_index,
                'exam' => $v->quizSession?->quiz?->title,
                'time' => $v->occurred_at?->toIso8601String(),
            ])
            ->all();

        return [
            'summary' => array_merge($counts, [
                'flagged_students' => count($flagged),
                'total_violations' => array_sum($counts),
            ]),
            'flagged_students' => $flagged,
            'feed' => $feed,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
