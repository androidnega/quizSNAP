<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('institution_id')->nullable()->after('issue_category');
            $table->string('student_phone', 32)->nullable()->after('student_name');
            $table->string('student_email', 255)->nullable()->after('student_phone');

            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('support_sessions', function (Blueprint $table) {
            $table->dropIndex(['institution_id', 'status']);
            $table->dropColumn(['institution_id', 'student_phone', 'student_email']);
        });
    }
};
