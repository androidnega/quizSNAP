<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Correct Mid-Semester Exam session for BC/ITN/24/302 (quiz #56, session #2000).
     */
    public function up(): void
    {
        if (! Schema::hasTable('quiz_sessions') || ! Schema::hasTable('results')) {
            return;
        }

        $session = DB::table('quiz_sessions')->where('id', 2000)->first();
        if (! $session) {
            return;
        }

        if ((int) $session->quiz_id !== 56) {
            return;
        }

        if (strtoupper(trim((string) $session->student_index)) !== 'BC/ITN/24/302') {
            return;
        }

        $updated = DB::table('results')
            ->where('quiz_session_id', 2000)
            ->update([
                'correct_count' => 14,
                'total_questions' => 20,
                'score' => 70.00,
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            DB::table('quiz_sessions')->where('id', 2000)->update(['updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('results')) {
            return;
        }

        $session = DB::table('quiz_sessions')->where('id', 2000)->first();
        if (! $session || (int) $session->quiz_id !== 56) {
            return;
        }

        DB::table('results')
            ->where('quiz_session_id', 2000)
            ->update([
                'correct_count' => 2,
                'total_questions' => 20,
                'score' => 10.00,
                'updated_at' => now(),
            ]);
    }
};
