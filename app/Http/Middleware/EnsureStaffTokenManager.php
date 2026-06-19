<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserFriendlyMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Super Admin or Coordinator may view examiners and assign AI quiz tokens.
 */
class EnsureStaffTokenManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        if (! $user || (! $user->isSuperAdmin() && ! $user->isCoordinator())) {
            return redirect()->route('dashboard')
                ->with('error', UserFriendlyMessages::ACCESS_DENIED);
        }

        return $next($request);
    }
}
