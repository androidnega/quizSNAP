<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\IssuesStudentLoginSmsOtp;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentAuthAuditLogger;
use App\Services\StudentAuthFlowService;
use App\Services\StudentOnboardingEmailOtpService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class StudentLoginController extends Controller
{
    use IssuesStudentLoginSmsOtp;

    /**
     * Show index number entry. Quiz is fixed from the link (stored in session after rules acceptance).
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in. Please logout first to login with a different account.');
        }
        
        if (session('admin_authenticated', false)) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in as staff. Please logout first to take a quiz.');
        }
        
        $quizId = session('quiz_id_for_login');
        if (!$quizId) {
            $quizToken = session('quiz_link_token');
            if ($quizToken) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quizToken])
                    ->with('error', 'Your quiz session expired. Please accept the rules again.');
            }
            return redirect()->route('student.landing')
                ->with('error', 'Your quiz session expired. Please open your quiz link again.');
        }
        $quiz = Quiz::find($quizId);
        if (! $quiz || ! $quiz->isAvailableForStudent(false)) {
            if ($quiz && $quiz->starts_at && $quiz->starts_at->isFuture()) {
                return redirect()->route('student.quiz-will-start', ['token' => $quiz->link_token]);
            }
            if ($quiz && $quiz->link_token) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                    ->with('error', 'This quiz is not currently available.');
            }
            return redirect()->route('student.landing')->with('error', 'This quiz is not currently available.');
        }
        return view('student.login', [
            'quiz' => $quiz,
            'password_login_enabled' => Student::isPasswordLoginEnabled(),
            'password_reset_enabled' => Student::isPasswordResetEnabled() && Student::isPasswordLoginEnabled(),
            'otp_return_login_enabled' => Student::isOtpReturnLoginEnabled() && Student::isPasswordLoginEnabled(),
            'onboarding_email_otp_enabled' => StudentOnboardingEmailOtpService::isEnabled(),
            'mail_configured' => \App\Services\MailConfigService::isConfigured(),
        ]);
    }

    /**
     * Verify index against the quiz's class group student list. On success store quiz_id + index_number, redirect to proctoring.
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
                'message' => 'You are already logged in as staff. Please logout first to take a quiz.',
            ], 422);
        }
        
        $request->validate(['index_number' => 'required|string']);
        $indexNumber = strtoupper(trim($request->index_number));
        $quizId = session('quiz_id_for_login');
        if (!$quizId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please start from the quiz link again.',
            ], 422);
        }

        $quiz = Quiz::with('classGroup')->find($quizId);
        if (! $quiz || ! $quiz->isAvailableForStudent(false, $indexNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is no longer available.',
            ], 422);
        }
        if (!$quiz->class_group_id && !$quiz->academic_class_id) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is no longer available.',
            ], 422);
        }

        $exists = false;
        if ($quiz->class_group_id) {
            $exists = ClassGroupStudent::existsInClassGroup((int) $quiz->class_group_id, $indexNumber);
        }
        if (!$exists && $quiz->academic_class_id && $quiz->academic_year_id) {
            $studentRecord = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
            if ($studentRecord
                && (int) $studentRecord->academic_class_id === (int) $quiz->academic_class_id
                && (int) $studentRecord->academic_year_id === (int) $quiz->academic_year_id
                && (!$quiz->level_id || (int) $studentRecord->level_id === (int) $quiz->level_id)
                && (!$quiz->semester_id || (int) $studentRecord->semester_id === (int) $quiz->semester_id)) {
                $exists = true;
            }
        }
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Index number not found. You must belong to a class first.',
            ], 422);
        }

        if ($this->isIpDeviceRestrictionEnabled()) {
            $ip = $request->ip();
            // Only treat as "in use" if a session has this IP and it was not reset (reset-* = released)
            if (QuizSession::where('quiz_id', $quiz->id)
                ->where('ip_address', $ip)
                ->whereRaw("ip_address NOT LIKE 'reset-%'")
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This IP address has already been used for this quiz. Access denied.',
                ], 422);
            }
        }

        session([
            'quiz_id' => $quiz->id,
            'index_number' => $indexNumber,
            'rules_accepted' => true,
        ]);
        session()->forget('quiz_id_for_login');

        QuizAcceptance::updateOrCreate(
            [
                'quiz_id' => $quiz->id,
                'index_number' => $indexNumber,
            ],
            [
                'ip_address' => $request->ip(),
                'accepted_at' => now(),
            ]
        );

        $indexHash = Student::hashIndexNumber($indexNumber);
        $student = Student::firstOrCreate(
            ['index_number_hash' => $indexHash],
            [
                'index_number' => $indexNumber,
                'index_number_hash' => $indexHash,
            ]
        );

        defer(fn () => StudentAuthAuditLogger::log('quiz_index_verified', $student, $indexHash, $request));

        $quiz->load(['classGroup.examiner', 'examiner']);

        return StudentAuthFlowService::nextStepResponse(
            $student,
            fn () => $this->jsonOtpStepWithoutSending($student, $indexHash)
        );
    }

    /**
     * User whose SMS balance is deducted for this quiz's OTP: coordinator (who has the class group) first, then examiner (class group owner, quiz examiner, or lecturers).
     */
    private function smsOwnerForQuiz(Quiz $quiz): ?User
    {
        $classGroup = $quiz->classGroup;
        if ($classGroup) {
            $coordinator = User::coordinatorWithSmsBalanceForClassGroup($classGroup);
            if ($coordinator) {
                return $coordinator;
            }
        }

        $candidates = [];
        if ($quiz->classGroup?->examiner) {
            $candidates[] = $quiz->classGroup->examiner;
        }
        if ($quiz->examiner && !$quiz->classGroup?->examiner?->is($quiz->examiner)) {
            $candidates[] = $quiz->examiner;
        }
        foreach ($candidates as $examiner) {
            if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                return $examiner;
            }
        }
        $classGroupId = $quiz->class_group_id;
        if ($classGroupId && Schema::hasColumn('class_group_course', 'examiner_id')) {
            $examinerIds = DB::table('class_group_course')
                ->where('class_group_id', $classGroupId)
                ->whereNotNull('examiner_id')
                ->distinct()
                ->pluck('examiner_id');
            foreach ($examinerIds as $eid) {
                $examiner = User::find($eid);
                if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                    return $examiner;
                }
            }
        }
        return null;
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }
}
