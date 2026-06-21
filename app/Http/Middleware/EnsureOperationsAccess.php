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
        $user = EnterpriseCenterAccess::resolveUser();
        if (! $user instanceof User) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        EnterpriseCenterAccess::syncSessionUser($user);

        if (! $user->canAccessOperations()) {
            return redirect()->route('dashboard')
                ->with('error', EnterpriseCenterAccess::deniedMessage($user, 'Operations Center'));
        }

        return $next($request);
    }
}
