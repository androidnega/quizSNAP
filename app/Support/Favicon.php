<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class Favicon
{
    /** QuizSnap signature amber — fixed, not theme-dependent. */
    public const COLOR_PUBLIC = '#f59e0b';

    /** Admin / staff chrome. */
    public const COLOR_ADMIN = '#1e293b';

    public static function isStaffContext(?Request $request = null): bool
    {
        $request ??= request();
        if (! $request) {
            return false;
        }

        if ($request->routeIs('admin.*', 'examiner.*', 'login', 'login.post', 'password.*')) {
            return true;
        }

        $user = auth()->user();

        return $user instanceof User && $request->routeIs('dashboard', 'dashboard.*');
    }

    public static function variant(?Request $request = null): string
    {
        return self::isStaffContext($request) ? 'admin' : 'default';
    }

    public static function filename(?Request $request = null): string
    {
        return self::variant($request) === 'admin' ? 'favicon-admin.svg' : 'favicon.svg';
    }

    public static function url(?Request $request = null): string
    {
        return asset(self::filename($request));
    }

    public static function themeColor(?Request $request = null): string
    {
        return self::isStaffContext($request) ? self::COLOR_ADMIN : self::COLOR_PUBLIC;
    }
}
