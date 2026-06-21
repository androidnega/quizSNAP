<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBroadcastingAuthenticated
{
    /**
     * Authenticate admin staff for /broadcasting/auth without redirects (Pusher expects JSON).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_authenticated', false) || ! session('admin_user_id')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::find(session('admin_user_id'));
        if (! $user instanceof User || ! $user->canAccessEnterpriseBroadcasting()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        session(['admin_role' => $user->role]);

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
