<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringSecurityEventOccurred;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class SecurityMonitoringService
{
    public function record(string $eventType, string $description, string $severity = 'warning', ?array $meta = null): ?SecurityEvent
    {
        if (! Schema::hasTable('security_events')) {
            return null;
        }

        try {
            $user = auth()->user();
            $riskScore = $this->calculateRiskScore($eventType, $severity, $meta);
            $event = SecurityEvent::query()->create([
                'event_type' => $eventType,
                'severity' => $severity,
                'risk_score' => $riskScore,
                'user_id' => $user instanceof User ? $user->id : null,
                'user_name' => $user instanceof User ? $user->name : null,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'route' => request()?->route()?->getName(),
                'description' => $description,
                'meta' => $meta,
                'occurred_at' => now(),
            ]);

            broadcast(new MonitoringSecurityEventOccurred($event))->toOthers();
            app(MonitoringNotificationService::class)->notifyForSecurityEvent($event);

            return $event;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public function recordFailedLogin(?string $username = null): void
    {
        $this->record(
            SecurityEvent::TYPE_FAILED_LOGIN,
            'Failed login attempt'.($username ? " for {$username}" : ''),
            'warning',
            ['username' => $username]
        );
    }

    public function recordPermissionDenied(?string $action = null): void
    {
        $this->record(
            SecurityEvent::TYPE_PERMISSION_DENIED,
            'Permission denied'.($action ? ": {$action}" : ''),
            'warning',
            ['action' => $action]
        );
    }

    public function recordCsrfFailure(): void
    {
        $this->record(SecurityEvent::TYPE_CSRF_FAILURE, 'CSRF token mismatch', 'warning');
    }

    public function recordRateLimitViolation(?string $limiter = null): void
    {
        $this->record(
            SecurityEvent::TYPE_RATE_LIMIT,
            'Rate limit exceeded'.($limiter ? " ({$limiter})" : ''),
            'warning',
            ['limiter' => $limiter]
        );
    }

    public function recordUnauthorizedAccess(?string $route = null): void
    {
        $this->record(SecurityEvent::TYPE_UNAUTHORIZED_ACCESS, 'Unauthorized route access'.($route ? ": {$route}" : ''), 'critical', ['route' => $route]);
    }

    public function recordRoleEscalationAttempt(?string $attemptedRole = null): void
    {
        $this->record(SecurityEvent::TYPE_ROLE_ESCALATION, 'Role escalation attempt'.($attemptedRole ? " to {$attemptedRole}" : ''), 'critical', ['attempted_role' => $attemptedRole]);
    }

    public function recordInvalidToken(?string $context = null): void
    {
        $this->record(SecurityEvent::TYPE_INVALID_TOKEN, 'Invalid token'.($context ? " ({$context})" : ''), 'warning', ['context' => $context]);
    }

    public function recordRepeatedFailedLogins(string $identifier, int $count): void
    {
        $this->record(
            'repeated_failed_login',
            "Repeated failed logins ({$count}) for {$identifier}",
            $count >= 10 ? 'critical' : 'warning',
            ['identifier' => $identifier, 'count' => $count]
        );
    }

    protected function calculateRiskScore(string $eventType, string $severity, ?array $meta): int
    {
        $base = match ($severity) {
            'critical' => 80,
            'warning' => 40,
            'info' => 10,
            default => 25,
        };

        $typeBoost = match ($eventType) {
            SecurityEvent::TYPE_ROLE_ESCALATION, SecurityEvent::TYPE_UNAUTHORIZED_ACCESS => 15,
            SecurityEvent::TYPE_FAILED_LOGIN, SecurityEvent::TYPE_RATE_LIMIT => 5,
            default => 0,
        };

        $countBoost = isset($meta['count']) ? min(20, (int) $meta['count']) : 0;

        return min(100, $base + $typeBoost + $countBoost);
    }
}
