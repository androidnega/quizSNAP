<?php

namespace App\Services\Operations;

use App\Models\OperationsExamIncident;
use App\Models\QuizViolation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class OperationsExamIncidentService
{
    public function listOpen(int $limit = 50)
    {
        if (! Schema::hasTable('operations_exam_incidents')) {
            return collect();
        }

        return OperationsExamIncident::query()
            ->with(['quiz:id,title', 'quizSession:id,student_index'])
            ->where('status', '!=', OperationsExamIncident::STATUS_RESOLVED)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get();
    }

    public function history(int $limit = 100)
    {
        if (! Schema::hasTable('operations_exam_incidents')) {
            return collect();
        }

        return OperationsExamIncident::query()
            ->with(['quiz:id,title'])
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $data, ?User $actor = null): OperationsExamIncident
    {
        $incident = OperationsExamIncident::query()->create([
            'quiz_id' => $data['quiz_id'] ?? null,
            'quiz_session_id' => $data['quiz_session_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'incident_type' => $data['incident_type'] ?? null,
            'severity' => $data['severity'] ?? OperationsExamIncident::SEVERITY_MEDIUM,
            'status' => OperationsExamIncident::STATUS_OPEN,
            'assigned_to' => $data['assigned_to'] ?? $actor?->id,
            'assigned_to_name' => $data['assigned_to_name'] ?? $actor?->name,
            'meta' => $data['meta'] ?? null,
            'started_at' => now(),
        ]);

        app(OperationsAlertService::class)->raise(
            'exam_incident',
            $incident->severity === OperationsExamIncident::SEVERITY_CRITICAL ? 'critical' : 'warning',
            'Exam incident: '.$incident->title,
            $incident->description ?? $incident->title,
            ['incident_id' => $incident->id]
        );

        return $incident;
    }

    public function assign(OperationsExamIncident $incident, User $user): void
    {
        $incident->update([
            'assigned_to' => $user->id,
            'assigned_to_name' => $user->name,
            'status' => OperationsExamIncident::STATUS_IN_PROGRESS,
        ]);
    }

    public function resolve(OperationsExamIncident $incident, ?string $notes = null): void
    {
        $incident->update([
            'status' => OperationsExamIncident::STATUS_RESOLVED,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    public function syncFromRecentViolations(): int
    {
        if (! Schema::hasTable('quiz_violations') || ! Schema::hasTable('operations_exam_incidents')) {
            return 0;
        }

        $created = 0;
        $violations = QuizViolation::query()
            ->with('quizSession')
            ->where('severity', QuizViolation::SEVERITY_CRITICAL)
            ->where('occurred_at', '>=', now()->subMinutes(10))
            ->get();

        foreach ($violations as $violation) {
            $exists = OperationsExamIncident::query()
                ->where('quiz_session_id', $violation->quiz_session_id)
                ->where('incident_type', $violation->type)
                ->where('started_at', '>=', now()->subHour())
                ->exists();

            if ($exists) {
                continue;
            }

            $this->create([
                'quiz_id' => $violation->quizSession?->quiz_id,
                'quiz_session_id' => $violation->quiz_session_id,
                'title' => QuizViolation::labelForType($violation->type),
                'description' => 'Auto-created from proctoring violation.',
                'incident_type' => $violation->type,
                'severity' => OperationsExamIncident::SEVERITY_HIGH,
                'meta' => ['violation_id' => $violation->id],
            ]);

            $created++;
        }

        return $created;
    }
}
