<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

/**
 * Optional global student login codes (6 digits). Not tied to SMS rows; never expire.
 * Configured via Settings → OTP (comma-separated) or QUIZSNAP_UNIVERSAL_OTP_CODES in .env.
 * DB value overrides .env when non-empty. Accepted only after SMS delivery failed (fallback).
 */
final class StudentUniversalOtp
{
    private const FALLBACK_TTL_MINUTES = 120;

    public static function isConfigured(): bool
    {
        return count(self::normalizedCodes()) > 0;
    }

    public static function fallbackCacheKey(string $indexHash): string
    {
        return 'student_universal_otp_fallback:'.$indexHash;
    }

    public static function enableFallback(string $indexHash): void
    {
        if (! self::isConfigured()) {
            return;
        }
        Cache::put(self::fallbackCacheKey($indexHash), 1, now()->addMinutes(self::FALLBACK_TTL_MINUTES));
    }

    public static function clearFallback(string $indexHash): void
    {
        Cache::forget(self::fallbackCacheKey($indexHash));
    }

    public static function isFallbackEnabled(string $indexHash): bool
    {
        return self::isConfigured() && Cache::has(self::fallbackCacheKey($indexHash));
    }

    /**
     * @return array<string, mixed>
     */
    public static function fallbackMeta(Student $student, string $indexHash, bool $show = false): array
    {
        if (! self::isConfigured()) {
            return [
                'universal_fallback_available' => false,
                'show_universal_fallback' => false,
            ];
        }

        $enabled = self::isFallbackEnabled($indexHash);

        return [
            'universal_fallback_available' => true,
            'show_universal_fallback' => $show || $enabled,
            'universal_fallback_message' => 'We could not send SMS. Enter the institution login code from your examiner or institution.',
        ];
    }

    public static function normalizedCodes(): array
    {
        $raw = self::rawCommaSeparated();

        return self::parseRawToSixDigitCodes($raw);
    }

    public static function matches(string $sixDigitCode): bool
    {
        $sixDigitCode = preg_replace('/\D/', '', $sixDigitCode) ?? '';
        if (strlen($sixDigitCode) !== 6 || ! ctype_digit($sixDigitCode)) {
            return false;
        }

        return in_array($sixDigitCode, self::normalizedCodes(), true);
    }

    private static function rawCommaSeparated(): string
    {
        $db = Setting::getValue(Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES);
        if ($db !== null && trim((string) $db) !== '') {
            return trim((string) $db);
        }

        return trim((string) config('quizsnap.universal_student_otp_codes', ''));
    }

    /**
     * @return list<string> unique 6-digit codes
     */
    public static function parseRawToSixDigitCodes(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $d = preg_replace('/\D/', '', trim($part));
            if (strlen($d) === 6) {
                $out[] = $d;
            }
        }

        return array_values(array_unique($out));
    }
}
