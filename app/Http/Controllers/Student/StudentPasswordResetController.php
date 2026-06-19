<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Mail\StudentPasswordResetMail;
use App\Models\ClassGroupStudent;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPasswordReset;
use App\Services\MailConfigService;
use App\Services\StudentAuthAuditLogger;
use App\Services\StudentAuthThrottleService;
use App\Services\StudentPasswordChangeLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentPasswordResetController extends Controller
{
    private const RESET_LINK_MINUTES = 60;

    public function showForgotForm(): View
    {
        if (! Student::isPasswordResetEnabled()) {
            abort(404);
        }

        return view('student.forgot-password', [
            'password_login_enabled' => Student::isPasswordLoginEnabled(),
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        if (! Student::isPasswordResetEnabled() || ! Student::isPasswordLoginEnabled()) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'Password reset is not available.');
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'email' => 'required|email|max:255',
        ]);

        $indexHash = Student::hashIndexNumber($request->index_number);
        $student = Student::where('index_number_hash', $indexHash)->first();

        $genericMessage = 'If your index and email match our records, we have sent a password reset link. Check your inbox.';

        if (! $student || ! $student->hasPassword()) {
            StudentAuthAuditLogger::log('password_reset_requested_unknown', null, $indexHash, $request);

            return back()->withInput($request->only('index_number', 'email'))
                ->with('info', $genericMessage);
        }

        $submittedEmail = strtolower(trim($request->email));
        $storedEmail = strtolower(trim((string) ($student->email ?? '')));

        if ($storedEmail === '' || $storedEmail !== $submittedEmail) {
            StudentAuthAuditLogger::log('password_reset_email_mismatch', $student, $indexHash, $request);

            return back()->withInput($request->only('index_number', 'email'))
                ->with('info', $genericMessage);
        }

        if (StudentAuthThrottleService::isLocked(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash)) {
            return back()->withInput($request->only('index_number', 'email'))
                ->with('error', StudentAuthThrottleService::lockoutMessage(StudentAuthThrottleService::TYPE_PASSWORD, $indexHash));
        }

        if (! StudentPasswordChangeLimiter::canChangePassword($student)) {
            StudentAuthAuditLogger::log('password_reset_weekly_limit', $student, $indexHash, $request, [
                'recent_changes' => StudentPasswordChangeLimiter::recentChangeCount($student),
            ]);

            return back()->withInput($request->only('index_number', 'email'))
                ->with('error', StudentPasswordChangeLimiter::blockedMessage());
        }

        MailConfigService::applyFromSettings();

        $token = Str::random(64);
        StudentPasswordReset::where('student_id', $student->id)->delete();
        StudentPasswordReset::create([
            'student_id' => $student->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(self::RESET_LINK_MINUTES),
        ]);

        $resetUrl = route('student.password.reset.form', ['token' => $token]);

        try {
            Mail::to($student->email)->send(new StudentPasswordResetMail($student, $resetUrl, self::RESET_LINK_MINUTES));
            StudentAuthAuditLogger::log('password_reset_sent', $student, $indexHash, $request);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->only('index_number', 'email'))
                ->with('error', 'Could not send email. Try again later or contact support.');
        }

        return redirect()->route('student.account.login.form')
            ->with('info', $genericMessage);
    }

    public function showResetForm(string $token): View|RedirectResponse
    {
        if (! Student::isPasswordResetEnabled()) {
            abort(404);
        }

        $reset = StudentPasswordReset::where('token', $token)->with('student')->first();
        if (! $reset || $reset->isExpired()) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'This reset link has expired. Request a new one from the forgot password page.');
        }

        return view('student.reset-password', [
            'token' => $token,
            'expiresMinutes' => self::RESET_LINK_MINUTES,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        if (! Student::isPasswordResetEnabled()) {
            abort(404);
        }

        $request->validate([
            'token' => 'required|string|size:64',
            'password' => Student::passwordValidationRules(),
        ], Student::passwordValidationMessages());

        $reset = StudentPasswordReset::where('token', $request->token)->with('student')->first();
        if (! $reset || $reset->isExpired()) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'This reset link has expired. Request a new one from the forgot password page.');
        }

        $student = $reset->student;

        if (! StudentPasswordChangeLimiter::canChangePassword($student)) {
            $reset->delete();

            return redirect()->route('student.account.login.form')
                ->with('error', StudentPasswordChangeLimiter::blockedMessage());
        }

        $student->password = Hash::make($request->password);
        $student->save();
        $reset->delete();

        StudentAuthThrottleService::clearFailures(StudentAuthThrottleService::TYPE_PASSWORD, $student->index_number_hash);
        StudentAuthAuditLogger::log('password_reset_completed', $student, $student->index_number_hash, $request);

        return redirect()->route('student.account.login.form')
            ->with('success', 'Your password has been reset. Sign in with your index number and new password.');
    }
}
