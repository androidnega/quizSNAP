<?php

namespace App\Observers;

use App\Models\Quiz;
use App\Services\Monitoring\AuditTrailService;

class QuizAuditObserver
{
    public function created(Quiz $quiz): void
    {
        try {
            app(AuditTrailService::class)->log('Quiz Created', Quiz::class, $quiz->id, null, $quiz->only(['title', 'status']));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function updated(Quiz $quiz): void
    {
        if (! $quiz->wasChanged()) {
            return;
        }

        try {
            app(AuditTrailService::class)->log(
                'Quiz Updated',
                Quiz::class,
                $quiz->id,
                array_intersect_key($quiz->getOriginal(), $quiz->getChanges()),
                $quiz->getChanges(),
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function deleted(Quiz $quiz): void
    {
        try {
            app(AuditTrailService::class)->log('Quiz Deleted', Quiz::class, $quiz->id, $quiz->only(['title', 'status']));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
