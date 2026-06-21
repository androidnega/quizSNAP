<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseQueryLog extends Model
{
    public $timestamps = false;

    public const STATUS_SLOW = 'slow';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEADLOCK = 'deadlock';

    protected $fillable = [
        'sql',
        'bindings',
        'execution_time_ms',
        'status',
        'route',
        'controller',
        'user_id',
        'connection',
        'error_message',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'bindings' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
