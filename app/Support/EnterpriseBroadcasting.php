<?php

namespace App\Support;

use App\Models\User;

final class EnterpriseBroadcasting
{
    public static function resolveUser(): ?User
    {
        $user = auth()->user();
        if ($user instanceof User) {
            return $user;
        }

        if (! session('admin_authenticated', false) || ! session('admin_user_id')) {
            return null;
        }

        return User::find(session('admin_user_id'));
    }

    public static function authorize(): bool
    {
        $user = self::resolveUser();

        return $user instanceof User && $user->canAccessEnterpriseBroadcasting();
    }
}
