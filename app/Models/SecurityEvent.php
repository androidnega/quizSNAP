<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    public $timestamps = false;

    public const TYPE_FAILED_LOGIN = 'failed_login';
    public const TYPE_LOCKED_ACCOUNT = 'locked_account';
    public const TYPE_PERMISSION_DENIED = 'permission_denied';
    public const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    public const TYPE_ROLE_ESCALATION = 'role_escalation_attempt';
    public const TYPE_SUSPICIOUS_REQUEST = 'suspicious_request';
    public const TYPE_CSRF_FAILURE = 'csrf_failure';
    public const TYPE_RATE_LIMIT = 'rate_limit_violation';
    public const TYPE_INVALID_TOKEN = 'invalid_token';

    protected $fillable = [
        'event_type',
        'severity',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'route',
        'description',
        'meta',
        'risk_score',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
