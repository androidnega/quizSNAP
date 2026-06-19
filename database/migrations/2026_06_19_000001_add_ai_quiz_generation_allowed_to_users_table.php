<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ai_quiz_generation_allowed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('ai_quiz_generation_allowed')->default(true)->after('ai_quiz_tokens_reset_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'ai_quiz_generation_allowed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('ai_quiz_generation_allowed');
            });
        }
    }
};
