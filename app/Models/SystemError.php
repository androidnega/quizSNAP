<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemError extends Model
{
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_FATAL = 'fatal';

    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'fingerprint',
        'exception_class',
        'exception_type',
        'message',
        'error_code',
        'severity',
        'file',
        'line',
        'class_name',
        'method',
        'route',
        'url',
        'http_method',
        'source_context',
        'occurrence_count',
        'affected_users_count',
        'affected_user_ids',
        'resolution_status',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'source_context' => 'array',
            'affected_user_ids' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(SystemErrorOccurrence::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
