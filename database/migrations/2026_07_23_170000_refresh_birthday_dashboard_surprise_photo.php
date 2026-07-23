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

        Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_IMAGE, BirthdayCelebrationService::DEFAULT_IMAGE_PATH);
        Cache::forget('setting:'.Setting::KEY_BIRTHDAY_CELEBRATION_IMAGE);
        app(PageCacheService::class)->bumpVersion();
    }

    public function down(): void
    {
        // Keep current image path on rollback.
    }
};
