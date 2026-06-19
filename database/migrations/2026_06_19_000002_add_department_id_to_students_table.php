<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || Schema::hasColumn('students', 'department_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('student_name')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'department_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
