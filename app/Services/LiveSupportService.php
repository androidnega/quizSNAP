<?php

namespace App\Services;

use App\Events\Support\SupportMessageSent;
use App\Events\Support\SupportSessionUpdated;
use App\Events\Support\SupportTyping;
use App\Models\ClassGroupStudent;
use App\Models\Student;
use App\Models\SupportMessage;
use App\Models\SupportSession;
use App\Models\User;
use App\Support\LiveSupportAccess;
use Illuminate\Support\Facades\DB;

class LiveSupportService
{
    public function __construct(
        private SupportAgentPresenceService $presence,
        private SupportAgentNotifier $notifier,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function createSession(array $data): SupportSession
    {
        $index = isset($data['student_index']) ? trim((string) $data['student_index']) : null;
        $phone = isset($data['student_phone']) ? trim((string) $data['student_phone']) : null;
        $email = isset($data['student_email']) ? trim((string) $data['student_email']) : null;
        $name = isset($data['student_name']) ? trim((string) $data['student_name']) : null;

        if ((! $index || ! $phone || ! $email) && session('student_id')) {
            $student = Student::find(session('student_id'));
            if ($student) {
                $index = $index ?: ($student->index_number ?: null);
                $phone = $phone ?: ($student->phone_contact ?: null);
                $email = $email ?: ($student->email ?: null);
                $name = $name ?: ($student->student_name ?: null);
            }
        }

        $session = SupportSession::createGuestSession([
            'student_index' => $index,
            'student_name' => $name,
            'student_phone' => $phone,
            'student_email' => $email,
            'institution_id' => LiveSupportAccess::resolveInstitutionId($index),
            'page_url' => isset($data['page_url']) ? substr(trim((string) $data['page_url']), 0, 500) : null,
            'issue_category' => isset($data['issue_category']) ? substr(trim((string) $data['issue_category']), 0, 64) : null,
            'last_message_at' => now(),
        ]);

        if ($this->presence->anyAgentOnline()) {
            $this->addSystemMessage($session, 'Support request received. An agent will join shortly.');
        } else {
            $this->addSystemMessage($session, 'Our support agents are currently away. Please leave your message and we will respond as soon as someone is available.');
        }

        SupportSessionUpdated::dispatch($session->fresh(['assignedAdmin']));
        $this->notifier->notifyNewSession($session);

        return $session;
    }

    public function broadcastTyping(SupportSession $session, string $senderType, string $senderLabel, bool $isTyping): void
    {
        if (! $session->isOpen()) {
            return;
        }

        SupportTyping::dispatch($session, $senderType, $senderLabel, $isTyping);
    }

    public function findByUuid(string $uuid): ?SupportSession
    {
        return SupportSession::where('uuid', $uuid)->first();
    }

    public function authorizeClient(SupportSession $session, ?string $token): bool
    {
        return $token !== null && $token !== '' && hash_equals($session->client_token, $token);
    }

    /**
     * @return array{session: SupportSession, claimed: bool, error?: string}
     */
    public function claimSession(SupportSession $session, User $staff): array
    {
        if (! $session->isOpen()) {
            return ['session' => $session, 'claimed' => false, 'error' => 'Session is closed.'];
        }

        if ($session->assigned_admin_id && (int) $session->assigned_admin_id !== (int) $staff->id) {
            $name = $session->assignedAdmin?->name ?: $session->assignedAdmin?->username ?: 'Another agent';

            return ['session' => $session->fresh(['assignedAdmin']), 'claimed' => false, 'error' => $name.' is already handling this chat.'];
        }

        if ((int) $session->assigned_admin_id === (int) $staff->id) {
            return ['session' => $session->fresh(['assignedAdmin']), 'claimed' => true];
        }

        $session->update([
            'status' => SupportSession::STATUS_ACTIVE,
            'assigned_admin_id' => $staff->id,
            'claimed_at' => now(),
        ]);

        $name = $staff->name ?: $staff->username;
        $this->addSystemMessage($session, $name.' joined the chat and is helping you now.');

        $fresh = $session->fresh(['assignedAdmin']);
        SupportSessionUpdated::dispatch($fresh);

        return ['session' => $fresh, 'claimed' => true];
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

    public function deleteSession(SupportSession $session): void
    {
        $session->messages()->delete();
        $session->delete();
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
    public function openSessionsForStaff(User $staff)
    {
        $query = SupportSession::query()
            ->with(['assignedAdmin:id,name,username'])
            ->whereIn('status', [SupportSession::STATUS_WAITING, SupportSession::STATUS_ACTIVE]);

        $indices = LiveSupportAccess::scopedStudentIndices($staff);
        if ($indices !== null) {
            if ($indices === []) {
                return collect();
            }
            $query->where(function ($q) use ($indices, $staff) {
                $q->whereIn(DB::raw('UPPER(TRIM(student_index))'), $indices);
                if ($staff->institution_id) {
                    $q->orWhere('institution_id', (int) $staff->institution_id);
                }
            });
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'waiting' THEN 0 ELSE 1 END")
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();
    }

    public function waitingCountForStaff(User $staff): int
    {
        return $this->openSessionsForStaff($staff)
            ->where('status', SupportSession::STATUS_WAITING)
            ->count();
    }
}
