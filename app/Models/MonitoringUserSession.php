<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringUserSession extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'user_name',
        'user_role',
        'actor_type',
        'ip_address',
        'current_page',
        'browser',
        'device',
        'location',
        'is_active',
        'last_activity_at',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
            'started_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
