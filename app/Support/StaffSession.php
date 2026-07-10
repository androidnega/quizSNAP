<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/** Shared staff login session keys used by /login and dashboard middleware. */
final class StaffSession
{
    public const REMEMBER_COOKIE = 'quizsnap_remember';

    /** @return list<string> */
    public static function allowedRoles(): array
    {
        return [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_LEGACY_ADMIN,
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_EXAMINER,
            User::ROLE_COORDINATOR,
            User::ROLE_SUPPORT_AGENT,
        ];
    }

    public static function establish(Request $request, User $user): void
    {
        self::clearStudentSessionKeys();
        auth()->logout();
        auth()->login($user, false);
        $request->session()->put('admin_authenticated', true);
        $request->session()->put('admin_user_id', $user->id);
        $request->session()->put('admin_role', $user->role);
    }

    public static function clear(): void
    {
        auth()->logout();
        session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
    }

    public static function clearStudentSessionKeys(): void
    {
        session()->forget(['student_id', 'student_index', 'student_login_intent']);
    }

    public static function restoreFromRememberCookie(Request $request): ?User
    {
        if (session('admin_authenticated', false) && session('admin_user_id')) {
            return null;
        }

        if (session('student_login_intent') || session('student_id')) {
            return null;
        }

        $token = $request->cookie(self::REMEMBER_COOKIE);
        if (! $token) {
            return null;
        }

        return User::where('remember_token', $token)
            ->whereIn('role', self::allowedRoles())
            ->first();
    }

    public static function resolve(Request $request): ?User
    {
        if (session('admin_authenticated', false) && session('admin_user_id')) {
            return self::applyAuthenticatedUser((int) session('admin_user_id'));
        }

        if (session('student_login_intent') || session('student_id')) {
            return null;
        }

        $fromRemember = self::restoreFromRememberCookie($request);
        if ($fromRemember) {
            $request->session()->regenerate();
            self::establish($request, $fromRemember);

            return $fromRemember;
        }

        return null;
    }

    /** Bind the staff user to the auth guard for this request. */
    public static function applyAuthenticatedUser(int $userId): ?User
    {
        $user = User::with('institution')->find($userId);
        if (! $user || ! $user->isStaff()) {
            self::clear();

            return null;
        }

        self::clearStudentSessionKeys();
        session(['admin_role' => $user->role]);
        auth()->login($user, false);

        return $user;
    }
}
