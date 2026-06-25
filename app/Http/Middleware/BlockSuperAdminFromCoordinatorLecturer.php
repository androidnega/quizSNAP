<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block Super Admin from coordinator/lecturer academic workflows.
 * Super Admin may access class groups and students (all institutions) for roster management.
 * Super Admin cannot access: Courses, Quizzes, Docu Mentor coordinator area, exam calendar.
 */
class BlockSuperAdminFromCoordinatorLecturer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        if (!$user || !$user->isSuperAdmin()) {
            return $next($request);
        }

        $blocked = $request->routeIs('dashboard.courses.*')
            || $request->routeIs('dashboard.quizzes.*')
            || $request->routeIs('dashboard.coordinators.*')
            || $request->routeIs('dashboard.exam-calendar.*')
            || $request->routeIs('dashboard.student-notifications.*');

        if ($blocked) {
            return redirect()->route('dashboard')
                ->with('error', 'This section is for coordinators and lecturers only. Use Class Groups or Students to manage rosters, or Institutions and Users for staff.');
        }

        return $next($request);
    }
}
