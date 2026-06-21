<?php

namespace App\Services\Operations;

use App\Events\DataUpdated;
use App\Models\Quiz;
use App\Models\User;

class OperationsExamControlService
{
    public function endExam(Quiz $quiz, ?User $actor = null): void
    {
        $quiz->update(['ends_at' => now(), 'is_active' => false, 'is_paused' => false]);
        $this->broadcastQuizUpdate();
        app(OperationsAlertService::class)->raise(
            'exam_ended',
            'info',
            'Exam ended',
            "Exam \"{$quiz->title}\" was ended by operations.",
            ['quiz_id' => $quiz->id, 'actor_id' => $actor?->id]
        );
    }

    public function extendTime(Quiz $quiz, int $additionalMinutes, ?User $actor = null): void
    {
        if (! $quiz->hasStarted() || $quiz->hasEnded()) {
            throw new \InvalidArgumentException('Exam is not currently active.');
        }

        $newDuration = min(600, $quiz->duration_minutes + $additionalMinutes);
        $quiz->update(['duration_minutes' => $newDuration]);
        $this->broadcastQuizUpdate();
        app(OperationsAlertService::class)->raise(
            'exam_extended',
            'info',
            'Exam time extended',
            "Exam \"{$quiz->title}\" extended by {$additionalMinutes} minutes.",
            ['quiz_id' => $quiz->id, 'actor_id' => $actor?->id]
        );
    }

    public function pauseExam(Quiz $quiz, ?User $actor = null): void
    {
        if (! $quiz->hasStarted() || $quiz->hasEnded()) {
            throw new \InvalidArgumentException('Exam is not currently active.');
        }

        $quiz->update(['is_paused' => true]);
        $this->broadcastQuizUpdate();
        app(OperationsAlertService::class)->raise(
            'exam_paused',
            'warning',
            'Exam paused',
            "Exam \"{$quiz->title}\" has been paused.",
            ['quiz_id' => $quiz->id, 'actor_id' => $actor?->id]
        );
    }

    public function resumeExam(Quiz $quiz, ?User $actor = null): void
    {
        $quiz->update(['is_paused' => false]);
        $this->broadcastQuizUpdate();
        app(OperationsAlertService::class)->raise(
            'exam_resumed',
            'info',
            'Exam resumed',
            "Exam \"{$quiz->title}\" has been resumed.",
            ['quiz_id' => $quiz->id, 'actor_id' => $actor?->id]
        );
    }

    public function broadcastMessage(Quiz $quiz, string $message, ?User $actor = null): void
    {
        $quiz->update(['operations_broadcast_message' => $message]);
        $this->broadcastQuizUpdate();
        app(OperationsAlertService::class)->raise(
            'exam_broadcast',
            'info',
            'Broadcast sent',
            "Message broadcast to exam \"{$quiz->title}\".",
            ['quiz_id' => $quiz->id, 'message' => $message, 'actor_id' => $actor?->id]
        );
    }

    protected function broadcastQuizUpdate(): void
    {
        try {
            broadcast(new DataUpdated('quizzes'))->toOthers();
        } catch (\Throwable) {
            // ignore
        }
    }
}
