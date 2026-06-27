<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportSession extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'uuid',
        'client_token',
        'status',
        'student_index',
        'student_name',
        'student_phone',
        'student_email',
        'institution_id',
        'page_url',
        'issue_category',
        'assigned_admin_id',
        'claimed_at',
        'closed_at',
        'screen_share_active',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_message_at' => 'datetime',
            'screen_share_active' => 'boolean',
        ];
    }

    public static function createGuestSession(array $attrs = []): self
    {
        return self::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'client_token' => Str::random(48),
            'status' => self::STATUS_WAITING,
        ], $attrs));
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_ACTIVE], true);
    }

    /** @return array<string, mixed> */
    public function toClientArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'student_index' => $this->student_index,
            'student_name' => $this->student_name,
            'student_phone' => $this->student_phone,
            'student_email' => $this->student_email,
            'page_url' => $this->page_url,
            'issue_category' => $this->issue_category,
            'screen_share_active' => $this->screen_share_active,
            'assigned_admin' => $this->assignedAdmin ? [
                'id' => $this->assignedAdmin->id,
                'name' => $this->assignedAdmin->name ?: $this->assignedAdmin->username,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
        ];
    }
}
