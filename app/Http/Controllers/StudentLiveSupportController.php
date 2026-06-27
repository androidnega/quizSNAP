<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportSession;
use App\Services\LiveSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentLiveSupportController extends Controller
{
    public function __construct(private LiveSupportService $support) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_index' => 'nullable|string|max:64',
            'student_name' => 'nullable|string|max:255',
            'page_url' => 'nullable|string|max:500',
            'issue_category' => 'nullable|string|max:64',
            'initial_message' => 'nullable|string|max:2000',
        ]);

        $session = $this->support->createSession($data);

        if (! empty($data['initial_message'])) {
            $this->support->sendMessage(
                $session,
                'student',
                null,
                trim($data['initial_message']),
            );
        }

        return response()->json([
            'success' => true,
            'session' => $session->toClientArray(),
            'client_token' => $session->client_token,
        ]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $this->support->authorizeClient($session, $this->clientToken($request))) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'session' => $session->fresh(['assignedAdmin'])->toClientArray(),
        ]);
    }

    public function messages(Request $request, string $uuid): JsonResponse
    {
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $this->support->authorizeClient($session, $this->clientToken($request))) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $sinceId = (int) $request->query('since', 0);
        $query = $session->messages()->orderBy('id');
        if ($sinceId > 0) {
            $query->where('id', '>', $sinceId);
        } else {
            $query->limit(200);
        }

        return response()->json([
            'success' => true,
            'messages' => $query->get()->map->toPayload()->values(),
        ]);
    }

    public function sendMessage(Request $request, string $uuid): JsonResponse
    {
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $this->support->authorizeClient($session, $this->clientToken($request))) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        if (! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'This chat is closed.'], 422);
        }

        $data = $request->validate([
            'body' => 'nullable|string|max:2000',
            'message_type' => 'nullable|string|in:text,webrtc',
            'meta' => 'nullable|array',
        ]);

        $type = $data['message_type'] ?? SupportMessage::TYPE_TEXT;
        if ($type === SupportMessage::TYPE_TEXT && trim((string) ($data['body'] ?? '')) === '') {
            return response()->json(['success' => false, 'message' => 'Message cannot be empty.'], 422);
        }

        $message = $this->support->sendMessage(
            $session,
            'student',
            null,
            $data['body'] ?? null,
            $type,
            $data['meta'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => $message->toPayload(),
        ]);
    }

    public function close(Request $request, string $uuid): JsonResponse
    {
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $this->support->authorizeClient($session, $this->clientToken($request))) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $session = $this->support->closeSession($session, 'You left the chat.');

        return response()->json([
            'success' => true,
            'session' => $session->toClientArray(),
        ]);
    }

    private function clientToken(Request $request): ?string
    {
        return $request->header('X-Support-Session-Token')
            ?: $request->input('client_token');
    }
}
