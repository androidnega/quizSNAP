<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\SecurityMonitoringService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RecordMonitoringSecurityEvents
{
    public function handle(Request $request, Closure $next): Response
    {
        if (RateLimiter::tooManyAttempts('monitoring-security:'.$request->ip(), 120)) {
            app(SecurityMonitoringService::class)->recordRateLimitViolation('monitoring-security');
        }

        try {
            $response = $next($request);
        } catch (AuthorizationException $e) {
            app(SecurityMonitoringService::class)->recordPermissionDenied($request->route()?->getName());
            throw $e;
        }

        if ($response->getStatusCode() === 403) {
            app(SecurityMonitoringService::class)->recordUnauthorizedAccess($request->route()?->getName());
        }

        if ($response->getStatusCode() === 429) {
            app(SecurityMonitoringService::class)->recordRateLimitViolation('http-429');
        }

        return $response;
    }
}
