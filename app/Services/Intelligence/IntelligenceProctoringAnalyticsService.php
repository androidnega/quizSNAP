<?php

namespace App\Services\Intelligence;

use App\Models\QuizSession;
use App\Models\QuizViolation;
use App\Services\Operations\OperationsProctoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IntelligenceProctoringAnalyticsService
{
    public function __construct(protected IntelligenceRiskEngine $risk) {}

    public function snapshot(int $days = 90): array
    {
        $since = now()->subDays($days);
        $ops = app(OperationsProctoringService::class)->snapshot();

        $repeatOffenders = [];
        if (Schema::hasTable('quiz_violations')) {
            $repeatOffenders = QuizViolation::query()
                ->select('quiz_session_id', DB::raw('COUNT(*) as total'))
                ->where('occurred_at', '>=', $since)
                ->groupBy('quiz_session_id')
                ->having('total', '>=', 3)
                ->orderByDesc('total')
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    $session = QuizSession::query()->find($row->quiz_session_id);

                    return [
                        'student_index' => $session?->student_index,
                        'violations' => (int) $row->total,
                        'integrity_score' => max(0, 100 - ((int) $row->total * 10)),
                    ];
                })
                ->filter(fn ($r) => $r['student_index'])
                ->values()
                ->all();
        }

        $totalViolations = array_sum(array_intersect_key($ops['summary'] ?? [], array_flip([
            'face_verification_failures', 'multiple_faces', 'phone_detected', 'tab_switching', 'copy_paste', 'window_blur',
        ])));

        $integrityScore = max(0, min(100, 100 - min(80, $totalViolations)));

        return array_merge($ops, [
            'integrity_score' => $integrityScore,
            'risk_score' => 100 - $integrityScore,
            'suspicious_activity_trend' => $this->weeklyViolationTrend($since),
            'repeat_offenders' => $repeatOffenders,
            'period_days' => $days,
        ]);
    }

    protected function weeklyViolationTrend($since): array
    {
        if (! Schema::hasTable('quiz_violations')) {
            return [];
        }

        return QuizViolation::query()
            ->where('occurred_at', '>=', $since)
            ->get()
            ->groupBy(fn ($v) => $v->occurred_at->format('Y-W'))
            ->map->count()
            ->all();
    }
}
