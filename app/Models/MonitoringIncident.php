<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringIncident extends Model
{
    public const SEVERITY_P1 = 'P1';
    public const SEVERITY_P2 = 'P2';
    public const SEVERITY_P3 = 'P3';
    public const SEVERITY_P4 = 'P4';

    public const STATUS_OPEN = 'open';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'title', 'severity', 'status', 'owner_id', 'owner_name',
        'affected_services', 'linked_error_ids', 'linked_deployment_id',
        'timeline', 'resolution_notes', 'started_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'affected_services' => 'array',
            'linked_error_ids' => 'array',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function deployment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MonitoringDeployment::class, 'linked_deployment_id');
    }
}
