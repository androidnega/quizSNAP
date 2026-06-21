<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\MonitoringNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringNotificationController extends Controller
{
    public function index(MonitoringNotificationService $notifications): View
    {
        $user = auth()->user();

        return view('admin.monitoring.notifications.index', [
            'notifications' => $notifications->recent(50, $user),
            'unreadCount' => $notifications->unreadCount($user),
        ]);
    }

    public function unreadCount(MonitoringNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'count' => $notifications->unreadCount(auth()->user()),
        ]);
    }

    public function markRead(Request $request, MonitoringNotificationService $notifications): JsonResponse|RedirectResponse
    {
        $id = (int) $request->input('id');
        $notifications->markRead($id, auth()->user());

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(MonitoringNotificationService $notifications): RedirectResponse
    {
        $notifications->markAllRead(auth()->user());

        return back()->with('success', 'All notifications marked as read.');
    }

    public function recent(MonitoringNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'notifications' => $notifications->recent(10, auth()->user()),
            'unread_count' => $notifications->unreadCount(auth()->user()),
        ]);
    }
}
