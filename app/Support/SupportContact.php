<?php

namespace App\Support;

use App\Models\Setting;

/**
 * QuizSnap support phone / WhatsApp links and prefill messages.
 */
final class SupportContact
{
    public const WHATSAPP_E164 = '233541069241';

    public const CALL_E164 = '+233257940791';

    public static function whatsAppNumber(): string
    {
        return self::WHATSAPP_E164;
    }

    public static function callNumber(): string
    {
        return self::CALL_E164;
    }

    /**
     * @param  array{index_number?: string, name?: string, issue?: string}  $context
     */
    public static function whatsAppPrefillMessage(array $context = []): string
    {
        $appName = trim((string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap')));
        if ($appName === '') {
            $appName = 'QuizSnap';
        }

        $lines = ["Hello {$appName} Support,", ''];

        if (! empty($context['name'])) {
            $lines[] = 'Name: '.$context['name'];
        }
        if (! empty($context['index_number'])) {
            $lines[] = 'Index: '.$context['index_number'];
        }
        if (! empty($context['issue'])) {
            $lines[] = 'Issue: '.$context['issue'];
        }

        if (! empty($context['name']) || ! empty($context['index_number']) || ! empty($context['issue'])) {
            $lines[] = '';
        }

        $lines[] = 'I need assistance with:';
        $lines[] = '';
        $lines[] = '[Please describe your issue here]';
        $lines[] = '';
        $lines[] = 'Thank you.';

        return implode("\n", $lines);
    }

    /**
     * @param  array{index_number?: string, name?: string, issue?: string}  $context
     */
    public static function whatsAppUrl(array $context = []): string
    {
        return 'https://wa.me/'.self::whatsAppNumber().'?text='.rawurlencode(self::whatsAppPrefillMessage($context));
    }

    /**
     * JSON-safe template for client-side WhatsApp links (login errors, etc.).
     *
     * @return array{number: string, appName: string}
     */
    public static function clientConfig(): array
    {
        $appName = trim((string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap')));

        return [
            'number' => self::whatsAppNumber(),
            'appName' => $appName !== '' ? $appName : 'QuizSnap',
        ];
    }
}
