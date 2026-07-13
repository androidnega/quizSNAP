<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'password_change_count')) {
                $table->unsignedInteger('password_change_count')->default(0)->after('password');
            }
            if (! Schema::hasColumn('students', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password_change_count');
            }
        });

        if (Schema::hasTable('auth_audit_logs') && Schema::hasColumn('students', 'password_change_count')) {
            $events = ['password_reset_completed', 'staff_password_reset'];
            $rows = DB::table('auth_audit_logs')
                ->select('actor_id', DB::raw('COUNT(*) as total'), DB::raw('MAX(created_at) as last_at'))
                ->where('actor_type', 'student')
                ->whereIn('event', $events)
                ->whereNotNull('actor_id')
                ->groupBy('actor_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('students')
                    ->where('id', $row->actor_id)
                    ->update([
                        'password_change_count' => (int) $row->total,
                        'password_changed_at' => $row->last_at,
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }
            if (Schema::hasColumn('students', 'password_change_count')) {
                $table->dropColumn('password_change_count');
            }
        });
    }
};
