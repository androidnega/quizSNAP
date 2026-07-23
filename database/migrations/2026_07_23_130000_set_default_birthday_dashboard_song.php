<?php

use App\Models\Setting;
use App\Services\BirthdayCelebrationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $current = Setting::getValue(Setting::KEY_BIRTHDAY_CELEBRATION_SONG_URL);
        if ($current === null || trim((string) $current) === '') {
            Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_SONG_URL, BirthdayCelebrationService::DEFAULT_SONG_PATH);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
