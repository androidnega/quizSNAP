<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $rows = [
            Setting::KEY_STUDENT_DASHBOARD_BANNER_ENABLED => '1',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_MODE => 'image',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_IMAGES => json_encode([Setting::STUDENT_DASHBOARD_DEFAULT_BANNER_PATH]),
        ];

        foreach ($rows as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
            Cache::forget('setting:' . $key);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->where('key', Setting::KEY_STUDENT_DASHBOARD_BANNER_ENABLED)->update([
            'value' => '0',
            'updated_at' => now(),
        ]);
        Cache::forget('setting:' . Setting::KEY_STUDENT_DASHBOARD_BANNER_ENABLED);
    }
};
