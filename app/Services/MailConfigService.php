<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    /**
     * Normalize SMTP host: strip accidental schemes/ports and convert email addresses to mail.{domain}.
     */
    public static function normalizeSmtpHost(?string $host): ?string
    {
        if ($host === null) {
            return null;
        }

        $host = trim($host);
        if ($host === '') {
            return '';
        }

        $host = preg_replace('#^(ssl://|tls://|smtp://)#i', '', $host) ?? $host;
        $host = preg_replace('#:\d{1,5}$#', '', $host) ?? $host;

        if (str_contains($host, '@')) {
            $domain = trim(explode('@', $host, 2)[1] ?? '');
            if ($domain !== '') {
                return 'mail.' . ltrim($domain, '.');
            }
        }

        return $host;
    }

    public static function applyFromSettings(): void
    {
        $mailer = Setting::getValue(Setting::KEY_MAIL_MAILER, config('mail.default'));
        $rawHost = Setting::getValue(Setting::KEY_MAIL_HOST, 'mail.quizsnap.online');
        $host = self::normalizeSmtpHost($rawHost) ?? '';
        if ($host !== '' && $rawHost !== null && trim((string) $rawHost) !== '' && $host !== trim((string) $rawHost)) {
            Setting::setValue(Setting::KEY_MAIL_HOST, $host);
        }
        $port = (int) Setting::getValue(Setting::KEY_MAIL_PORT, '465');
        $username = Setting::getValue(Setting::KEY_MAIL_USERNAME);
        $password = Setting::getValue(Setting::KEY_MAIL_PASSWORD);
        $encryption = Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, 'ssl');
        $fromAddress = Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, 'deveopers@quizsnap.online');
        $fromName = Setting::getValue(Setting::KEY_MAIL_FROM_NAME, 'QuizSnap');

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress ?: 'noreply@quizsnap.local');
        Config::set('mail.from.name', $fromName ?: 'QuizSnap');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
    }

    /** Whether outbound email can be sent (Settings SMTP host + username). */
    public static function isConfigured(?array $settings = null): bool
    {
        if ($settings !== null) {
            return trim((string) ($settings[Setting::KEY_MAIL_HOST] ?? '')) !== ''
                && trim((string) ($settings[Setting::KEY_MAIL_USERNAME] ?? '')) !== '';
        }

        return trim((string) Setting::getValue(Setting::KEY_MAIL_HOST, '')) !== ''
            && trim((string) Setting::getValue(Setting::KEY_MAIL_USERNAME, '')) !== '';
    }
}
