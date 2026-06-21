<?php

namespace App\Services;

use App\Models\Setting;

class EmailBrandingService
{
    /**
     * @return array{appName: string, institutionName: string, logoUrl: string, year: string, fromAddress: string, fromName: string}
     */
    public static function context(): array
    {
        return [
            'appName' => (string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap')),
            'institutionName' => (string) Setting::getValue(Setting::KEY_INSTITUTION_NAME, ''),
            'logoUrl' => (string) Setting::getValue(Setting::KEY_INSTITUTION_LOGO, ''),
            'year' => (string) date('Y'),
            'fromAddress' => (string) Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, config('mail.from.address', '')),
            'fromName' => (string) Setting::getValue(Setting::KEY_MAIL_FROM_NAME, config('mail.from.name', 'QuizSnap')),
        ];
    }
}
