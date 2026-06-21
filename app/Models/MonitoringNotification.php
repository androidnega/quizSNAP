<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'title',
        'message',
        'meta',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
