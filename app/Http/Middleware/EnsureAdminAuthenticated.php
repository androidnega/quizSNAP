<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\EnterpriseCenterAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    /**
     * Require staff authentication for admin routes (super_admin or examiner only).
     * Coordinator must not access admin pages; redirect to coordinator dashboard with error instead of login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('login') || $request->routeIs('login.post')) {
            return $next($request);
        }

        // Restore session from "remember me" cookie if session expired
        if (!session('admin_authenticated', false) && $request->cookie('quizsnap_remember')) {
            $user = User::where('remember_token', $request->cookie('quizsnap_remember'))
                ->whereIn('role', [
                    User::ROLE_SUPER_ADMIN,
                    User::ROLE_LEGACY_ADMIN,
                    User::ROLE_SYSTEM_ADMIN,
                    User::ROLE_EXAMINER,
                    User::ROLE_COORDINATOR,
                ])
                ->first();
            if ($user) {
                $request->session()->regenerate();
                session([
                    'admin_authenticated' => true,
                    'admin_user_id' => $user->id,
                    'admin_role' => $user->role,
                ]);
            }
        }

        if (!session('admin_authenticated', false)) {
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in.');
        }

        $user = User::with('institution')->find(session('admin_user_id'));
        if (!$user) {
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
            return redirect()->guest(route('login'))
                ->with('error', 'Session invalid. Please log in again.');
        }

        // System Monitor: dashboard hub, profile, and all enterprise centers only.
        if ($user->role === User::ROLE_SYSTEM_ADMIN) {
            if (! EnterpriseCenterAccess::systemMonitorRouteAllowed($request)) {
                return redirect()->route('dashboard')
                    ->with('error', 'System Monitors can only access their dashboard and enterprise centers.');
            }

            session(['admin_role' => $user->role]);
            auth()->setUser($user);

            return $next($request);
        }

        // Coordinators may access their dashboard (coordinators.*), Docu Mentor (proposals/chapters), Class Groups, Courses, Exam Calendar, profile, and logout
        $coordinatorAllowed = $request->routeIs('dashboard.profile.*')
            || $request->routeIs('dashboard.coordinators.*')
            || $request->routeIs('dashboard.class-groups.*')
            || $request->routeIs('dashboard.students.*')
            || $request->routeIs('dashboard.courses.*')
            || $request->routeIs('dashboard.exam-calendar.*')
            || $request->routeIs('logout');
        if ($user->role === 'coordinator' && !$coordinatorAllowed) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to the admin area. This section is for administrators and examiners only.');
        }

        if ($user->role === 'coordinator') {
            session(['admin_role' => $user->role]);
            auth()->setUser($user);
            return $next($request);
        }

        if (!$user->isStaff()) {
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in with a staff account.');
        }

        // Keep session role in sync with database
        session(['admin_role' => $user->role]);

        // Set user for this request so policies and auth()->user() work
        auth()->setUser($user);

        return $next($request);
    }
}
