<?php

namespace App\Policies;

use App\Models\ExamCalendar;
use App\Models\User;

class ExamCalendarPolicy
{
    /** Coordinator and Super Admin can manage; Examiner can view if they can view the class group. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isCoordinator();
    }

    public function view(User $user, ExamCalendar $examCalendar): bool
    {
        $examCalendar->loadMissing('classGroup');
        $cg = $examCalendar->classGroup;
        if (! $cg) {
            return $user->isSuperAdmin();
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array((int) $cg->id, $user->classGroupIds(), true);
    }

    /** Only Coordinator and Super Admin create/update/delete exam calendar entries. */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isCoordinatorOnly();
    }

    public function update(User $user, ExamCalendar $examCalendar): bool
    {
        return $this->view($user, $examCalendar)
            && ($user->isSuperAdmin() || $user->isCoordinatorOnly());
    }

    public function delete(User $user, ExamCalendar $examCalendar): bool
    {
        return $this->update($user, $examCalendar);
    }
}
