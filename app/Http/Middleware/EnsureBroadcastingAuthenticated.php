<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBroadcastingAuthenticated
{
    /**
     * Authenticate staff for /broadcasting/auth without redirects (Pusher expects JSON).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_authenticated', false) || ! session('admin_user_id')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::find(session('admin_user_id'));
        if (! $user || ! $user->isStaff()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        session(['admin_role' => $user->role]);
        auth()->setUser($user);

        return $next($request);
    }
}
