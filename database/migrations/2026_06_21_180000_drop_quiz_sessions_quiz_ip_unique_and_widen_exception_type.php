<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quiz_sessions')) {
            try {
                DB::statement('ALTER TABLE quiz_sessions DROP INDEX quiz_sessions_quiz_id_ip_address_unique');
            } catch (\Throwable) {
                try {
                    Schema::table('quiz_sessions', function (Blueprint $table) {
                        $table->dropUnique(['quiz_id', 'ip_address']);
                    });
                } catch (\Throwable) {
                    // Already removed or different index name on this deployment.
                }
            }
        }

        if (Schema::hasTable('system_errors') && Schema::hasColumn('system_errors', 'exception_type')) {
            Schema::table('system_errors', function (Blueprint $table) {
                $table->string('exception_type', 128)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quiz_sessions')) {
            Schema::table('quiz_sessions', function (Blueprint $table) {
                $table->unique(['quiz_id', 'ip_address']);
            });
        }

        if (Schema::hasTable('system_errors') && Schema::hasColumn('system_errors', 'exception_type')) {
            Schema::table('system_errors', function (Blueprint $table) {
                $table->string('exception_type', 32)->nullable()->change();
            });
        }
    }
};
