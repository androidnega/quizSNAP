<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class Favicon
{
    /** QuizSnap signature amber — fixed, not theme-dependent. */
    public const COLOR_PUBLIC = '#fdb813';

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

    public static function url(?Request $request = null): string
    {
        return asset('favicon.ico');
    }

    public static function png32(): string
    {
        return asset('favicon-32x32.png');
    }

    public static function png16(): string
    {
        return asset('favicon-16x16.png');
    }

    public static function appleTouchIcon(): string
    {
        return asset('apple-touch-icon.png');
    }

    public static function themeColor(?Request $request = null): string
    {
        return self::isStaffContext($request) ? self::COLOR_ADMIN : self::COLOR_PUBLIC;
    }
}
