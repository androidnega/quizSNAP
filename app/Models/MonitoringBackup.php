<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringBackup extends Model
{
    public $timestamps = false;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_MISSING = 'missing';

    protected $fillable = [
        'backup_type', 'status', 'size_bytes', 'location',
        'retention_days', 'restore_test_status', 'backed_up_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'backed_up_at' => 'datetime',
        ];
    }
}
