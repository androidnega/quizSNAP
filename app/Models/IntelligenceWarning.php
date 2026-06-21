<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntelligenceWarning extends Model
{
    public const UPDATED_AT = null;
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'warning_type', 'severity', 'title', 'message', 'subject_type', 'subject_key', 'status', 'meta', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
