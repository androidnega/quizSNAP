<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserFriendlyMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIntelligenceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        if (! $user || ! $user->canAccessIntelligence()) {
            return redirect()->route('dashboard')
                ->with('error', UserFriendlyMessages::ACCESS_DENIED);
        }

        return $next($request);
    }
}
