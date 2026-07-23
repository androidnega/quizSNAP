<?php

use App\Models\Setting;
use App\Services\PageCacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep team-lead birthday celebration enabled with a sensible date window on deploy.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $today = now()->startOfDay();
        $end = $today->copy()->addDays(14);

        Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED, '1');
        Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_START, $today->format('Y-m-d'));
        Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_END, $end->format('Y-m-d'));

        foreach ([
            Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED,
            Setting::KEY_BIRTHDAY_CELEBRATION_START,
            Setting::KEY_BIRTHDAY_CELEBRATION_END,
        ] as $key) {
            Cache::forget('setting:'.$key);
        }

        app(PageCacheService::class)->bumpVersion();
    }

    public function down(): void
    {
        // No-op: do not disable a live celebration on rollback.
    }
};
