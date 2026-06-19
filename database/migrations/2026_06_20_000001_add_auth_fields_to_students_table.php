<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'email')) {
                $table->string('email', 255)->nullable()->after('student_name');
                $table->unique('email');
            }
            if (! Schema::hasColumn('students', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (! Schema::hasColumn('students', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone_contact');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('students', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            if (Schema::hasColumn('students', 'phone_verified_at')) {
                $table->dropColumn('phone_verified_at');
            }
        });
    }
};
