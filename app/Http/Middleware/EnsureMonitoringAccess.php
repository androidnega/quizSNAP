<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\EnterpriseCenterAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMonitoringAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        if (! $user || ! $user->canAccessMonitoring()) {
            return redirect()->route('dashboard')
                ->with('error', EnterpriseCenterAccess::deniedMessage($user, 'Monitoring Center'));
        }

        return $next($request);
    }
}
