<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for student dashboard session lists and published quiz lookups.
     */
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->index('student_index', 'quiz_sessions_student_index_idx');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->index(
                ['class_group_id', 'is_published', 'is_active'],
                'quizzes_class_group_published_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropIndex('quiz_sessions_student_index_idx');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex('quizzes_class_group_published_active_idx');
        });
    }
};
