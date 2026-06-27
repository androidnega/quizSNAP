<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SupportSession;
use App\Models\User;
use App\Support\LiveSupportAccess;
use Illuminate\Support\Facades\Log;

class SupportAgentNotifier
{
    public function notifyNewSession(SupportSession $session): void
    {
        if (! ArkeselService::hasApiKey()) {
            return;
        }

        $recipients = $this->recipientsForSession($session);
        if ($recipients === []) {
            return;
        }

        $appName = trim((string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap')));
        if ($appName === '') {
            $appName = 'QuizSnap';
        }

        $who = $session->student_name ?: ($session->student_index ?: 'a visitor');
        $message = "{$appName}: New live chat from {$who}. Log in to Live Support to respond.";

        foreach ($recipients as $user) {
            $phone = trim((string) ($user->phone ?? ''));
            if ($phone === '') {
                continue;
            }

            try {
                ArkeselService::sendSms($phone, $message);
            } catch (\Throwable $e) {
                Log::warning('Support chat SMS failed', [
                    'user_id' => $user->id,
                    'session_uuid' => $session->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** @return list<User> */
    private function recipientsForSession(SupportSession $session): array
    {
        $dedicated = User::query()
            ->where('role', User::ROLE_SUPPORT_AGENT)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        if ($dedicated->isNotEmpty()) {
            return $dedicated->all();
        }

        return User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get()
            ->filter(fn (User $user) => LiveSupportAccess::canRespond($user))
            ->values()
            ->all();
    }
}
