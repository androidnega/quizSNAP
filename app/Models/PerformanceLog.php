<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'route',
        'controller',
        'page_load_time_ms',
        'controller_time_ms',
        'memory_usage_kb',
        'query_time_ms',
        'request_duration_ms',
        'response_duration_ms',
        'cache_hits',
        'cache_misses',
        'user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
