<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Faculty-scoped academic catalog queries (semesters, categories, levels, classes).
 * Academic years and app settings remain global.
 */
final class AcademicCatalogScope
{
    public static function apply(Builder $query, ?User $user, string $table): Builder
    {
        if (! Schema::hasColumn($table, 'faculty_id')) {
            return $query;
        }

        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $facultyId = $user->faculty_id;
        if (! $facultyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('faculty_id', $facultyId);
    }

    public static function facultyIdForWrite(?User $user): ?int
    {
        if (! $user || $user->isSuperAdmin()) {
            return $user?->faculty_id ? (int) $user->faculty_id : null;
        }

        return $user->faculty_id ? (int) $user->faculty_id : null;
    }

    public static function assertCanAccess(?User $user, $model): void
    {
        if (! $user || $user->isSuperAdmin()) {
            return;
        }

        if (! Schema::hasColumn($model->getTable(), 'faculty_id')) {
            return;
        }

        if ((int) ($model->faculty_id ?? 0) !== (int) ($user->faculty_id ?? -1)) {
            abort(403, 'This academic record is outside your faculty.');
        }
    }
}
