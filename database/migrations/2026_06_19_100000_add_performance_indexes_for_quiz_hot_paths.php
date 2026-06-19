<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for live proctor queries, session lookups, and violation aggregates.
     */
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->index(['quiz_id', 'student_index'], 'quiz_sessions_quiz_student_index_idx');
            $table->index('last_heartbeat_at', 'quiz_sessions_last_heartbeat_at_idx');
            $table->index(['ended_at', 'auto_submit_after'], 'quiz_sessions_ended_auto_submit_idx');
        });

        Schema::table('quiz_violations', function (Blueprint $table) {
            $table->index(['quiz_session_id', 'image_url'], 'quiz_violations_session_image_idx');
        });

        Schema::table('valid_indices', function (Blueprint $table) {
            $table->index('course_id', 'valid_indices_course_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropIndex('quiz_sessions_quiz_student_index_idx');
            $table->dropIndex('quiz_sessions_last_heartbeat_at_idx');
            $table->dropIndex('quiz_sessions_ended_auto_submit_idx');
        });

        Schema::table('quiz_violations', function (Blueprint $table) {
            $table->dropIndex('quiz_violations_session_image_idx');
        });

        Schema::table('valid_indices', function (Blueprint $table) {
            $table->dropIndex('valid_indices_course_id_idx');
        });
    }
};
