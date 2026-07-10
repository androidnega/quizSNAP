<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Support\StaffSession;
use App\Support\StudentSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // Staff session takes priority — stale student_id must not shadow examiner/admin login.
        if (StaffSession::resolve($request)) {
            return $next($request);
        }

        if (session('student_id')) {
            $student = Student::find(session('student_id'));
            if ($student) {
                auth()->setUser($student);

                return $next($request);
            }
            session()->forget(['student_id', 'student_index']);
        }

        return redirect()->route('student.account.login.form')
            ->with('error', 'Please sign in to access the dashboard.');
    }
}
