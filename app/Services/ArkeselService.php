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
 * Docs: https://developers.arkesel.com/
 * API key from https://sms.arkesel.com/dashboard (SMS API section).
 */
class ArkeselService
{
    /** Get API key: database (Settings) first, then .env (ARKESEL_API_KEY / OTP_ARKESEL_API_KEY) for cPanel/live. */
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

    private static function httpClient(): PendingRequest
    {
        $retries = max(1, (int) config('services.arkesel.retries', 3));

        return Http::connectTimeout((int) config('services.arkesel.connect_timeout', 15))
            ->timeout((int) config('services.arkesel.timeout', 30))
            ->retry($retries, 1000, function ($exception) {
                return $exception instanceof ConnectionException;
            });
    }

    public static function hasApiKey(): bool
    {
        return self::getApiKey() !== '';
    }

    /**
     * @return array{success: false, message: string, detail?: string, connection_error?: true}
     */
    private static function connectionFailure(string $context, ConnectionException $e, bool $includeDetail): array
    {
        Log::warning('Arkesel ' . $context . ' connection failed', ['message' => $e->getMessage()]);

        $result = [
            'success' => false,
            'message' => $includeDetail
                ? 'Could not reach Arkesel. Check that this server can connect to ' . self::baseUrl() . ' on port 443, then try again.'
                : 'Could not reach Arkesel. Please try again.',
            'connection_error' => true,
        ];
        if ($includeDetail) {
            $result['detail'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Check Arkesel SMS and main balance. Returns ['success' => bool, 'message' => string, 'sms_balance' => string|null, 'main_balance' => string|null].
     */
    public static function checkBalance(bool $includeConnectionDetail = false): array
    {
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
                ->get(self::baseUrl() . '/api/v2/clients/balance-details');
        } catch (ConnectionException $e) {
            return self::checkBalanceV1($apiKey, $e, $includeConnectionDetail);
        }

        return self::parseV2BalanceResponse($response);
    }

    /**
     * @return array{success: bool, message: string, sms_balance?: string|null, main_balance?: string|null, detail?: string, connection_error?: true}
     */
    private static function checkBalanceV1(string $apiKey, ?ConnectionException $v2Error, bool $includeConnectionDetail): array
    {
        try {
            $response = self::httpClient()->get(self::baseUrl() . '/sms/api', [
                'action' => 'check-balance',
                'api_key' => $apiKey,
                'response' => 'json',
            ]);
        } catch (ConnectionException $e) {
            return self::connectionFailure('balance check', $e, $includeConnectionDetail);
        }

        $body = $response->json();
        if ($response->successful() && is_array($body) && ($body['code'] ?? '') === 'ok') {
            return [
                'success' => true,
                'message' => 'Balance retrieved.',
                'sms_balance' => isset($body['balance']) ? (string) $body['balance'] : null,
                'main_balance' => isset($body['main_balance']) ? (string) $body['main_balance'] : null,
            ];
        }

        if ($v2Error !== null) {
            return self::connectionFailure('balance check', $v2Error, $includeConnectionDetail);
        }

        $errorMessage = $body['message'] ?? $body['error'] ?? 'Unknown error';
        if ($response->status() === 401) {
            $errorMessage = 'Invalid API key.';
        }

        return ['success' => false, 'message' => is_string($errorMessage) ? $errorMessage : json_encode($errorMessage)];
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

    /**
     * Send SMS via Arkesel API v2 (v1 fallback on connection failure).
     * Recipient: international format e.g. 233544919953 (Ghana).
     */
    public static function sendSms(string $recipient, string $message, ?string $senderId = null, bool $includeConnectionDetail = false): array
    {
        $apiKey = self::getApiKey();
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Arkesel API key is not configured.'];
        }

        $sender = $senderId ?? Setting::getValue(Setting::KEY_OTP_ARKESEL_SENDER_ID, 'QuizSnap');
        $sender = substr(trim($sender), 0, 11);

        $recipient = preg_replace('/\D/', '', $recipient);
        if ($recipient === '') {
            return ['success' => false, 'message' => 'Invalid recipient number.'];
        }
        if (strlen($recipient) < 10) {
            return ['success' => false, 'message' => 'Recipient number too short (use international format, e.g. 233XXXXXXXXX).'];
        }

        try {
            $response = self::httpClient()
                ->withHeaders([
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::baseUrl() . '/api/v2/sms/send', [
                    'sender' => $sender,
                    'recipients' => [$recipient],
                    'message' => $message,
                ]);
        } catch (ConnectionException $e) {
            return self::sendSmsV1($apiKey, $recipient, $sender, $message, $e, $includeConnectionDetail);
        }

        $parsed = self::parseV2SendResponse($response, $recipient);
        if ($parsed['success'] || ! ($parsed['connection_fallback'] ?? false)) {
            unset($parsed['connection_fallback']);

            return $parsed;
        }

        return self::sendSmsV1($apiKey, $recipient, $sender, $message, null, $includeConnectionDetail);
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
        if ($status === 0 || $status >= 500) {
            return ['success' => false, 'message' => 'We couldn\'t send the code. Please try again in a moment.', 'connection_fallback' => true];
        }

        return ['success' => false, 'message' => 'We couldn\'t send the code. Please try again in a moment.'];
    }

    /**
     * @return array{success: bool, message: string, detail?: string, connection_error?: true}
     */
    private static function sendSmsV1(
        string $apiKey,
        string $recipient,
        string $sender,
        string $message,
        ?ConnectionException $v2Error,
        bool $includeConnectionDetail
    ): array {
        try {
            $response = self::httpClient()->asForm()->post(self::baseUrl() . '/sms/api', [
                'action' => 'send-sms',
                'api_key' => $apiKey,
                'to' => $recipient,
                'from' => $sender,
                'sms' => $message,
                'response' => 'json',
            ]);
        } catch (ConnectionException $e) {
            return self::connectionFailure('SMS', $e, $includeConnectionDetail);
        }

        $body = $response->json();
        Log::info('Arkesel SMS v1 response', ['status' => $response->status(), 'body' => $body]);

        if ($response->successful() && is_array($body) && ($body['code'] ?? '') === 'ok') {
            return ['success' => true, 'message' => 'SMS sent successfully.'];
        }

        if ($v2Error !== null) {
            return self::connectionFailure('SMS', $v2Error, $includeConnectionDetail);
        }

        $errorMessage = $body['message'] ?? $body['error'] ?? 'Unknown error';
        if ($response->status() === 401) {
            $errorMessage = 'SMS service is not configured correctly. Please try again later or contact your institution.';
        }

        return ['success' => false, 'message' => is_string($errorMessage) ? $errorMessage : 'We couldn\'t send the code. Please try again in a moment.'];
    }

    /**
     * Send a test OTP (6-digit code) via SMS. Used for testing OTP delivery from Settings.
     */
    public static function sendTestOtp(string $recipient): array
    {
        $code = (string) random_int(100000, 999999);
        $message = 'Your QuizSnap OTP test code is: ' . $code . '. Do not share.';
        $result = self::sendSms($recipient, $message, null, true);
        if ($result['success']) {
            $result['message'] = 'Test OTP sent successfully to ' . $recipient . '.';
        }
        return $result;
    }
}
