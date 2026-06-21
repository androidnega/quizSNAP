<?php

namespace App\Support;

use App\Models\User;

final class EnterpriseCenterAccess
{
    public static function deniedMessage(?User $user, string $center): string
    {
        $role = $user?->role ?? session('admin_role') ?? 'unknown';
        $label = User::monitoringRoleLabels()[$role]
            ?? User::superAdminCreatableRoles()[$role]
            ?? $role;

        return sprintf(
            '%s is only available to Super Admin or System Monitor accounts. Your account role is "%s".',
            $center,
            $label
        );
    }
}
