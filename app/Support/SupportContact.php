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
     * @param  array{
     *     index_number?: string,
     *     name?: string,
     *     issue?: string,
     *     description?: string,
     *     system_error?: string,
     *     page?: string,
     * }  $context
     */
    public static function whatsAppPrefillMessage(array $context = []): string
    {
        $appName = trim((string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap')));
        if ($appName === '') {
            $appName = 'QuizSnap';
        }

        $description = trim((string) ($context['description'] ?? $context['issue'] ?? ''));
        $systemError = trim((string) ($context['system_error'] ?? ''));
        $page = trim((string) ($context['page'] ?? ''));

        $lines = ["Hello {$appName} Support,", ''];

        if (! empty($context['name'])) {
            $lines[] = 'Name: '.$context['name'];
        }
        if (! empty($context['index_number'])) {
            $lines[] = 'Index: '.$context['index_number'];
        }
        if ($page !== '') {
            $lines[] = 'Page: '.$page;
        }

        if (! empty($context['name']) || ! empty($context['index_number']) || $page !== '') {
            $lines[] = '';
        }

        if ($systemError !== '') {
            $lines[] = 'System message: '.$systemError;
            $lines[] = '';
        }

        $lines[] = 'What I need help with:';
        $lines[] = $description !== '' ? $description : '(No description provided)';
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
     * JSON-safe config for client-side WhatsApp links and the support modal.
     *
     * @param  array{index_number?: string, name?: string, page?: string}  $context
     * @return array{number: string, appName: string, defaultContext: array<string, string>}
     */
    public static function clientConfig(array $context = []): array
    {
        $appName = trim((string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap')));

        return [
            'number' => self::whatsAppNumber(),
            'appName' => $appName !== '' ? $appName : 'QuizSnap',
            'defaultContext' => array_filter([
                'name' => isset($context['name']) ? trim((string) $context['name']) : null,
                'index_number' => isset($context['index_number']) ? trim((string) $context['index_number']) : null,
                'page' => isset($context['page']) ? trim((string) $context['page']) : null,
            ], fn ($v) => $v !== null && $v !== ''),
        ];
    }
}
