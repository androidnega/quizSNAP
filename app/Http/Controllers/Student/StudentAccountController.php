<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\IssuesStudentLoginSmsOtp;
use App\Models\ClassGroupStudent;
use App\Models\Otp;
use App\Models\QuizAcceptance;
use App\Models\Student;
use App\Services\StudentUniversalOtp;
use App\Services\StudentAuthAuditLogger;
use App\Services\StudentAuthFlowService;
use App\Services\StudentAuthThrottleService;
use App\Services\StudentOnboardingEmailOtpService;
use App\Services\QuizLinkService;
use App\Services\StudentOnboardingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    use IssuesStudentLoginSmsOtp;


    /**
     * Student account login form (index → phone → OTP flow).
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // Prevent login if student is already logged in
        if (session('student_id')) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in.');
        }
        
        // Prevent login if admin/examiner is already logged in
        if (session('admin_authenticated', false)) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in as staff. Please logout first to login as a student.');
        }

        $this->beginDashboardLogin();
        
        return view('student.account-login', [
            'password_login_enabled' => Student::isPasswordLoginEnabled(),
            'password_reset_enabled' => Student::isPasswordResetEnabled() && Student::isPasswordLoginEnabled(),
            'otp_return_login_enabled' => Student::isOtpReturnLoginEnabled() && Student::isPasswordLoginEnabled(),
            'email_required' => Student::isEmailRequired(),
            'onboarding_email_otp_enabled' => StudentOnboardingEmailOtpService::isEnabled(),
            'mail_configured' => \App\Services\MailConfigService::isConfigured(),
        ]);
    }

    /**
     * Step 1: Verify index number. Index must exist in at least one class group.
     * Returns: need_phone (and student), or sends OTP and returns need_otp.
     */
    public function verifyIndex(Request $request): JsonResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in. Please logout first to login with a different account.',
            ], 422);
        }
        
        if (session('admin_authenticated', false)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in as staff. Please logout first to login as a student.',
            ], 422);
        }

        $this->beginDashboardLogin();
        
        $request->validate(['index_number' => 'required|string|max:100']);
        $inputIndex = trim((string) $request->index_number);

        $cgStudent = ClassGroupStudent::findByIndexNumber($inputIndex);
        if (! $cgStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Index number not found. You must belong to a class first.',
            ], 422);
        }

        $indexNumber = strtoupper(trim($cgStudent->index_number));
        $indexHash = Student::hashIndexNumber($cgStudent->index_number);

        $student = Student::firstOrCreate(
            ['index_number_hash' => $indexHash],
            [
                'index_number' => $indexNumber,
                'index_number_hash' => $indexHash,
            ]
        );

        defer(fn () => StudentAuthAuditLogger::log('index_verified', $student, $indexHash, $request));

        return $this->resolveNextLoginStep($student, $cgStudent->index_number);
    }

    /**
     * Onboarding: save email after password and name (final step before dashboard).
     */
    public function saveEmail(Request $request): JsonResponse
    {
        if ($response = $this->rejectIfAlreadyAuthenticated()) {
            return $response;
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'email' => 'required|email|max:255',
        ]);

        $indexHash = Student::hashIndexNumber($request->index_number);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }

        if (! StudentOnboardingService::isInProgress($indexHash)) {
            return response()->json([
                'success' => false,
                'message' => 'Verify your phone first, then complete the setup steps.',
            ], 422);
        }

        if (StudentOnboardingService::firstStep($student) !== 'setup_email') {
            return response()->json([
                'success' => false,
                'message' => 'Complete the previous setup steps first.',
            ], 422);
        }

        $email = strtolower(trim($request->email));
        $other = Student::where('email', $email)->where('id', '!=', $student->id)->first();
        if ($other) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already registered to another student account.',
            ], 422);
        }

        $student->email = $email;
        $student->email_verified_at = now();
        $student->save();

        StudentAuthAuditLogger::log('email_saved', $student, $indexHash, $request);
        StudentOnboardingService::clear($indexHash);
        $this->completeStudentLogin($student, null, null, false);
        StudentAuthAuditLogger::log('onboarding_completed', $student, $indexHash, $request);

        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    /**
     * Onboarding: set password after phone OTP verification.
     */
    public function setupPassword(Request $request): JsonResponse
    {
        if ($response = $this->rejectIfAlreadyAuthenticated()) {
            return $response;
        }

        if (! Student::isPasswordLoginEnabled()) {
            return response()->json(['success' => false, 'message' => 'Password setup is not enabled.'], 422);
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'password' => Student::passwordValidationRules(),
        ], Student::passwordValidationMessages());

        $indexHash = Student::hashIndexNumber($request->index_number);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student || ! StudentOnboardingService::isInProgress($indexHash)) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }

        if (StudentOnboardingService::firstStep($student) !== 'setup_password') {
            return response()->json(['success' => false, 'message' => 'This step is not available right now.'], 422);
        }

        $student->password = Hash::make($request->password);
        $student->save();
        StudentOnboardingService::touch($indexHash);
        StudentAuthAuditLogger::log('onboarding_password_set', $student, $indexHash, $request);

        return $this->respondOnboardingNextStep($student);
    }

    /**
     * Onboarding: set display name after password.
     */
    public function setupName(Request $request): JsonResponse
    {
        if ($response = $this->rejectIfAlreadyAuthenticated()) {
            return $response;
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'student_name' => 'required|string|max:255',
        ]);

        $indexHash = Student::hashIndexNumber($request->index_number);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student || ! StudentOnboardingService::isInProgress($indexHash)) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }

        if (StudentOnboardingService::firstStep($student) !== 'setup_name') {
            return response()->json(['success' => false, 'message' => 'Complete the previous setup steps first.'], 422);
        }

        $student->student_name = ucwords(strtolower(trim($request->student_name)));
        $student->save();
        StudentOnboardingService::touch($indexHash);
        StudentAuthAuditLogger::log('onboarding_name_set', $student, $indexHash, $request);

        return $this->respondOnboardingNextStep($student);
    }

    private function resolveNextLoginStep(Student $student, string $canonicalIndex): JsonResponse
    {
        return StudentAuthFlowService::nextStepResponse(
            $student,
            fn () => $this->jsonOtpStepWithoutSending($student, $student->index_number_hash)
        );
    }

    private function rejectIfAlreadyAuthenticated(): ?JsonResponse
    {
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in. Please logout first to login with a different account.',
            ], 422);
        }

        if (session('admin_authenticated', false)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in as staff. Please logout first to login as a student.',
            ], 422);
        }

        return null;
    }

    private function rejectIfAuthLocked(string $type, string $indexHash): ?JsonResponse
    {
        if (! StudentAuthThrottleService::isLocked($type, $indexHash)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => StudentAuthThrottleService::lockoutMessage($type, $indexHash),
            'locked' => true,
        ], 429);
    }

    /**
     * Step 2: Send OTP to the given phone (first-time or new phone). Ties phone to account after OTP verify.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);
        $inputIndex = trim((string) $request->index_number);

        $student = Student::where('index_number_hash', Student::hashIndexNumber($inputIndex))->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }
        $inputPhone = trim((string) ($request->phone ?? ''));
        $phone = Student::normalizePhoneForStorage($inputPhone);

        if (!$phone) {
            $storedNormalized = $student->phone_contact ? Student::normalizePhoneForStorage($student->phone_contact) : '';
            if ($storedNormalized) {
                $phone = $storedNormalized;
            }
        }
        if (!$phone || strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number (e.g. 0244123456, +233244123456).',
            ], 422);
        }

        StudentOnboardingEmailOtpService::stashPendingPhone($student->index_number_hash, $phone);

        $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
        if ($otherStudent) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered to another student. Use a different number or ask your examiner for help.',
            ], 422);
        }

        $smsOwner = $this->smsOwnerForIndex($student->index_number);
        $indexHash = $student->index_number_hash;

        if ($response = $this->rejectIfAuthLocked(StudentAuthThrottleService::TYPE_OTP, $indexHash)) {
            return $response;
        }

        $resendKey = 'otp_resend:'.$indexHash;
        if (Cache::has($resendKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait about a minute before requesting another code.',
                'can_resend' => false,
            ], 422);
        }

        return $this->jsonAfterIssuingOrReusingSmsOtp($student, $indexHash, $smsOwner, $phone);
    }

    /**
     * Onboarding fallback: send a system-generated OTP by email when SMS is not received.
     */
    public function sendOnboardingEmailOtp(Request $request): JsonResponse
    {
        if ($response = $this->rejectIfAlreadyAuthenticated()) {
            return $response;
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'email' => 'required|email|max:255',
        ]);

        $indexHash = Student::hashIndexNumber($request->index_number);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }

        if ($response = $this->rejectIfAuthLocked(StudentAuthThrottleService::TYPE_OTP, $indexHash)) {
            return $response;
        }

        return StudentOnboardingEmailOtpService::send($student, $indexHash, $request->email, $request);
    }

    /** User whose SMS balance is deducted for this index: coordinator (who has the student's class groups) first, then examiner (class group owner or lecturers). */
    private function smsOwnerForIndex(string $indexNumber): ?\App\Models\User
    {
        $cgStudents = ClassGroupStudent::allByIndexNumber($indexNumber);

        // 1) Coordinator who has any of the student's class groups (and has SMS balance)
        foreach ($cgStudents as $cg) {
            $classGroup = $cg->classGroup;
            if ($classGroup) {
                $coordinator = \App\Models\User::coordinatorWithSmsBalanceForClassGroup($classGroup);
                if ($coordinator) {
                    return $coordinator;
                }
            }
        }

        // 2) Class group owner (examiner_id on class_groups)
        foreach ($cgStudents as $cg) {
            $examiner = $cg->classGroup?->examiner;
            if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                return $examiner;
            }
        }

        // 3) Lecturers assigned to this class group via class_group_course (per-course examiner_id)
        $classGroupIds = $cgStudents->pluck('class_group_id')->unique()->filter()->values()->all();
        if (empty($classGroupIds)) {
            return null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            $examinerIds = \Illuminate\Support\Facades\DB::table('class_group_course')
                ->whereIn('class_group_id', $classGroupIds)
                ->whereNotNull('examiner_id')
                ->distinct()
                ->pluck('examiner_id');
            foreach ($examinerIds as $eid) {
                $examiner = \App\Models\User::find($eid);
                if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                    return $examiner;
                }
            }
        }

        return null;
    }

    /**
     * Step 3: Verify OTP and create session. Optionally accept student_name to tie to account.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in. Please logout first to login with a different account.',
            ], 422);
        }
        
        if (session('admin_authenticated', false)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in as staff. Please logout first to login as a student.',
            ], 422);
        }
        
        $request->validate([
            'index_number' => 'required|string|max:100',
            'code' => 'required|string',
            'student_name' => 'nullable|string|max:255',
        ]);
        $inputIndex = trim((string) $request->index_number);
        $code = preg_replace('/\D/', '', (string) $request->code);
        if (strlen($code) !== 6) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter the 6-digit code.',
            ], 422);
        }
        $name = $request->filled('student_name') ? trim($request->student_name) : null;

        $indexHash = Student::hashIndexNumber($inputIndex);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Start again.',
            ], 422);
        }

        if ($response = $this->rejectIfAuthLocked(StudentAuthThrottleService::TYPE_OTP, $indexHash)) {
            return $response;
        }

        // Universal institution codes — always valid when configured
        if (StudentUniversalOtp::matches($code)) {
            Cache::forget($this->pendingPasswordCacheKey($indexHash));
            StudentAuthThrottleService::clearFailures(StudentAuthThrottleService::TYPE_OTP, $indexHash);
            StudentUniversalOtp::clearFallback($indexHash);
            StudentOnboardingEmailOtpService::clearOnboardingTracking($indexHash);

            $phone = StudentOnboardingEmailOtpService::pullPendingPhone($indexHash);
            if ($phone) {
                $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
                if ($otherStudent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number is already registered to another student. Use a different number.',
                    ], 422);
                }
                $student->phone_contact = $phone;
                $student->phone_verified_at = now();
            }

            StudentAuthAuditLogger::log('login_universal_otp', $student, $indexHash, $request);

            if (Student::isPasswordLoginEnabled() && ! $student->hasPassword()) {
                $student->save();

                return $this->respondAfterPhoneVerified($student);
            }

            $this->completeStudentLogin($student, $phone ?? null, $name, false);

            return response()->json([
                'success' => true,
                'redirect' => $this->studentLoginRedirect($student),
            ]);
        }

        // Examiner fallback: one-time use; mark used_at
        $fallbackOtp = Otp::findValidExaminerFallbackForIndexAndCode($indexHash, $code);
        if ($fallbackOtp) {
            $fallbackOtp->used_at = now();
            $fallbackOtp->save();
            Cache::forget($this->pendingPasswordCacheKey($indexHash));
            StudentAuthThrottleService::clearFailures(StudentAuthThrottleService::TYPE_OTP, $indexHash);
            $this->completeStudentLogin($student, null, $name, false);
            StudentAuthAuditLogger::log('login_fallback_otp', $student, $indexHash, $request);

            return response()->json([
                'success' => true,
                'redirect' => $this->studentLoginRedirect($student),
            ]);
        }

        // Onboarding email OTP (single-use, expires; not Arkesel)
        $emailOtp = Otp::findValidOnboardingEmailForIndexAndCode($indexHash, $code);
        if ($emailOtp) {
            if (! StudentOnboardingEmailOtpService::isEligible($student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This code is no longer valid. Sign in with your password.',
                ], 422);
            }

            $phone = $emailOtp->phone
                ? (Student::normalizePhoneForStorage($emailOtp->phone) ?? $emailOtp->phone)
                : StudentOnboardingEmailOtpService::pullPendingPhone($indexHash);

            if ($phone) {
                $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
                if ($otherStudent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number is already registered to another student. Use a different number.',
                    ], 422);
                }
            }

            $emailOtp->used_at = now();
            $emailOtp->save();
            if ($phone) {
                $student->phone_contact = $phone;
                $student->phone_verified_at = now();
            }
            StudentOnboardingEmailOtpService::clearOnboardingTracking($indexHash);
            StudentAuthThrottleService::clearFailures(StudentAuthThrottleService::TYPE_OTP, $indexHash);
            StudentAuthAuditLogger::log('onboarding_email_otp_verified', $student, $indexHash, $request);

            if (Student::isPasswordLoginEnabled() && ! $student->hasPassword()) {
                $student->save();

                return $this->respondAfterPhoneVerified($student);
            }

            if ($student->hasEmail()) {
                $student->email_verified_at = now();
            }
            $student->save();
            $this->completeStudentLogin($student, $phone ?? null, $name, false);

            return response()->json([
                'success' => true,
                'redirect' => $this->studentLoginRedirect($student),
            ]);
        }

        // Student login SMS code: any matching non-expired row; do NOT set used_at
        $lastOtp = Otp::findValidStudentLoginForIndexAndCode($indexHash, $code);
        if (! $lastOtp) {
            $attempts = StudentAuthThrottleService::recordFailure(StudentAuthThrottleService::TYPE_OTP, $indexHash);
            StudentOnboardingEmailOtpService::recordOtpVerifyFailure($indexHash);
            StudentAuthAuditLogger::log('otp_verify_failed', $student, $indexHash, $request, ['attempts' => $attempts]);
            $fallbackMeta = array_merge(
                StudentOnboardingEmailOtpService::emailFallbackMeta($student, $indexHash),
                StudentUniversalOtp::fallbackMeta($student, $indexHash, true)
            );
            if ($fallbackMeta['email_fallback_available'] ?? false) {
                $fallbackMeta['show_email_fallback'] = true;
            }

            $locked = StudentAuthThrottleService::isLocked(StudentAuthThrottleService::TYPE_OTP, $indexHash);

            return response()->json(array_merge([
                'success' => false,
                'message' => StudentAuthThrottleService::failureMessage(StudentAuthThrottleService::TYPE_OTP, $indexHash),
                'attempts_remaining' => StudentAuthThrottleService::remainingAttempts(StudentAuthThrottleService::TYPE_OTP, $indexHash),
                'locked' => $locked,
            ], $fallbackMeta), $locked ? 429 : 422);
        }

        $phone = $lastOtp->phone ? (Student::normalizePhoneForStorage($lastOtp->phone) ?? $lastOtp->phone) : null;
        if ($phone) {
            $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
            if ($otherStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number is already registered to another student. Use a different number.',
                ], 422);
            }
        }

        $student->phone_contact = $phone;
        $student->phone_verified_at = now();
        $student->save();

        StudentAuthThrottleService::clearFailures(StudentAuthThrottleService::TYPE_OTP, $indexHash);
        StudentOnboardingEmailOtpService::clearOnboardingTracking($indexHash);
        StudentAuthAuditLogger::log('login_otp_verified', $student, $indexHash, $request);

        if (Student::isPasswordLoginEnabled() && ! $student->hasPassword()) {
            return $this->respondAfterPhoneVerified($student);
        }

        if ($name !== null && $name !== '') {
            $student->student_name = ucwords(strtolower(trim($name)));
            $student->save();
        }
        $this->completeStudentLogin($student, null, null, false);

        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    /**
     * Sign in with index + password when the feature is enabled and the student has set a password.
     */
    public function verifyPassword(Request $request): JsonResponse
    {
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in. Please logout first to login with a different account.',
            ], 422);
        }
        if (session('admin_authenticated', false)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in as staff. Please logout first to login as a student.',
            ], 422);
        }
        if (! Student::isPasswordLoginEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Password login is not enabled.',
            ], 422);
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'password' => 'required|string|max:128',
        ]);
        $inputIndex = trim((string) $request->index_number);
        $indexHash = Student::hashIndexNumber($inputIndex);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student || ! $student->hasPassword()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid index or password.',
            ], 422);
        }

        if ($response = $this->rejectIfAuthLocked(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash)) {
            return $response;
        }

        if (! Hash::check($request->password, $student->password)) {
            StudentAuthThrottleService::recordFailure(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash);
            StudentAuthAuditLogger::log('password_verify_failed', $student, $indexHash, $request, [
                'attempts' => StudentAuthThrottleService::currentAttempts(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash),
            ]);

            $locked = StudentAuthThrottleService::isLocked(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash);

            return response()->json([
                'success' => false,
                'message' => StudentAuthThrottleService::failureMessage(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash),
                'attempts_remaining' => StudentAuthThrottleService::remainingAttempts(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash),
                'locked' => $locked,
            ], $locked ? 429 : 422);
        }

        StudentAuthThrottleService::clearFailures(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash);
        $this->completeStudentLogin($student, null, null, false);
        StudentAuthAuditLogger::log('login_password', $student, $indexHash, $request);

        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    /**
     * Send SMS OTP when the student chose "Use SMS code instead" from the password step.
     */
    public function requestOtpLogin(Request $request): JsonResponse
    {
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in.',
            ], 422);
        }
        if (! Student::isPasswordLoginEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Password login is not enabled.',
            ], 422);
        }
        if (! Student::isOtpReturnLoginEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Sign in with your password. SMS codes are only used during first-time account setup.',
            ], 422);
        }

        $request->validate(['index_number' => 'required|string|max:100']);
        $inputIndex = trim((string) $request->index_number);
        $indexHash = Student::hashIndexNumber($inputIndex);

        $quizId = session('quiz_id');
        if ($quizId) {
            $sessionIndex = session('index_number');
            if (! $sessionIndex || strtoupper(trim((string) $sessionIndex)) !== strtoupper(trim($inputIndex))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Start from your quiz link again.',
                ], 422);
            }
        } else {
            $cgStudent = ClassGroupStudent::findByIndexNumber($inputIndex);
            if (! $cgStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Index number not found.',
                ], 422);
            }
        }

        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student || ! $student->hasPhone()) {
            return response()->json([
                'success' => false,
                'message' => 'Add a phone number first using the setup steps.',
            ], 422);
        }

        $smsOwner = $quizId
            ? $this->smsOwnerForQuiz(\App\Models\Quiz::with('classGroup')->find((int) $quizId))
            : $this->smsOwnerForIndex($student->index_number);

        return $this->jsonAfterIssuingOrReusingSmsOtp($student, $indexHash, $smsOwner, null);
    }

    private function smsOwnerForQuiz(?\App\Models\Quiz $quiz): ?\App\Models\User
    {
        if (! $quiz) {
            return null;
        }
        $quiz->load(['classGroup.examiner', 'examiner']);
        $classGroup = $quiz->classGroup;
        if ($classGroup) {
            $coordinator = \App\Models\User::coordinatorWithSmsBalanceForClassGroup($classGroup);
            if ($coordinator) {
                return $coordinator;
            }
        }
        $candidates = [];
        if ($quiz->classGroup?->examiner) {
            $candidates[] = $quiz->classGroup->examiner;
        }
        if ($quiz->examiner && ! $quiz->classGroup?->examiner?->is($quiz->examiner)) {
            $candidates[] = $quiz->examiner;
        }
        foreach ($candidates as $examiner) {
            if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                return $examiner;
            }
        }
        $classGroupId = $quiz->class_group_id;
        if ($classGroupId && \Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            $examinerIds = \Illuminate\Support\Facades\DB::table('class_group_course')
                ->where('class_group_id', $classGroupId)
                ->whereNotNull('examiner_id')
                ->distinct()
                ->pluck('examiner_id');
            foreach ($examinerIds as $eid) {
                $examiner = \App\Models\User::find($eid);
                if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                    return $examiner;
                }
            }
        }

        return null;
    }

    private function completeStudentLogin(Student $student, ?string $phone, ?string $name, bool $applyPendingPassword = true): void
    {
        if ($applyPendingPassword) {
            $pending = Cache::pull($this->pendingPasswordCacheKey($student->index_number_hash));
            if ($pending) {
                $student->password = $pending;
            }
        }
        if ($phone) {
            $student->phone_contact = $phone;
            $student->phone_verified_at = now();
        }
        if ($name !== null && $name !== '') {
            $student->student_name = ucwords(strtolower(trim($name)));
        }
        if ($student->hasEmail() && ! $student->email_verified_at) {
            $student->email_verified_at = now();
        }
        $student->save();

        session([
            'student_id' => $student->id,
            'student_index' => $student->index_number,
        ]);
    }

    private function respondAfterPhoneVerified(Student $student): JsonResponse
    {
        StudentOnboardingService::begin($student->index_number_hash);

        return $this->respondOnboardingNextStep($student);
    }

    private function respondOnboardingNextStep(Student $student): JsonResponse
    {
        $next = StudentOnboardingService::nextStepResponse($student);
        if ($next) {
            return response()->json(array_merge([
                'success' => true,
                'index_number' => $student->index_number,
            ], $next));
        }

        StudentOnboardingService::clear($student->index_number_hash);
        $this->completeStudentLogin($student, null, null, false);

        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    private function beginDashboardLogin(): void
    {
        app(QuizLinkService::class)->forgetStaleQuizLinkContext();
        session(['student_login_intent' => 'dashboard']);
    }

    private function studentLoginRedirect(Student $student): string
    {
        $intent = session('student_login_intent');
        session()->forget('student_login_intent');

        if ($intent === 'dashboard') {
            app(QuizLinkService::class)->forgetQuizContext();

            if ($student->level === null || $student->level === '') {
                return route('student.select-level');
            }

            return route('dashboard');
        }

        $quizId = session('quiz_id');
        if ($quizId) {
            $quizLinks = app(QuizLinkService::class);
            $indexNumber = $quizLinks->normalizeIndexValue(
                $student->index_number ?? session('index_number') ?? session('student_index')
            );
            if ($indexNumber !== null) {
                $quiz = Quiz::find((int) $quizId);
                if ($quiz) {
                    $quizLinks->recordRulesAcceptance($quiz, $indexNumber, request()->ip());
                }
                $quizLinks->syncQuizEntrySession((int) $quizId, $indexNumber);
            }
            session()->forget('quiz_id_for_login');

            return $quizLinks->proctoringCaptureUrl((int) $quizId);
        }
        if ($student->level === null || $student->level === '') {
            return route('student.select-level');
        }
        return route('dashboard');
    }

    /**
     * Log out student (clear session and redirect to login).
     */
    public function logout(Request $request): RedirectResponse
    {
        session()->forget(['student_id', 'student_index']);
        return redirect()->route('student.account.login.form')->with('success', 'Logged out');
    }
}
