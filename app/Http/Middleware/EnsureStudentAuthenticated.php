<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Support\UserFriendlyMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $studentId = session('student_id');
        if (!$studentId) {
            return redirect()->guest(route('student.account.login.form'))
                ->with('error', UserFriendlyMessages::SIGN_IN_REQUIRED);
        }

        $student = auth()->user();
        if (! $student instanceof Student) {
            $student = Student::find($studentId);
        }
        if (!$student) {
            session()->forget(['student_id', 'student_index']);
            return redirect()->guest(route('student.account.login.form'))
                ->with('error', UserFriendlyMessages::SIGN_IN_REQUIRED);
        }

        auth()->setUser($student);

        return $next($request);
    }
}
