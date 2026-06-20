<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentNotification extends Model
{
    public const TYPE_NEW_QUIZ = 'new_quiz';

    public const TYPE_TIMETABLE = 'timetable';

    public const TYPE_RESULT_HELD = 'result_held';

    public const TYPE_RESULT_RELEASED = 'result_released';

    public const TYPE_STAFF_MESSAGE = 'staff_message';

    protected $fillable = [
        'student_index',
        'student_index_hash',
        'type',
        'title',
        'body',
        'action_url',
        'meta',
        'source_id',
        'source_type',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function iconClass(): string
    {
        return match ($this->type) {
            self::TYPE_NEW_QUIZ => 'fa-clipboard-list',
            self::TYPE_TIMETABLE => 'fa-calendar-alt',
            self::TYPE_RESULT_HELD => 'fa-lock',
            self::TYPE_RESULT_RELEASED => 'fa-unlock',
            self::TYPE_STAFF_MESSAGE => 'fa-bullhorn',
            default => 'fa-bell',
        };
    }
}
