<?php

namespace App\Services;

/**
 * Client-side Reverb (WebSocket) settings for the browser.
 * Skips loading when env still has template placeholders.
 */
class ReverbClientConfig
{
    public static function isEnabled(): bool
    {
        if (config('broadcasting.default') !== 'reverb') {
            return false;
        }

        if (! config('broadcasting.connections.reverb.app_id')) {
            return false;
        }

        $key = (string) config('broadcasting.connections.reverb.key', '');
        if ($key === '') {
            return false;
        }

        return ! self::isPlaceholder($key);
    }

    public static function isPlaceholder(string $value): bool
    {
        $lower = strtolower(trim($value));

        if ($lower === '') {
            return true;
        }

        $needles = ['change_me', 'change-me', 'your-domain', 'example.com', 'replace_me', 'replace-me'];
        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{key: string, host: string, port: int, scheme: string}|null */
    public static function clientConfig(): ?array
    {
        if (! self::isEnabled()) {
            return null;
        }

        $host = (string) config('broadcasting.connections.reverb.options.host', '');
        if ($host === '' || self::isPlaceholder($host)) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (is_string($appHost) && $appHost !== '' && ! self::isPlaceholder($appHost)) {
                $host = $appHost;
            }
        }

        if ($host === '' || self::isPlaceholder($host)) {
            return null;
        }

        $scheme = (string) (config('broadcasting.connections.reverb.options.scheme') ?? 'https');
        $port = (int) config('broadcasting.connections.reverb.options.port', $scheme === 'https' ? 443 : 8080);

        if ($scheme === 'http' && $port === 443) {
            $port = 8080;
        }

        return [
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'host' => $host,
            'port' => $port,
            'scheme' => $scheme,
        ];
    }
}
