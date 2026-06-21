<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringCapacitySnapshot extends Model
{
    public $timestamps = false;

    public const TYPE_DATABASE = 'database';
    public const TYPE_STORAGE = 'storage';

    protected $fillable = [
        'snapshot_type', 'total_bytes', 'used_bytes', 'free_bytes',
        'growth_rate_daily', 'breakdown', 'forecast', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'growth_rate_daily' => 'float',
            'breakdown' => 'array',
            'forecast' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
