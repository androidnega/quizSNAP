<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\Monitoring\AuditTrailService;

class SettingAuditObserver
{
    public function updated(Setting $setting): void
    {
        if (! $setting->wasChanged()) {
            return;
        }

        app(AuditTrailService::class)->log(
            'Settings Modified',
            Setting::class,
            null,
            ['key' => $setting->getKey(), 'value' => $setting->getOriginal('value') ?? null],
            ['key' => $setting->getKey(), 'value' => $setting->value ?? null]
        );
    }
}
