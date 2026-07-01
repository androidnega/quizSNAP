<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Support\StaffSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('student_id')) {
            $student = Student::find(session('student_id'));
            if ($student) {
                auth()->setUser($student);

                return $next($request);
            }
            session()->forget(['student_id', 'student_index']);
        }

        if (StaffSession::resolve($request)) {
            return $next($request);
        }

        return redirect()->route('login')
            ->with('error', 'Please log in to access the dashboard.');
    }
}
