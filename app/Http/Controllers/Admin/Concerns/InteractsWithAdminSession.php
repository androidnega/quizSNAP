<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\User;
use App\Support\StaffSession;

trait InteractsWithAdminSession
{
    protected function adminUser(): ?User
    {
        $user = auth()->user();
        if ($user instanceof User) {
            return $user;
        }

        $adminUserId = session('admin_user_id');

        return $adminUserId
            ? StaffSession::applyAuthenticatedUser((int) $adminUserId)
            : null;
    }

    /** Route prefix for redirects: unified dashboard. */
    protected function staffRoutePrefix(): string
    {
        return 'dashboard';
    }
}
