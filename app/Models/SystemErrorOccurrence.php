<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemErrorOccurrence extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'system_error_id',
        'user_id',
        'user_name',
        'user_role',
        'session_id',
        'browser',
        'device',
        'operating_system',
        'ip_address',
        'environment',
        'request_payload',
        'stack_trace',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function systemError(): BelongsTo
    {
        return $this->belongsTo(SystemError::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
