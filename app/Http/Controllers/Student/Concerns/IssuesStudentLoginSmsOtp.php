<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Otp;
use App\Models\Student;
use App\Services\ArkeselService;
use App\Services\StudentOnboardingEmailOtpService;
use App\Services\StudentUniversalOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

trait IssuesStudentLoginSmsOtp
{
    /**
     * Issue or reuse student_login SMS OTP. $sendToPhone: use when number is not yet saved on the student row (send-otp step).
     */
    protected function jsonAfterIssuingOrReusingSmsOtp(Student $student, string $indexHash, ?\App\Models\User $smsOwner, ?string $sendToPhone): JsonResponse
    {
        $destination = $sendToPhone ?? $student->phone_contact;
        if (! $destination || strlen((string) $destination) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'No valid phone number on file.',
            ], 422);
        }

        $lastOtp = Otp::latestStudentLoginForIndex($indexHash);

        if ($lastOtp && ! $lastOtp->isExpired()
            && $lastOtp->created_at
            && $lastOtp->created_at->gt(now()->subMinutes(Otp::STUDENT_LOGIN_SMS_COOLDOWN_MINUTES))) {
            return $this->jsonReuseExistingSmsOtp($student, $indexHash, $lastOtp);
        }

        $code = (string) random_int(100000, 999999);
        Otp::deleteStudentLoginOtpsForIndex($indexHash);
        StudentOnboardingEmailOtpService::stashPendingPhone($indexHash, $destination);
        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. This code stays valid until you receive a new one.';
        $result = ArkeselService::sendSms($destination, $message);
        StudentOnboardingEmailOtpService::recordSmsAttempt($indexHash);

        if (! $result['success']) {
            StudentUniversalOtp::enableFallback($indexHash);

            if (($result['connection_error'] ?? false) && ArkeselService::allowLocalConnectionFallback()) {
                Otp::create([
                    'index_number_hash' => $indexHash,
                    'type' => Otp::TYPE_STUDENT_LOGIN,
                    'code' => $code,
                    'phone' => $destination,
                    'expires_at' => null,
                ]);
                StudentUniversalOtp::clearFallback($indexHash);
                Cache::put('otp_resend:'.$indexHash, 1, now()->addSeconds(Otp::RESEND_COOLDOWN_SECONDS));

                return response()->json(array_merge([
                    'success' => true,
                    'step' => 'otp',
                    'index_number' => $student->index_number,
                    'message' => 'SMS could not be sent from this computer. Use this code to continue testing: '.$code,
                    'dev_otp_code' => $code,
                    'sms_delivered' => false,
                    'has_name' => ! empty($student->student_name),
                    'can_resend' => true,
                    'days_remaining' => null,
                    'otp_never_expires' => true,
                    'otp_channel' => 'sms',
                ], StudentOnboardingEmailOtpService::emailFallbackMeta($student, $indexHash),
                    StudentUniversalOtp::fallbackMeta($student, $indexHash)));
            }

            $meta = array_merge(
                StudentOnboardingEmailOtpService::emailFallbackMeta($student, $indexHash),
                StudentUniversalOtp::fallbackMeta($student, $indexHash, true)
            );
            if ($meta['email_fallback_available'] ?? false) {
                $meta['show_email_fallback'] = true;
            }

            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (($result['connection_error'] ?? false) && ($meta['universal_fallback_available'] ?? false)) {
                $msg = 'SMS could not be sent (network). Enter the institution login code from your examiner, or try email fallback below.';
            } elseif (($result['connection_error'] ?? false) && ($meta['email_fallback_available'] ?? false)) {
                $msg = 'SMS could not be sent (network). Try the email code option below.';
            } elseif (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }

            return response()->json(array_merge([
                'success' => false,
                'message' => $msg,
                'step' => ($meta['universal_fallback_available'] ?? false) || ($meta['email_fallback_available'] ?? false) ? 'otp' : null,
            ], $meta), 422);
        }

        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'phone' => $destination,
            'expires_at' => null,
        ]);
        StudentUniversalOtp::clearFallback($indexHash);

        if ($smsOwner) {
            $smsOwner->increment('sms_used');
        }
        Cache::put('otp_resend:'.$indexHash, 1, now()->addSeconds(Otp::RESEND_COOLDOWN_SECONDS));

        return response()->json(array_merge([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => ($result['log_driver'] ?? false)
                ? $result['message']
                : ($sendToPhone === null
                    ? 'A code has been sent to your registered number. It stays valid until you request a new code.'
                    : 'A code has been sent to your number. It stays valid until you request a new code.'),
            'has_name' => ! empty($student->student_name),
            'can_resend' => true,
            'days_remaining' => null,
            'otp_never_expires' => true,
            'otp_channel' => 'sms',
            'log_driver' => (bool) ($result['log_driver'] ?? false),
        ], StudentOnboardingEmailOtpService::emailFallbackMeta($student, $indexHash),
            StudentUniversalOtp::fallbackMeta($student, $indexHash)));
    }

    /**
     * Fast index-verify path: reuse a recent code or show OTP step without calling SMS API.
     */
    protected function jsonOtpStepWithoutSending(Student $student, string $indexHash): JsonResponse
    {
        $lastOtp = Otp::latestStudentLoginForIndex($indexHash);

        if ($lastOtp && ! $lastOtp->isExpired()
            && $lastOtp->created_at
            && $lastOtp->created_at->gt(now()->subMinutes(Otp::STUDENT_LOGIN_SMS_COOLDOWN_MINUTES))) {
            return $this->jsonReuseExistingSmsOtp($student, $indexHash, $lastOtp);
        }

        return response()->json(array_merge([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'Tap Resend code to receive a 6-digit SMS login code.',
            'has_name' => ! empty($student->student_name),
            'can_resend' => true,
            'days_remaining' => null,
            'otp_never_expires' => true,
            'otp_channel' => 'sms',
        ], StudentOnboardingEmailOtpService::emailFallbackMeta($student, $indexHash),
            StudentUniversalOtp::fallbackMeta($student, $indexHash)));
    }

    protected function jsonReuseExistingSmsOtp(Student $student, string $indexHash, Otp $lastOtp): JsonResponse
    {
        StudentUniversalOtp::clearFallback($indexHash);
        $daysRemaining = $lastOtp->daysRemaining();
        StudentOnboardingEmailOtpService::recordSmsAttempt($indexHash);

        return response()->json(array_merge([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code was already sent recently. Use the 6-digit code from your last SMS, or wait a few minutes and use Resend code.',
            'has_name' => ! empty($student->student_name),
            'can_resend' => true,
            'days_remaining' => $daysRemaining,
            'otp_never_expires' => $daysRemaining === null,
            'otp_channel' => 'sms',
        ], StudentOnboardingEmailOtpService::emailFallbackMeta($student, $indexHash),
            StudentUniversalOtp::fallbackMeta($student, $indexHash)));
    }

    protected function pendingPasswordCacheKey(string $indexHash): string
    {
        return 'student_pw_setup:'.$indexHash;
    }
}
