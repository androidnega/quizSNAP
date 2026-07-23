<?php

use App\Models\Setting;
use App\Services\BirthdayCelebrationService;
use App\Services\PageCacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $service = app(BirthdayCelebrationService::class);
        $defaults = $service->defaults();
        $defaults[Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED] = '1';
        $defaults[Setting::KEY_BIRTHDAY_CELEBRATION_START] = now()->format('Y-m-d');
        $defaults[Setting::KEY_BIRTHDAY_CELEBRATION_END] = now()->format('Y-m-d');

        foreach ($defaults as $key => $value) {
            if (Setting::getValue($key) !== null) {
                continue;
            }
            Setting::setValue($key, $value);
            Cache::forget('setting:' . $key);
        }

        app(PageCacheService::class)->bumpVersion();
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED, '0');
        Cache::forget('setting:' . Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED);
        app(PageCacheService::class)->bumpVersion();
    }
};
