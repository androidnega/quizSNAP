<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportSession;
use App\Services\LiveSupportService;
use App\Services\SupportAgentPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentLiveSupportController extends Controller
{
    public function __construct(
        private LiveSupportService $support,
        private SupportAgentPresenceService $presence,
    ) {}

    public function availability(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'agents_online' => $this->presence->anyAgentOnline(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $guestRules = [];
        if (! session('student_id')) {
            $guestRules = [
                'student_name' => 'required|string|max:255',
                'student_phone' => 'required|string|max:32',
                'student_email' => 'nullable|email|max:255',
                'student_index' => 'nullable|string|max:64',
            ];
        }

        $data = $request->validate(array_merge([
            'student_index' => 'nullable|string|max:64',
            'student_name' => 'nullable|string|max:255',
            'student_phone' => 'nullable|string|max:32',
            'student_email' => 'nullable|string|max:255',
            'page_url' => 'nullable|string|max:500',
            'issue_category' => 'nullable|string|max:64',
            'initial_message' => 'nullable|string|max:2000',
        ], $guestRules), [
            'student_name.required' => 'Please enter your name so an agent can help you.',
            'student_phone.required' => 'Please enter your phone number so we can reach you.',
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
            'session' => $session->fresh(['assignedAdmin'])->toClientArray(),
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
            'message_type' => 'nullable|string|in:text,webrtc,image',
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

    public function uploadImage(Request $request, string $uuid): JsonResponse
    {
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $this->support->authorizeClient($session, $this->clientToken($request))) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        if (! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'This chat is closed.'], 422);
        }

        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $path = $request->file('image')->store('support-images', 'public');
        $url = Storage::disk('public')->url($path);

        $message = $this->support->sendMessage(
            $session,
            'student',
            null,
            null,
            SupportMessage::TYPE_IMAGE,
            ['url' => $url, 'path' => $path],
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

    public function typing(Request $request, string $uuid): JsonResponse
    {
        $session = $this->support->findByUuid($uuid);
        if (! $session || ! $this->support->authorizeClient($session, $this->clientToken($request))) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $data = $request->validate([
            'typing' => 'required|boolean',
        ]);

        $label = $session->student_name ?: ($session->student_index ?: 'Guest');
        $this->support->broadcastTyping($session, 'student', $label, (bool) $data['typing']);

        return response()->json(['success' => true]);
    }

    private function clientToken(Request $request): ?string
    {
        return $request->header('X-Support-Session-Token')
            ?: $request->input('client_token');
    }
}
