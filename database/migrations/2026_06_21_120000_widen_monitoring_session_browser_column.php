<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('monitoring_user_sessions') && Schema::hasColumn('monitoring_user_sessions', 'browser')) {
            DB::statement('ALTER TABLE monitoring_user_sessions MODIFY browser VARCHAR(512) NULL');
        }

        if (Schema::hasTable('system_error_occurrences') && Schema::hasColumn('system_error_occurrences', 'browser')) {
            DB::statement('ALTER TABLE system_error_occurrences MODIFY browser VARCHAR(512) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('monitoring_user_sessions') && Schema::hasColumn('monitoring_user_sessions', 'browser')) {
            DB::statement('ALTER TABLE monitoring_user_sessions MODIFY browser VARCHAR(128) NULL');
        }

        if (Schema::hasTable('system_error_occurrences') && Schema::hasColumn('system_error_occurrences', 'browser')) {
            DB::statement('ALTER TABLE system_error_occurrences MODIFY browser VARCHAR(128) NULL');
        }
    }
};
