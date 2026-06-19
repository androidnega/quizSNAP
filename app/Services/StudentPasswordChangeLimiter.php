<?php

namespace App\Services;

use App\Models\AuthAuditLog;
use App\Models\Student;

class StudentPasswordChangeLimiter
{
    public const MAX_CHANGES_PER_WEEK = 3;

    public const WINDOW_DAYS = 7;

    public const EVENT_PASSWORD_CHANGED = 'password_reset_completed';

    public static function recentChangeCount(Student $student): int
    {
        if (! $student->id) {
            return 0;
        }

        return AuthAuditLog::query()
            ->where('actor_type', 'student')
            ->where('actor_id', $student->id)
            ->where('event', self::EVENT_PASSWORD_CHANGED)
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->count();
    }

    public static function canChangePassword(Student $student): bool
    {
        return self::recentChangeCount($student) < self::MAX_CHANGES_PER_WEEK;
    }

    public static function remainingChanges(Student $student): int
    {
        return max(0, self::MAX_CHANGES_PER_WEEK - self::recentChangeCount($student));
    }

    public static function blockedMessage(): string
    {
        return 'You have already changed your password '
            . self::MAX_CHANGES_PER_WEEK
            . ' times in the last '
            . self::WINDOW_DAYS
            . ' days. For security, please wait before resetting again or contact support.';
    }
}
