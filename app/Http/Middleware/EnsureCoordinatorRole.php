<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCoordinatorRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access the coordinator area.');
        }
        if (!$user->isCoordinator() || $user->isSuperAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to the coordinator area. This section is for coordinators only.');
        }

        return $next($request);
    }
}
