<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_WEBRTC = 'webrtc';

    public const TYPE_SYSTEM = 'system';

    public const TYPE_IMAGE = 'image';

    public const TYPE_AUDIO = 'audio';

    protected $fillable = [
        'support_session_id',
        'sender_type',
        'sender_id',
        'message_type',
        'body',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SupportSession::class, 'support_session_id');
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'support_session_id' => $this->support_session_id,
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'message_type' => $this->message_type,
            'body' => $this->body,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
