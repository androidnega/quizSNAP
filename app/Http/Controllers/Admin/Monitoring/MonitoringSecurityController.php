<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringUserSession;
use App\Models\SecurityEvent;
use App\Models\SystemAuditLog;
use App\Services\Monitoring\SessionMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringSecurityController extends Controller
{
    public function security(Request $request): View
    {
        $query = SecurityEvent::query()->orderByDesc('occurred_at');
        if ($type = $request->query('type')) {
            $query->where('event_type', $type);
        }

        return view('admin.monitoring.security.index', [
            'events' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function auditTrail(Request $request): View
    {
        $query = SystemAuditLog::query()->orderByDesc('occurred_at');
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        return view('admin.monitoring.audit-trail.index', [
            'logs' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function sessions(Request $request): View
    {
        $query = MonitoringUserSession::query()
            ->where('is_active', true)
            ->where('last_activity_at', '>=', now()->subHours(24))
            ->orderByDesc('last_activity_at');

        return view('admin.monitoring.sessions.index', [
            'sessions' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function terminateSession(Request $request, SessionMonitoringService $sessions): RedirectResponse
    {
        $sessionId = $request->input('session_id');
        if (! $sessionId) {
            return back()->with('error', 'Session ID required.');
        }

        $sessions->terminate($sessionId);

        return back()->with('success', 'Session terminated and storage invalidated.');
    }

    public function forceLogout(Request $request): RedirectResponse
    {
        $userId = (int) $request->input('user_id');
        if (! $userId) {
            return back()->with('error', 'User ID required.');
        }

        $count = app(\App\Services\Monitoring\SessionTerminationService::class)->forceLogoutUser($userId);

        return back()->with('success', "Force logged out {$count} session(s).");
    }
}
