<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe staff user deletion — clears FK references before removing the account.
 */
class UserStaffLifecycleService
{
    public function deleteStaffUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->prepareForDeletion($user);
            $user->courses()->detach();
            $user->delete();
        });
    }

    private function prepareForDeletion(User $user): void
    {
        $userId = (int) $user->id;

        DB::table('users')
            ->where('id', $userId)
            ->update(['remember_token' => null, 'updated_at' => now()]);

        if (Schema::hasTable('class_group_course') && Schema::hasColumn('class_group_course', 'examiner_id')) {
            DB::table('class_group_course')->where('examiner_id', $userId)->update(['examiner_id' => null]);
        }

        if (Schema::hasTable('quizzes') && Schema::hasColumn('quizzes', 'examiner_id')) {
            DB::table('quizzes')->where('examiner_id', $userId)->update(['examiner_id' => null]);
        }

        if (Schema::hasTable('support_sessions') && Schema::hasColumn('support_sessions', 'assigned_admin_id')) {
            DB::table('support_sessions')->where('assigned_admin_id', $userId)->update(['assigned_admin_id' => null]);
        }

        if (Schema::hasTable('attendance_upload_logs') && Schema::hasColumn('attendance_upload_logs', 'uploaded_by')) {
            DB::table('attendance_upload_logs')->where('uploaded_by', $userId)->update(['uploaded_by' => null]);
        }

        if (Schema::hasTable('project_proposals') && Schema::hasColumn('project_proposals', 'user_id')) {
            DB::table('project_proposals')->where('user_id', $userId)->update(['user_id' => null]);
        }

        if (Schema::hasTable('groups') && Schema::hasColumn('groups', 'leader_id')) {
            DB::table('groups')->where('leader_id', $userId)->update(['leader_id' => null]);
        }

        if (Schema::hasTable('sms_logs') && Schema::hasColumn('sms_logs', 'user_id')) {
            DB::table('sms_logs')->where('user_id', $userId)->update(['user_id' => null]);
        }

        $sessionTable = config('session.table', 'sessions');
        if (config('session.driver') === 'database'
            && Schema::hasTable($sessionTable)
            && Schema::hasColumn($sessionTable, 'user_id')) {
            DB::table($sessionTable)->where('user_id', $userId)->delete();
        }

        if (Schema::hasTable('class_groups') && Schema::hasColumn('class_groups', 'examiner_id')) {
            ClassGroup::query()->where('examiner_id', $userId)->delete();
        }
    }
}
