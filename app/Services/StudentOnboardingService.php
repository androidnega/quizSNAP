<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks first-time setup after phone OTP (password → name → email → dashboard).
 */
class StudentOnboardingService
{
    public const CACHE_MINUTES = 30;

    public static function cacheKey(string $indexHash): string
    {
        return 'student_onboarding_setup:'.$indexHash;
    }

    public static function begin(string $indexHash): void
    {
        Cache::put(self::cacheKey($indexHash), ['started_at' => now()->toIso8601String()], now()->addMinutes(self::CACHE_MINUTES));
    }

    public static function isInProgress(string $indexHash): bool
    {
        return Cache::has(self::cacheKey($indexHash));
    }

    public static function clear(string $indexHash): void
    {
        Cache::forget(self::cacheKey($indexHash));
    }

    public static function touch(string $indexHash): void
    {
        if (self::isInProgress($indexHash)) {
            Cache::put(self::cacheKey($indexHash), ['started_at' => now()->toIso8601String()], now()->addMinutes(self::CACHE_MINUTES));
        }
    }

    /**
     * Remaining onboarding steps after phone is verified.
     *
     * @return list<string> setup_password|setup_name|setup_email
     */
    public static function remainingSteps(Student $student): array
    {
        $steps = [];
        if (Student::isPasswordLoginEnabled() && ! $student->hasPassword()) {
            $steps[] = 'setup_password';
        }
        if (! self::hasDisplayName($student)) {
            $steps[] = 'setup_name';
        }
        if ($student->needsEmailCollection()) {
            $steps[] = 'setup_email';
        }

        return $steps;
    }

    public static function firstStep(Student $student): ?string
    {
        return self::remainingSteps($student)[0] ?? null;
    }

    public static function hasDisplayName(Student $student): bool
    {
        $name = (string) ($student->student_name ?? '');

        return trim($name) !== '';
    }

    /**
     * @return array{step: string, message: string}|null
     */
    public static function stepPayload(Student $student, string $step): ?array
    {
        return match ($step) {
            'setup_password' => [
                'step' => 'setup_password',
                'message' => 'Phone verified. Create a password for your account (min ' . Student::PASSWORD_MIN_LENGTH . ' characters).',
            ],
            'setup_name' => [
                'step' => 'setup_name',
                'message' => 'What name should we show on your account?',
            ],
            'setup_email' => [
                'step' => 'setup_email',
                'message' => 'Enter your email for account recovery and notifications.',
            ],
            default => null,
        };
    }

    public static function nextStepResponse(Student $student): ?array
    {
        $step = self::firstStep($student);
        if (! $step) {
            return null;
        }

        return self::stepPayload($student, $step);
    }
}
