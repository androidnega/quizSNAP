<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('question_pools', 'type')) {
            Schema::table('question_pools', function (Blueprint $table) {
                $table->string('type')->default('mcq')->after('question_text');
            });
        }

        if (! Schema::hasColumn('quizzes', 'question_type_counts')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->json('question_type_counts')->nullable()->after('number_of_questions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('question_pools', 'type')) {
            Schema::table('question_pools', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        if (Schema::hasColumn('quizzes', 'question_type_counts')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('question_type_counts');
            });
        }
    }
};
