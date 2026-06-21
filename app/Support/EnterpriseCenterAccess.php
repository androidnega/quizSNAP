<?php

namespace App\Support;

use App\Models\User;

final class EnterpriseCenterAccess
{
    public static function resolveUser(): ?User
    {
        $user = auth()->user();
        if ($user instanceof User) {
            return $user;
        }

        $userId = session('admin_user_id');
        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    public static function syncSessionUser(?User $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        auth()->setUser($user);
        session(['admin_role' => $user->role]);
    }

    public static function deniedMessage(?User $user, string $center): string
    {
        $role = $user?->role ?? session('admin_role') ?? 'unknown';
        $label = User::monitoringRoleLabels()[$role]
            ?? User::superAdminCreatableRoles()[$role]
            ?? $role;

        return sprintf(
            '%s is only available to System Monitor accounts. Your account role is "%s".',
            $center,
            $label
        );
    }
}
