<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\EnterpriseCenterAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperationsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            $user = User::find(session('admin_user_id'));
        }

        if (! $user || ! $user->canAccessOperations()) {
            return redirect()->route('dashboard')
                ->with('error', EnterpriseCenterAccess::deniedMessage($user, 'Operations Center'));
        }

        return $next($request);
    }
}
