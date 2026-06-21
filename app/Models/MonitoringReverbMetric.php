<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringReverbMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'connected_users', 'connected_channels', 'messages_per_minute',
        'events_per_minute', 'failed_broadcasts', 'connection_failures',
        'average_latency_ms', 'broadcast_queue_delay_ms', 'health_score', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'health_score' => 'float',
            'recorded_at' => 'datetime',
        ];
    }
}
