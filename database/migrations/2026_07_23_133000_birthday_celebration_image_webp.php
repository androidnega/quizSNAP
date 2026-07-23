<?php

use App\Models\Setting;
use App\Services\BirthdayCelebrationService;
use App\Services\PageCacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $current = trim((string) (Setting::getValue(Setting::KEY_BIRTHDAY_CELEBRATION_IMAGE) ?? ''));
        if ($current === '' || $current === BirthdayCelebrationService::LEGACY_DEFAULT_IMAGE_PATH) {
            Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_IMAGE, BirthdayCelebrationService::DEFAULT_IMAGE_PATH);
        }

        app(PageCacheService::class)->bumpVersion();
    }

    public function down(): void
    {
        // no-op
    }
};
