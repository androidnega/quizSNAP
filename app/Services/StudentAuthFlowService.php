<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\JsonResponse;

class StudentAuthFlowService
{
    /**
     * Determine the next onboarding/login step after index verification.
     *
     * @param  callable(): JsonResponse  $issueOtp
     */
    public static function nextStepResponse(Student $student, callable $issueOtp): JsonResponse
    {
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

        if (Student::isPasswordLoginEnabled() && ! $student->hasPassword()) {
            $payload = [
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'require_phone_verification' => true,
                'password_login_enabled' => true,
                'message' => 'Enter your phone number. We will send a one-time SMS code to verify it.',
            ];
            if ($student->hasPhone()) {
                $payload['prefill_phone'] = $student->phone_contact;
            }

            return response()->json($payload);
        }

        if ($student->needsPhoneVerification()) {
            return response()->json([
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'require_phone_verification' => true,
                'message' => $student->hasPhone()
                    ? 'Verify your phone number with a one-time SMS code.'
                    : 'Enter your active phone number to receive a one-time code.',
                'prefill_phone' => $student->hasPhone() ? $student->phone_contact : null,
            ]);
        }

        return $issueOtp();
    }
}
