<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\SupportMessage;
use App\Models\SupportSession;
use App\Models\User;
use App\Services\LiveSupportService;
use App\Services\SupportAgentPresenceService;
use App\Services\SupportChatMediaService;
use App\Support\LiveSupportAccess;
use App\Support\SupportAgentAvatars;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveSupportController extends Controller
{
    use InteractsWithAdminSession;

    public function __construct(
        private LiveSupportService $support,
        private SupportAgentPresenceService $presence,
        private SupportChatMediaService $media,
    ) {}

    public function index(): View
    {
        $staff = $this->ensureStaff();

        return view('admin.support.index', [
            'openSessions' => $this->support->openSessionsForStaff($staff),
            'canDeleteSessions' => LiveSupportAccess::canDeleteSession($staff),
            'supportDisplayName' => $staff->support_display_name,
            'resolvedSupportDisplayName' => $staff->supportDisplayName(),
            'supportAvatar' => $staff->support_avatar,
            'avatarCatalog' => SupportAgentAvatars::catalog(),
        ]);
    }

    public function sessions(): JsonResponse
    {
        $staff = $this->ensureStaff();

        return response()->json([
            'success' => true,
            'waiting_count' => $this->support->waitingCountForStaff($staff),
            'sessions' => $this->support->openSessionsForStaff($staff)->map->toClientArray()->values(),
        ]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $sinceId = (int) $request->query('since', 0);
        $messagesQuery = $session->messages()->orderBy('id');
        if ($sinceId > 0) {
            $messagesQuery->where('id', '>', $sinceId);
        } else {
            $messagesQuery->limit(500);
        }

        return response()->json([
            'success' => true,
            'session' => $session->load('assignedAdmin')->toClientArray(),
            'messages' => $messagesQuery->get()->map->toPayload()->values(),
        ]);
    }

    public function claim(string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $result = $this->support->claimSession($session, $staff);
        if (! $result['claimed']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Could not claim chat.',
                'session' => $result['session']->toClientArray(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'session' => $result['session']->toClientArray(),
        ]);
    }

    public function sendMessage(Request $request, string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session || ! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session not available.'], 404);
        }

        if ($session->assigned_admin_id && (int) $session->assigned_admin_id !== (int) $staff->id) {
            $name = $session->assignedAdmin?->name ?: 'Another agent';

            return response()->json(['success' => false, 'message' => $name.' is already handling this chat.'], 403);
        }

        if (! $session->assigned_admin_id) {
            $result = $this->support->claimSession($session, $staff);
            if (! $result['claimed']) {
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Could not claim chat.'], 409);
            }
            $session = $result['session'];
        }

        $data = $request->validate([
            'body' => 'nullable|string|max:2000',
            'message_type' => 'nullable|string|in:text,webrtc,system,image,audio',
            'meta' => 'nullable|array',
        ]);

        $type = $data['message_type'] ?? SupportMessage::TYPE_TEXT;
        if ($type === SupportMessage::TYPE_TEXT && trim((string) ($data['body'] ?? '')) === '') {
            return response()->json(['success' => false, 'message' => 'Message cannot be empty.'], 422);
        }

        $message = $this->support->sendMessage(
            $session,
            'admin',
            $staff->id,
            $data['body'] ?? null,
            $type,
            $data['meta'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => $message->toPayload(),
        ]);
    }

    public function uploadImage(Request $request, string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session || ! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session not available.'], 404);
        }

        if ($session->assigned_admin_id && (int) $session->assigned_admin_id !== (int) $staff->id) {
            return response()->json(['success' => false, 'message' => 'This chat is assigned to another agent.'], 403);
        }

        if (! $session->assigned_admin_id) {
            $result = $this->support->claimSession($session, $staff);
            if (! $result['claimed']) {
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Could not claim chat.'], 409);
            }
            $session = $result['session'];
        }

        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $stored = $this->media->storeImage($session, $request->file('image'));

        $message = $this->support->sendMessage(
            $session,
            'admin',
            $staff->id,
            null,
            SupportMessage::TYPE_IMAGE,
            $stored,
        );

        return response()->json([
            'success' => true,
            'message' => $message->toPayload(),
        ]);
    }

    public function uploadAudio(Request $request, string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session || ! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session not available.'], 404);
        }

        if ($session->assigned_admin_id && (int) $session->assigned_admin_id !== (int) $staff->id) {
            return response()->json(['success' => false, 'message' => 'This chat is assigned to another agent.'], 403);
        }

        if (! $session->assigned_admin_id) {
            $result = $this->support->claimSession($session, $staff);
            if (! $result['claimed']) {
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Could not claim chat.'], 409);
            }
            $session = $result['session'];
        }

        $request->validate([
            'audio' => 'required|file|max:8192',
        ]);

        $file = $request->file('audio');
        $allowed = ['webm', 'ogg', 'mp3', 'mpeg', 'mp4', 'm4a', 'wav', 'x-wav'];
        $ext = strtolower($file->getClientOriginalExtension() ?: 'webm');
        if (! in_array($ext, $allowed, true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported audio format.'], 422);
        }

        $stored = $this->media->storeAudio($session, $file);

        $message = $this->support->sendMessage(
            $session,
            'admin',
            $staff->id,
            null,
            SupportMessage::TYPE_AUDIO,
            $stored,
        );

        return response()->json([
            'success' => true,
            'message' => $message->toPayload(),
        ]);
    }

    public function requestScreenShare(string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session || ! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session not available.'], 404);
        }

        if ($session->assigned_admin_id && (int) $session->assigned_admin_id !== (int) $staff->id) {
            return response()->json(['success' => false, 'message' => 'This chat is assigned to another agent.'], 403);
        }

        if (! $session->assigned_admin_id) {
            $result = $this->support->claimSession($session, $staff);
            if (! $result['claimed']) {
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Could not claim chat.'], 409);
            }
            $session = $result['session'];
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
            $staff->id,
            null,
            SupportMessage::TYPE_WEBRTC,
            ['signal' => 'request_screen'],
        );

        return response()->json(['success' => true]);
    }

    public function close(string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if ($session->assigned_admin_id && (int) $session->assigned_admin_id !== (int) $staff->id) {
            return response()->json(['success' => false, 'message' => 'Only the assigned agent can close this chat.'], 403);
        }

        $session = $this->support->closeSession($session, 'Support agent closed this chat.');

        return response()->json([
            'success' => true,
            'session' => $session->toClientArray(),
        ]);
    }

    public function presence(): JsonResponse
    {
        $staff = $this->ensureStaff();
        $this->presence->ping($staff);

        return response()->json([
            'success' => true,
            'agents_online' => $this->presence->anyAgentOnline(),
        ]);
    }

    public function availableAgents(): JsonResponse
    {
        $staff = $this->ensureStaff();

        return response()->json([
            'success' => true,
            'agents' => $this->presence->onlineRespondersExcluding((int) $staff->id),
        ]);
    }

    public function displayName(): JsonResponse
    {
        $staff = $this->ensureStaff();

        return response()->json([
            'success' => true,
            'support_display_name' => $staff->support_display_name,
            'resolved_name' => $staff->supportDisplayName(),
        ]);
    }

    public function updateDisplayName(Request $request): JsonResponse
    {
        $staff = $this->ensureStaff();

        $data = $request->validate([
            'support_display_name' => 'nullable|string|max:64',
        ]);

        $value = isset($data['support_display_name']) ? trim((string) $data['support_display_name']) : '';
        $staff->update([
            'support_display_name' => $value !== '' ? $value : null,
        ]);
        $staff->refresh();

        return response()->json([
            'success' => true,
            'support_display_name' => $staff->support_display_name,
            'resolved_name' => $staff->supportDisplayName(),
        ]);
    }

    public function avatarCatalog(): JsonResponse
    {
        $this->ensureStaff();

        return response()->json([
            'success' => true,
            'catalog' => SupportAgentAvatars::catalog(),
        ]);
    }

    public function avatar(): JsonResponse
    {
        $staff = $this->ensureStaff();

        return response()->json([
            'success' => true,
            'support_avatar' => $staff->support_avatar,
            'avatar' => $staff->supportAvatarPayload(),
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $staff = $this->ensureStaff();

        $data = $request->validate([
            'support_avatar' => 'nullable|string|max:32',
        ]);

        $value = SupportAgentAvatars::normalize($data['support_avatar'] ?? null);
        $staff->update(['support_avatar' => $value]);
        $staff->refresh();

        return response()->json([
            'success' => true,
            'support_avatar' => $staff->support_avatar,
            'avatar' => $staff->supportAvatarPayload(),
        ]);
    }

    public function refer(Request $request, string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session || ! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session not available.'], 404);
        }

        $data = $request->validate([
            'agent_id' => 'required|integer|exists:users,id',
        ]);

        $target = User::find((int) $data['agent_id']);
        if (! $target) {
            return response()->json(['success' => false, 'message' => 'Agent not found.'], 404);
        }

        $result = $this->support->referSession($session, $staff, $target);
        if (! $result['referred']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Could not refer chat.',
                'session' => $result['session']->toClientArray(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'session' => $result['session']->toClientArray(),
        ]);
    }

    public function typing(Request $request, string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        $session = $this->findScopedSession($uuid, $staff);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $data = $request->validate([
            'typing' => 'required|boolean',
        ]);

        $label = $staff->supportDisplayName();
        $this->support->broadcastTyping($session, 'admin', $label, (bool) $data['typing']);

        return response()->json(['success' => true]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $staff = $this->ensureStaff();
        if (! LiveSupportAccess::canDeleteSession($staff)) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete chats.'], 403);
        }

        $session = $this->findScopedSession($uuid, $staff);
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Chat not found.'], 404);
        }

        $this->support->deleteSession($session);

        return response()->json(['success' => true]);
    }

    private function ensureStaff(): User
    {
        $staff = $this->adminUser();
        abort_unless($staff && LiveSupportAccess::canRespond($staff), 403);

        return $staff;
    }

    private function ensureSuperAdmin(): User
    {
        $staff = $this->adminUser();
        abort_unless($staff && LiveSupportAccess::canDeleteSession($staff), 403);

        return $staff;
    }

    private function findScopedSession(string $uuid, User $staff): ?SupportSession
    {
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! LiveSupportAccess::sessionInScope($staff, $session)) {
            return null;
        }

        return $session;
    }
}
