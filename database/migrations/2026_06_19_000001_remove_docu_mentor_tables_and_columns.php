<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $docuMentorTables = [
            'document_ai_reviews',
            'submissions',
            'features',
            'chapters',
            'project_proposals',
            'project_files',
            'project_student_scores',
            'supervisor_project_approvals',
            'project_supervisors',
            'projects',
            'group_members',
            'groups',
            'categories',
            'group_names',
            'student_documents',
        ];

        foreach ($docuMentorTables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        if (Schema::hasTable('users')) {
            DB::table('users')->whereIn('role', ['student', 'leader'])->delete();

            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'group_leader')) {
                    $table->dropColumn('group_leader');
                }
            });
        }

        if (Schema::hasTable('student_levels') && Schema::hasColumn('student_levels', 'allows_docu_mentor')) {
            Schema::table('student_levels', function (Blueprint $table) {
                $table->dropColumn('allows_docu_mentor');
            });
        }

        if (Schema::hasTable('academic_years') && Schema::hasColumn('academic_years', 'submission_deadline')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropColumn('submission_deadline');
            });
        }
    }

    public function down(): void
    {
        // Docu Mentor schema is intentionally not restored.
    }
};
