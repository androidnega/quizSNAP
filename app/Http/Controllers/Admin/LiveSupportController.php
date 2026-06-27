<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\SupportMessage;
use App\Models\SupportSession;
use App\Services\LiveSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveSupportController extends Controller
{
    use InteractsWithAdminSession;

    public function __construct(private LiveSupportService $support) {}

    public function index(): View
    {
        $admin = $this->adminUser();
        abort_unless($admin && $admin->isSuperAdmin(), 403);

        return view('admin.support.index', [
            'openSessions' => $this->support->openSessionsForAdmin(),
        ]);
    }

    public function sessions(): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json([
            'success' => true,
            'sessions' => $this->support->openSessionsForAdmin()->map->toClientArray()->values(),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $this->ensureSuperAdmin();
        $session = $this->support->findByUuid($uuid);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'session' => $session->load('assignedAdmin')->toClientArray(),
            'messages' => $session->messages()->orderBy('id')->limit(500)->get()->map->toPayload()->values(),
        ]);
    }

    public function claim(string $uuid): JsonResponse
    {
        $admin = $this->ensureSuperAdmin();
        $session = $this->support->findByUuid($uuid);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $session = $this->support->claimSession($session, $admin);

        return response()->json([
            'success' => true,
            'session' => $session->toClientArray(),
        ]);
    }

    public function sendMessage(Request $request, string $uuid): JsonResponse
    {
        $admin = $this->ensureSuperAdmin();
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session not available.'], 404);
        }

        if ($session->assigned_admin_id && (int) $session->assigned_admin_id !== (int) $admin->id) {
            return response()->json(['success' => false, 'message' => 'This chat is assigned to another agent.'], 403);
        }

        if (! $session->assigned_admin_id) {
            $session = $this->support->claimSession($session, $admin);
        }

        $data = $request->validate([
            'body' => 'nullable|string|max:2000',
            'message_type' => 'nullable|string|in:text,webrtc,system',
            'meta' => 'nullable|array',
        ]);

        $type = $data['message_type'] ?? SupportMessage::TYPE_TEXT;
        if ($type === SupportMessage::TYPE_TEXT && trim((string) ($data['body'] ?? '')) === '') {
            return response()->json(['success' => false, 'message' => 'Message cannot be empty.'], 422);
        }

        $message = $this->support->sendMessage(
            $session,
            'admin',
            $admin->id,
            $data['body'] ?? null,
            $type,
            $data['meta'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => $message->toPayload(),
        ]);
    }

    public function requestScreenShare(string $uuid): JsonResponse
    {
        $admin = $this->ensureSuperAdmin();
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session not available.'], 404);
        }

        if (! $session->assigned_admin_id) {
            $session = $this->support->claimSession($session, $admin);
        }

        $this->support->setScreenShare($session, true);
        $this->support->sendMessage(
            $session,
            'system',
            null,
            'An agent requested to view your screen. Please tap "Share screen" when prompted.',
            SupportMessage::TYPE_SYSTEM,
        );
        $this->support->sendMessage(
            $session,
            'admin',
            $admin->id,
            null,
            SupportMessage::TYPE_WEBRTC,
            ['signal' => 'request_screen'],
        );

        return response()->json(['success' => true]);
    }

    public function close(string $uuid): JsonResponse
    {
        $this->ensureSuperAdmin();
        $session = $this->support->findByUuid($uuid);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $session = $this->support->closeSession($session, 'Support agent closed this chat.');

        return response()->json([
            'success' => true,
            'session' => $session->toClientArray(),
        ]);
    }

    private function ensureSuperAdmin()
    {
        $admin = $this->adminUser();
        abort_unless($admin && $admin->isSuperAdmin(), 403);

        return $admin;
    }
}
