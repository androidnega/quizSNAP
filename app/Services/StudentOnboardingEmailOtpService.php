<?php

namespace App\Services;

use App\Jobs\SendStudentOnboardingOtpEmail;
use App\Models\Otp;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StudentOnboardingEmailOtpService
{
    public const EXPIRES_MINUTES = Otp::ONBOARDING_EMAIL_OTP_MINUTES;

    public static function isEnabled(?array $settings = null): bool
    {
        $enabled = $settings !== null
            ? (($settings[Setting::KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED] ?? '1') === '1')
            : (Setting::getValue(Setting::KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED, '1') === '1');

        return $enabled && self::isMailConfigured($settings);
    }

    public static function isMailConfigured(?array $settings = null): bool
    {
        return MailConfigService::isConfigured($settings);
    }

    /** Only while phone is not yet verified (first-time setup). */
    public static function isEligible(Student $student): bool
    {
        return ! $student->hasVerifiedPhone();
    }

    public static function pendingPhoneCacheKey(string $indexHash): string
    {
        return 'student_onboarding_pending_phone:'.$indexHash;
    }

    public static function smsAttemptsCacheKey(string $indexHash): string
    {
        return 'student_onboarding_sms_attempts:'.$indexHash;
    }

    public static function otpVerifyFailuresCacheKey(string $indexHash): string
    {
        return 'student_onboarding_otp_failures:'.$indexHash;
    }

    public static function stashPendingPhone(string $indexHash, string $phone): void
    {
        Cache::put(self::pendingPhoneCacheKey($indexHash), $phone, now()->addMinutes(30));
    }

    public static function pullPendingPhone(string $indexHash): ?string
    {
        $phone = Cache::pull(self::pendingPhoneCacheKey($indexHash));

        return $phone ? (Student::normalizePhoneForStorage($phone) ?? $phone) : null;
    }

    public static function peekPendingPhone(string $indexHash): ?string
    {
        $phone = Cache::get(self::pendingPhoneCacheKey($indexHash));

        return $phone ? (Student::normalizePhoneForStorage($phone) ?? $phone) : null;
    }

    public static function recordSmsAttempt(string $indexHash): int
    {
        $key = self::smsAttemptsCacheKey($indexHash);
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addHours(2));

        return $count;
    }

    public static function smsAttemptCount(string $indexHash): int
    {
        return (int) Cache::get(self::smsAttemptsCacheKey($indexHash), 0);
    }

    public static function recordOtpVerifyFailure(string $indexHash): int
    {
        $key = self::otpVerifyFailuresCacheKey($indexHash);
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addHours(2));

        return $count;
    }

    public static function clearOnboardingTracking(string $indexHash): void
    {
        Cache::forget(self::smsAttemptsCacheKey($indexHash));
        Cache::forget(self::otpVerifyFailuresCacheKey($indexHash));
        Cache::forget(self::pendingPhoneCacheKey($indexHash));
    }

    /**
     * @return array<string, mixed>
     */
    public static function emailFallbackMeta(Student $student, string $indexHash): array
    {
        if (! self::isEligible($student) || ! self::isEnabled()) {
            return [
                'email_fallback_available' => false,
                'show_email_fallback' => false,
            ];
        }

        $smsAttempts = self::smsAttemptCount($indexHash);
        $failures = (int) Cache::get(self::otpVerifyFailuresCacheKey($indexHash), 0);
        $promote = $smsAttempts >= 2 || $failures >= 1;

        return [
            'email_fallback_available' => true,
            'show_email_fallback' => $promote,
            'prefill_email' => $student->email ?: null,
        ];
    }

    public static function send(Student $student, string $indexHash, string $email, ?Request $request = null): JsonResponse
    {
        if (! self::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification is not available. Ask your examiner for help or try SMS again.',
            ], 422);
        }

        if (! self::isEligible($student)) {
            return response()->json([
                'success' => false,
                'message' => 'Email codes are only available during first-time account setup.',
            ], 422);
        }

        $email = strtolower(trim($email));
        $other = Student::where('email', $email)->where('id', '!=', $student->id)->first();
        if ($other) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already registered to another student account.',
            ], 422);
        }

        $resendKey = 'email_otp_resend:'.$indexHash;
        if (Cache::has($resendKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait about a minute before requesting another email code.',
            ], 422);
        }

        $phone = self::peekPendingPhone($indexHash);
        if (! $phone && $student->hasPhone()) {
            $phone = Student::normalizePhoneForStorage($student->phone_contact) ?? $student->phone_contact;
        }
        if (! $phone || strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Go back and enter your phone number first, then try email verification.',
            ], 422);
        }

        $student->email = $email;
        $student->save();

        $code = (string) random_int(100000, 999999);
        Otp::deleteOnboardingEmailOtpsForIndex($indexHash);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_ONBOARDING_EMAIL,
            'code' => $code,
            'phone' => $phone,
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);

        SendStudentOnboardingOtpEmail::dispatch($student->id, $email, $code, self::EXPIRES_MINUTES);

        Cache::put($resendKey, 1, now()->addSeconds(Otp::RESEND_COOLDOWN_SECONDS));
        StudentAuthAuditLogger::log('onboarding_email_otp_sent', $student, $indexHash, $request, [
            'email' => $email,
        ]);

        return response()->json(array_merge([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'We sent a 6-digit code to '.$email.'. It expires in '.self::EXPIRES_MINUTES.' minutes.',
            'has_name' => ! empty($student->student_name),
            'can_resend' => true,
            'otp_channel' => 'email',
            'expires_minutes' => self::EXPIRES_MINUTES,
            'days_remaining' => null,
            'otp_never_expires' => false,
        ], self::emailFallbackMeta($student, $indexHash)));
    }
}
