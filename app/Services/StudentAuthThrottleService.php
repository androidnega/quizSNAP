<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class StudentAuthThrottleService
{
    public const TYPE_OTP = 'otp';

    public const TYPE_PASSWORD = 'password';

    public static function maxAttempts(): int
    {
        $value = (int) Setting::getValue(Setting::KEY_STUDENT_OTP_MAX_ATTEMPTS, '5');

        return max(3, min(20, $value));
    }

    public static function lockoutMinutes(): int
    {
        $value = (int) Setting::getValue(Setting::KEY_STUDENT_OTP_LOCKOUT_MINUTES, '15');

        return max(5, min(120, $value));
    }

    public static function isLocked(string $type, string $indexHash): bool
    {
        return Cache::has(self::lockKey($type, $indexHash));
    }

    public static function lockoutRemainingSeconds(string $type, string $indexHash): int
    {
        $expiresAt = Cache::get(self::lockKey($type, $indexHash));
        if (! $expiresAt) {
            return 0;
        }

        return max(0, (int) $expiresAt - time());
    }

    public static function currentAttempts(string $type, string $indexHash): int
    {
        return (int) Cache::get(self::attemptKey($type, $indexHash), 0);
    }

    public static function remainingAttempts(string $type, string $indexHash): int
    {
        return max(0, self::maxAttempts() - self::currentAttempts($type, $indexHash));
    }

    public static function recordFailure(string $type, string $indexHash): int
    {
        $attemptKey = self::attemptKey($type, $indexHash);
        $attempts = self::currentAttempts($type, $indexHash) + 1;
        Cache::put($attemptKey, $attempts, now()->addMinutes(self::lockoutMinutes()));

        if ($attempts >= self::maxAttempts()) {
            Cache::put(
                self::lockKey($type, $indexHash),
                time() + (self::lockoutMinutes() * 60),
                now()->addMinutes(self::lockoutMinutes())
            );
        }

        return $attempts;
    }

    public static function clearFailures(string $type, string $indexHash): void
    {
        Cache::forget(self::attemptKey($type, $indexHash));
        Cache::forget(self::lockKey($type, $indexHash));
    }

    public static function lockoutMessage(string $type, string $indexHash): string
    {
        $seconds = self::lockoutRemainingSeconds($type, $indexHash);
        $minutes = max(1, (int) ceil($seconds / 60));

        return $type === self::TYPE_PASSWORD
            ? "Too many failed password attempts. Try again in {$minutes} minute(s) or use “Forgot password”."
            : "Too many failed code attempts. Try again in {$minutes} minute(s) or request a new code.";
    }

    public static function failureMessage(string $type, string $indexHash): string
    {
        if (self::isLocked($type, $indexHash)) {
            return self::lockoutMessage($type, $indexHash);
        }

        $remaining = self::remainingAttempts($type, $indexHash);

        return $type === self::TYPE_PASSWORD
            ? "Incorrect password. You have {$remaining} attempt(s) left before a temporary lockout."
            : "Incorrect code. You have {$remaining} attempt(s) left before a temporary lockout.";
    }

    private static function attemptKey(string $type, string $indexHash): string
    {
        return 'student_auth_attempts:'.$type.':'.$indexHash;
    }

    private static function lockKey(string $type, string $indexHash): string
    {
        return 'student_auth_lock:'.$type.':'.$indexHash;
    }
}
