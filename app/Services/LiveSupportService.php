<?php

namespace App\Services;

use App\Events\Support\SupportMessageSent;
use App\Events\Support\SupportSessionUpdated;
use App\Models\SupportMessage;
use App\Models\SupportSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LiveSupportService
{
    public function createSession(array $data): SupportSession
    {
        $session = SupportSession::createGuestSession([
            'student_index' => isset($data['student_index']) ? trim((string) $data['student_index']) : null,
            'student_name' => isset($data['student_name']) ? trim((string) $data['student_name']) : null,
            'page_url' => isset($data['page_url']) ? substr(trim((string) $data['page_url']), 0, 500) : null,
            'issue_category' => isset($data['issue_category']) ? substr(trim((string) $data['issue_category']), 0, 64) : null,
            'last_message_at' => now(),
        ]);

        $this->addSystemMessage($session, 'Support request received. An agent will join shortly.');

        SupportSessionUpdated::dispatch($session->fresh(['assignedAdmin']));

        return $session;
    }

    public function findByUuid(string $uuid): ?SupportSession
    {
        return SupportSession::where('uuid', $uuid)->first();
    }

    public function authorizeClient(SupportSession $session, ?string $token): bool
    {
        return $token !== null && $token !== '' && hash_equals($session->client_token, $token);
    }

    public function claimSession(SupportSession $session, User $admin): SupportSession
    {
        if (! $session->isOpen()) {
            return $session;
        }

        $session->update([
            'status' => SupportSession::STATUS_ACTIVE,
            'assigned_admin_id' => $admin->id,
            'claimed_at' => $session->claimed_at ?? now(),
        ]);

        $name = $admin->name ?: $admin->username;
        $this->addSystemMessage($session, $name.' joined the chat.');

        $fresh = $session->fresh(['assignedAdmin']);
        SupportSessionUpdated::dispatch($fresh);

        return $fresh;
    }

    public function closeSession(SupportSession $session, string $reason = 'Session closed.'): SupportSession
    {
        if ($session->status === SupportSession::STATUS_CLOSED) {
            return $session;
        }

        $session->update([
            'status' => SupportSession::STATUS_CLOSED,
            'closed_at' => now(),
            'screen_share_active' => false,
        ]);

        $this->addSystemMessage($session, $reason);

        $fresh = $session->fresh(['assignedAdmin']);
        SupportSessionUpdated::dispatch($fresh);

        return $fresh;
    }

    public function sendMessage(
        SupportSession $session,
        string $senderType,
        ?int $senderId,
        ?string $body,
        string $messageType = SupportMessage::TYPE_TEXT,
        ?array $meta = null,
    ): SupportMessage {
        $message = DB::transaction(function () use ($session, $senderType, $senderId, $body, $messageType, $meta) {
            $msg = SupportMessage::create([
                'support_session_id' => $session->id,
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'message_type' => $messageType,
                'body' => $body,
                'meta' => $meta,
            ]);

            $session->update(['last_message_at' => now()]);

            return $msg;
        });

        SupportMessageSent::dispatch($session->fresh(['assignedAdmin']), $message);

        return $message;
    }

    public function addSystemMessage(SupportSession $session, string $body): SupportMessage
    {
        return $this->sendMessage($session, 'system', null, $body, SupportMessage::TYPE_SYSTEM);
    }

    public function setScreenShare(SupportSession $session, bool $active): SupportSession
    {
        $session->update(['screen_share_active' => $active]);
        $fresh = $session->fresh(['assignedAdmin']);
        SupportSessionUpdated::dispatch($fresh);

        return $fresh;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SupportSession> */
    public function openSessionsForAdmin()
    {
        return SupportSession::query()
            ->with(['assignedAdmin:id,name,username'])
            ->whereIn('status', [SupportSession::STATUS_WAITING, SupportSession::STATUS_ACTIVE])
            ->orderByRaw("CASE WHEN status = 'waiting' THEN 0 ELSE 1 END")
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();
    }
}
