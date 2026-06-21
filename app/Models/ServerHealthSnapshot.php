<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerHealthSnapshot extends Model
{
    public $timestamps = false;

    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';
    public const STATUS_OFFLINE = 'offline';

    protected $fillable = [
        'status',
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'disk_free_bytes',
        'load_average',
        'php_version',
        'laravel_version',
        'mysql_version',
        'queue_workers',
        'storage_usage_bytes',
        'uptime_seconds',
        'network_status',
        'meta',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_usage' => 'float',
            'ram_usage' => 'float',
            'disk_usage' => 'float',
            'load_average' => 'float',
            'meta' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
