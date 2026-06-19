<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Arkesel API integration for SMS and OTP.
 *
 * driver=log  — no HTTP calls; codes written to laravel.log (set ARKESEL_DRIVER=log).
 * driver=live — real Arkesel API (default).
 */
class ArkeselService
{
    private static function getApiKey(): string
    {
        $key = Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY, '');
        if (is_string($key) && trim($key) !== '') {
            return trim($key);
        }
        $key = config('services.arkesel.api_key');

        return is_string($key) ? trim($key) : '';
    }

    private static function baseUrl(): string
    {
        return rtrim((string) config('services.arkesel.base_url', 'https://sms.arkesel.com'), '/');
    }

    public static function driver(): string
    {
        return (string) config('services.arkesel.driver', 'live');
    }

    public static function usesLogDriver(): bool
    {
        return self::driver() === 'log';
    }

    /** Local dev: Arkesel often blocked on Mac/home networks — still allow OTP testing. */
    public static function allowLocalConnectionFallback(): bool
    {
        return (bool) config('services.arkesel.fallback_on_connection_error', false)
            && app()->environment('local');
    }

    private static function httpClient(): PendingRequest
    {
        $retries = max(0, (int) config('services.arkesel.retries', 0));

        $client = Http::connectTimeout((int) config('services.arkesel.connect_timeout', 5))
            ->timeout((int) config('services.arkesel.timeout', 10));

        if ($retries > 0) {
            $client = $client->retry($retries, 500, fn ($exception) => $exception instanceof ConnectionException);
        }

        return $client;
    }

    public static function hasApiKey(): bool
    {
        return self::usesLogDriver() || self::getApiKey() !== '';
    }

    /**
     * @return array{success: false, message: string, detail?: string, connection_error?: true}
     */
    private static function connectionFailure(string $context, ConnectionException $e, bool $includeDetail): array
    {
        Log::warning('Arkesel '.$context.' connection failed', ['message' => $e->getMessage()]);

        $hint = self::usesLogDriver()
            ? 'SMS log mode is on (ARKESEL_DRIVER=log). Set ARKESEL_DRIVER=live to send real SMS.'
            : 'Could not reach the SMS provider. Check server DNS/firewall for '.self::baseUrl().' on port 443.';

        $result = [
            'success' => false,
            'message' => $includeDetail ? $hint : 'Could not reach the SMS provider. Please try again.',
            'connection_error' => true,
        ];
        if ($includeDetail) {
            $result['detail'] = $e->getMessage();
        }

        return $result;
    }

    public static function checkBalance(bool $includeConnectionDetail = false): array
    {
        if (self::usesLogDriver()) {
            return [
                'success' => true,
                'message' => 'Log driver active — no live Arkesel balance (local development).',
                'sms_balance' => '999',
                'main_balance' => '0',
                'log_driver' => true,
            ];
        }

        $apiKey = self::getApiKey();
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Arkesel API key is not configured.'];
        }

        try {
            $response = self::httpClient()
                ->withHeaders([
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get(self::baseUrl().'/api/v2/clients/balance-details');
        } catch (ConnectionException $e) {
            return self::connectionFailure('balance check', $e, $includeConnectionDetail);
        }

        return self::parseV2BalanceResponse($response);
    }

    /**
     * @return array{success: bool, message: string, sms_balance?: string|null, main_balance?: string|null}
     */
    private static function parseV2BalanceResponse(Response $response): array
    {
        $body = $response->json();
        $status = $response->status();
        if ($status === 200 && isset($body['status']) && $body['status'] === 'success' && isset($body['data'])) {
            $data = $body['data'];

            return [
                'success' => true,
                'message' => 'Balance retrieved.',
                'sms_balance' => isset($data['sms_balance']) ? (string) $data['sms_balance'] : null,
                'main_balance' => isset($data['main_balance']) ? (string) $data['main_balance'] : null,
            ];
        }

        $errorMessage = $body['message'] ?? $body['error'] ?? 'Unknown error';
        if ($status === 401) {
            $errorMessage = 'Invalid API key.';
        }

        return ['success' => false, 'message' => is_string($errorMessage) ? $errorMessage : json_encode($errorMessage)];
    }

    public static function sendSms(string $recipient, string $message, ?string $senderId = null, bool $includeConnectionDetail = false): array
    {
        $recipient = preg_replace('/\D/', '', $recipient);
        if ($recipient === '') {
            return ['success' => false, 'message' => 'Invalid recipient number.'];
        }
        if (strlen($recipient) < 10) {
            return ['success' => false, 'message' => 'Recipient number too short (use international format, e.g. 233XXXXXXXXX).'];
        }

        if (self::usesLogDriver()) {
            Log::info('[QuizSnap SMS — log driver]', [
                'recipient' => $recipient,
                'message' => $message,
            ]);

            return [
                'success' => true,
                'message' => 'SMS logged locally. Open storage/logs/laravel.log and search for "QuizSnap SMS" to see the code.',
                'log_driver' => true,
            ];
        }

        $apiKey = self::getApiKey();
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Arkesel API key is not configured.'];
        }

        $sender = $senderId ?? Setting::getValue(Setting::KEY_OTP_ARKESEL_SENDER_ID, 'QuizSnap');
        $sender = substr(trim((string) $sender), 0, 11);

        try {
            $response = self::httpClient()
                ->withHeaders([
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::baseUrl().'/api/v2/sms/send', [
                    'sender' => $sender,
                    'recipients' => [$recipient],
                    'message' => $message,
                ]);
        } catch (ConnectionException $e) {
            return self::connectionFailure('SMS', $e, $includeConnectionDetail);
        }

        return self::parseV2SendResponse($response, $recipient);
    }

    /**
     * @return array{success: bool, message: string, connection_fallback?: bool}
     */
    private static function parseV2SendResponse(Response $response, string $recipient): array
    {
        $body = $response->json();
        $status = $response->status();

        Log::info('Arkesel SMS response', ['status' => $status, 'body' => $body]);

        if ($status === 200 && isset($body['status']) && $body['status'] === 'success') {
            $data = $body['data'] ?? [];
            foreach (is_array($data) ? $data : [] as $item) {
                if (isset($item['invalid numbers']) && is_array($item['invalid numbers']) && in_array($recipient, $item['invalid numbers'], true)) {
                    Log::warning('Arkesel SMS recipient invalid', ['recipient' => $recipient]);

                    return ['success' => false, 'message' => 'Phone number not valid for SMS delivery. Use international format (e.g. 233544919953 for Ghana).'];
                }
            }

            return ['success' => true, 'message' => 'SMS sent successfully.'];
        }

        $errorMessage = $body['message'] ?? $body['error'] ?? 'Unknown error';
        if (is_array($errorMessage)) {
            $errorMessage = json_encode($errorMessage);
        }

        Log::warning('Arkesel SMS send failed', ['status' => $status, 'body' => $body]);

        if ($status === 401) {
            return ['success' => false, 'message' => 'SMS service is not configured correctly. Please try again later or contact your institution.'];
        }
        if ($status === 402 || $status === 403) {
            return ['success' => false, 'message' => 'SMS service is temporarily unavailable. Please try again later.'];
        }
        if ($status === 422) {
            return ['success' => false, 'message' => 'That phone number may not be valid for SMS. Use international format (e.g. 233XXXXXXXXX) and try again.'];
        }

        return ['success' => false, 'message' => 'We couldn\'t send the code. Please try again in a moment.'];
    }

    public static function sendTestOtp(string $recipient): array
    {
        $code = (string) random_int(100000, 999999);
        $message = 'Your QuizSnap OTP test code is: '.$code.'. Do not share.';
        $result = self::sendSms($recipient, $message, null, true);

        if ($result['success']) {
            $result['sms_delivered'] = ! ($result['log_driver'] ?? false);
            $result['message'] = ($result['log_driver'] ?? false)
                ? 'Test OTP logged locally. Search laravel.log for: '.$code
                : 'Test OTP sent successfully to '.$recipient.'.';

            return $result;
        }

        if (($result['connection_error'] ?? false) && self::allowLocalConnectionFallback()) {
            Log::warning('[QuizSnap SMS — Arkesel unreachable, local test code only]', [
                'recipient' => $recipient,
                'code' => $code,
            ]);

            return [
                'success' => true,
                'sms_delivered' => false,
                'test_code' => $code,
                'connection_error' => true,
                'message' => 'Arkesel is unreachable from this machine (SMS was not sent). Dev test code: '.$code
                    .'. Set ARKESEL_FALLBACK_ON_CONNECTION_ERROR=true only if you need offline OTP testing.',
            ];
        }

        return $result;
    }
}
