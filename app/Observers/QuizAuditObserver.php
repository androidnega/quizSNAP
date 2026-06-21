<?php

namespace App\Observers;

use App\Models\Quiz;
use App\Services\Monitoring\AuditTrailService;

class QuizAuditObserver
{
    public function created(Quiz $quiz): void
    {
        app(AuditTrailService::class)->log('Quiz Created', Quiz::class, $quiz->id, null, $quiz->only(['title', 'status']));
    }

    public function updated(Quiz $quiz): void
    {
        if ($quiz->wasChanged()) {
            app(AuditTrailService::class)->log(
                'Quiz Updated',
                Quiz::class,
                $quiz->id,
                array_intersect_key($quiz->getOriginal(), $quiz->getChanges()),
                $quiz->getChanges(),
            );
        }
    }

    public function deleted(Quiz $quiz): void
    {
        app(AuditTrailService::class)->log('Quiz Deleted', Quiz::class, $quiz->id, $quiz->only(['title', 'status']));
    }
}
