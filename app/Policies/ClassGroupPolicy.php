<?php

namespace App\Policies;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ClassGroupPolicy
{
    /**
     * Admin (Super Admin), Coordinator (academic structure owner), and Examiners can access class groups.
     * Coordinator manages all in scope; Examiner only their assigned class groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isCoordinator();
    }

    /**
     * Check if examiner is assigned to this class group (either owns it or teaches a course in it).
     */
    private function isExaminerAssignedToClassGroup(User $user, ClassGroup $classGroup): bool
    {
        if ((int) $classGroup->examiner_id === (int) $user->id) {
            return true;
        }

        if (Schema::hasColumn('class_group_course', 'examiner_id')) {
            return $classGroup->courses()
                ->wherePivot('examiner_id', $user->id)
                ->exists();
        }

        return false;
    }

    /** Super admin: all groups. Coordinator: faculty/department/institution scope. */
    private function coordinatorCanAccess(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role !== User::ROLE_COORDINATOR && ! (bool) ($user->coordinator ?? false)) {
            return false;
        }

        return in_array((int) $classGroup->id, $user->classGroupIds(), true);
    }

    public function view(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === User::ROLE_COORDINATOR || (bool) ($user->coordinator ?? false)) {
            return $this->coordinatorCanAccess($user, $classGroup);
        }

        if (! $user->isStaff()) {
            return false;
        }

        return $this->isExaminerAssignedToClassGroup($user, $classGroup);
    }

    /**
     * Admin and Coordinator can create class groups (assign examiner). Examiners cannot create (Coordinator manages academic structure).
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === User::ROLE_COORDINATOR || (bool) ($user->coordinator ?? false)) {
            return true;
        }

        return false;
    }

    /**
     * Only Super Admin and Coordinator can update class group or manage students (within scope).
     */
    public function update(User $user, ClassGroup $classGroup): bool
    {
        return $this->coordinatorCanAccess($user, $classGroup);
    }

    /**
     * Only examiner (assigned to the group) or Super Admin can generate a one-time fallback login code.
     * Coordinators must not have access to generate code.
     */
    public function generateFallbackCode(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (! $user->isStaff() || $user->isCoordinator()) {
            return false;
        }

        return $this->isExaminerAssignedToClassGroup($user, $classGroup);
    }

    public function delete(User $user, ClassGroup $classGroup): bool
    {
        return $this->coordinatorCanAccess($user, $classGroup);
    }
}
