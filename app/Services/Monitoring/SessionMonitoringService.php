<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringUserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionMonitoringService
{
    /** Keep under DB column size (512) with byte-safe margin. */
    private const BROWSER_MAX = 500;

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
            $userAgent = (string) $request->userAgent();

            $session = MonitoringUserSession::query()->firstOrNew(['session_id' => $sessionId]);
            if (! $session->exists) {
                $session->started_at = now();
            }

            $session->fill([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role ?? session('admin_role'),
                'actor_type' => session('student_id') ? 'student' : 'staff',
                'ip_address' => $request->ip(),
                'current_page' => Str::limit($request->fullUrl(), 500, ''),
                'browser' => $this->normalizeBrowser($userAgent),
                'device' => $this->detectDevice($request, $userAgent),
                'is_active' => true,
                'last_activity_at' => now(),
            ]);
            $session->save();
        } catch (\Throwable) {
            // Never report — avoids error-storm when session tracking fails.
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
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function normalizeBrowser(string $userAgent): ?string
    {
        $userAgent = trim($userAgent);

        return $userAgent === '' ? null : Str::limit($userAgent, self::BROWSER_MAX, '');
    }

    protected function detectDevice(Request $request, string $userAgent): string
    {
        if ($request->header('Sec-CH-UA-Mobile') === '?1') {
            return 'Mobile';
        }

        if (preg_match('/Android|webOS|iPhone|iPod|iPad|Mobile|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            return 'Mobile';
        }

        if (preg_match('/Tablet|iPad/i', $userAgent)) {
            return 'Tablet';
        }

        return 'Desktop';
    }
}
