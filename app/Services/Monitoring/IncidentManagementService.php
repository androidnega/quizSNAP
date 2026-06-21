<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringIncident;
use App\Models\User;

class IncidentManagementService
{
    public function create(array $data, ?User $user = null): MonitoringIncident
    {
        return MonitoringIncident::query()->create([
            'title' => $data['title'],
            'severity' => $data['severity'] ?? MonitoringIncident::SEVERITY_P3,
            'status' => MonitoringIncident::STATUS_OPEN,
            'owner_id' => $user?->id ?? ($data['owner_id'] ?? null),
            'owner_name' => $user?->name ?? ($data['owner_name'] ?? null),
            'affected_services' => $data['affected_services'] ?? [],
            'linked_error_ids' => $data['linked_error_ids'] ?? [],
            'linked_deployment_id' => $data['linked_deployment_id'] ?? null,
            'timeline' => $data['timeline'] ?? null,
            'started_at' => now(),
        ]);
    }

    public function updateStatus(MonitoringIncident $incident, string $status, ?string $notes = null): MonitoringIncident
    {
        $incident->update([
            'status' => $status,
            'resolution_notes' => $notes ?? $incident->resolution_notes,
            'resolved_at' => $status === MonitoringIncident::STATUS_RESOLVED ? now() : null,
        ]);

        return $incident->fresh();
    }

    public function listOpen(int $limit = 50)
    {
        return MonitoringIncident::query()
            ->where('status', '!=', MonitoringIncident::STATUS_RESOLVED)
            ->orderByRaw("FIELD(severity, 'P1','P2','P3','P4')")
            ->limit($limit)
            ->get();
    }
}
