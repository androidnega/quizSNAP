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

        return $key !== '' && ! str_contains($key, 'CHANGE_ME');
    }

    /** @return array{key: string, host: string, port: int, scheme: string}|null */
    public static function clientConfig(): ?array
    {
        if (! self::isEnabled()) {
            return null;
        }

        $host = (string) config('broadcasting.connections.reverb.options.host', '');
        if ($host === '' || str_contains($host, 'example.com')) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (is_string($appHost) && $appHost !== '') {
                $host = $appHost;
            }
        }

        if ($host === '') {
            return null;
        }

        return [
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'host' => $host,
            'port' => (int) config('broadcasting.connections.reverb.options.port', 443),
            'scheme' => (string) (config('broadcasting.connections.reverb.options.scheme') ?? 'https'),
        ];
    }
}
