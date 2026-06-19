<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralized data scoping for role-based isolation.
 */
class DataScopeService
{
    public function __construct(
        private ?User $user = null
    ) {
        $this->user = $user ?? auth()->user();
    }

    public static function for(?User $user = null): self
    {
        return new self($user);
    }

    public function classGroupIds(): array
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return [];
        }

        return $user->classGroupIds();
    }

    public function courseIds(): array
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return [];
        }

        return $user->assignedCourseIds();
    }

    public function scopeQuizzes(Builder $query): Builder
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $classGroupIds = $user->classGroupIds();

        return $query->where(function (Builder $q) use ($user, $classGroupIds) {
            if (!empty($classGroupIds)) {
                $q->whereIn('class_group_id', $classGroupIds);
            }
            if ($user->id) {
                $q->orWhere('examiner_id', $user->id);
            }
            if (empty($classGroupIds) && !$user->id) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function scopeClassGroups(Builder $query): Builder
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $ids = $user->classGroupIds();

        return $query->whereIn('id', $ids);
    }
}
