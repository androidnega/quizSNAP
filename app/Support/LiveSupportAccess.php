<?php

namespace App\Support;

use App\Models\ClassGroupStudent;
use App\Models\SupportSession;
use App\Models\User;

final class LiveSupportAccess
{
    public static function canRespond(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->role === User::ROLE_COORDINATOR
            || $user->role === User::ROLE_EXAMINER
            || (bool) ($user->coordinator ?? false);
    }

    public static function canDeleteSession(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public static function canClearStudentPhone(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /** @return list<string> Uppercase trimmed index numbers in staff scope. Empty = none. Null = all (super admin). */
    public static function scopedStudentIndices(User $user): ?array
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        if (! self::canRespond($user)) {
            return [];
        }

        $groupIds = $user->classGroupIds();
        if ($groupIds === []) {
            return [];
        }

        return ClassGroupStudent::query()
            ->whereIn('class_group_id', $groupIds)
            ->pluck('index_number')
            ->map(fn ($i) => strtoupper(trim((string) $i)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function sessionInScope(User $user, SupportSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $indices = self::scopedStudentIndices($user);
        if ($indices === []) {
            return false;
        }

        $index = strtoupper(trim((string) ($session->student_index ?? '')));
        if ($index !== '' && in_array($index, $indices, true)) {
            return true;
        }

        if ($session->institution_id && (int) $user->institution_id === (int) $session->institution_id) {
            return true;
        }

        return false;
    }

    public static function resolveInstitutionId(?string $indexNumber): ?int
    {
        if (! $indexNumber || trim($indexNumber) === '') {
            return null;
        }

        $cgStudent = ClassGroupStudent::findByIndexNumber(trim($indexNumber));

        return $cgStudent?->classGroup?->examiner?->institution_id
            ? (int) $cgStudent->classGroup->examiner->institution_id
            : null;
    }
}
