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
            $setting->getKey(),
            ['value' => $setting->getOriginal('value') ?? null],
            ['value' => $setting->value ?? null]
        );
    }
}
