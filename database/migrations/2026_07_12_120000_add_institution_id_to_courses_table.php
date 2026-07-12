<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isolate courses per institution so coordinators cannot see another school's catalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        if (! Schema::hasColumn('courses', 'institution_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('institution_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('institutions')
                    ->nullOnDelete();
                $table->index('institution_id');
            });
        }

        // Backfill from assigned examiners
        if (Schema::hasTable('course_user') && Schema::hasTable('users')) {
            $rows = DB::table('course_user')
                ->join('users', 'users.id', '=', 'course_user.user_id')
                ->whereNotNull('users.institution_id')
                ->select('course_user.course_id', 'users.institution_id')
                ->orderBy('course_user.course_id')
                ->get();

            $byCourse = [];
            foreach ($rows as $row) {
                $cid = (int) $row->course_id;
                if (! isset($byCourse[$cid])) {
                    $byCourse[$cid] = (int) $row->institution_id;
                }
            }
            foreach ($byCourse as $courseId => $institutionId) {
                DB::table('courses')
                    ->where('id', $courseId)
                    ->whereNull('institution_id')
                    ->update(['institution_id' => $institutionId]);
            }
        }

        // Backfill remaining from class-group examiner institution
        if (Schema::hasTable('class_group_course') && Schema::hasTable('class_groups')) {
            $rows = DB::table('class_group_course')
                ->join('class_groups', 'class_groups.id', '=', 'class_group_course.class_group_id')
                ->join('users', 'users.id', '=', 'class_groups.examiner_id')
                ->whereNotNull('users.institution_id')
                ->select('class_group_course.course_id', 'users.institution_id')
                ->get();

            $byCourse = [];
            foreach ($rows as $row) {
                $cid = (int) $row->course_id;
                if (! isset($byCourse[$cid])) {
                    $byCourse[$cid] = (int) $row->institution_id;
                }
            }
            foreach ($byCourse as $courseId => $institutionId) {
                DB::table('courses')
                    ->where('id', $courseId)
                    ->whereNull('institution_id')
                    ->update(['institution_id' => $institutionId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'institution_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('institution_id');
            });
        }
    }
};
