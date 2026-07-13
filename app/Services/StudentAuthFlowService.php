<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\JsonResponse;

class StudentAuthFlowService
{
    /**
     * Determine the next onboarding/login step after index verification.
     *
     * Phone is always required before password login or OTP-only continue.
     *
     * @param  callable(): JsonResponse  $issueOtp
     */
    public static function nextStepResponse(Student $student, callable $issueOtp): JsonResponse
    {
        // Phone is mandatory — collect/verify before password or return OTP login.
        if ($student->needsPhoneVerification() || ! $student->hasPhone()) {
            $payload = [
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'require_phone_verification' => true,
                'password_login_enabled' => Student::isPasswordLoginEnabled(),
                'message' => $student->hasPhone()
                    ? 'Verify your phone number with a one-time SMS code.'
                    : 'Enter your active phone number. It is required for account access (SMS or institution code).',
            ];
            if ($student->hasPhone()) {
                $payload['prefill_phone'] = $student->phone_contact;
            }

            return response()->json($payload);
        }

        if (Student::isPasswordLoginEnabled() && $student->hasPassword()) {
            return response()->json([
                'success' => true,
                'step' => 'password',
                'index_number' => $student->index_number,
                'message' => 'Enter the password you saved for your account.',
                'password_login_enabled' => true,
                'password_reset_enabled' => Student::isPasswordResetEnabled(),
            ]);
        }

        return $issueOtp();
    }
}
