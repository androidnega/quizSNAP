<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringUserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionMonitoringService
{
    public function trackRequest(Request $request): void
    {
        if (! Schema::hasTable('monitoring_user_sessions')) {
            return;
        }

        $sessionId = $request->session()?->getId();
        if (! $sessionId) {
            return;
        }

        try {
            $user = auth()->user();
            MonitoringUserSession::query()->updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_id' => $user?->id,
                    'user_name' => $user?->name,
                    'user_role' => $user?->role ?? session('admin_role'),
                    'actor_type' => session('student_id') ? 'student' : 'staff',
                    'ip_address' => $request->ip(),
                    'current_page' => Str::limit($request->fullUrl(), 500),
                    'browser' => Str::limit((string) $request->userAgent(), 128),
                    'device' => $request->header('Sec-CH-UA-Mobile') === '?1' ? 'Mobile' : 'Desktop',
                    'is_active' => true,
                    'last_activity_at' => now(),
                    'started_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function terminate(string $sessionId): bool
    {
        return app(SessionTerminationService::class)->terminate($sessionId);
    }

    public function activeCount(): int
    {
        if (! Schema::hasTable('monitoring_user_sessions')) {
            return 0;
        }

        try {
            return MonitoringUserSession::query()
                ->where('is_active', true)
                ->where('last_activity_at', '>=', now()->subMinutes(15))
                ->count();
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }
}
