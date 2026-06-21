<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntelligenceAnomaly extends Model
{
    public const UPDATED_AT = null;
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'anomaly_type', 'severity', 'title', 'description', 'metrics', 'status', 'detected_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
