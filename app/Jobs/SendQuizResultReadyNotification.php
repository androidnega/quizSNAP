<?php

namespace App\Jobs;

use App\Mail\QuizResultReadyNotification;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendQuizResultReadyNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $quizSessionId
    ) {}

    public function handle(): void
    {
        if (Setting::getValue(Setting::KEY_NOTIFY_RESULT_READY, '0') !== '1') {
            return;
        }
        $to = Setting::getValue(Setting::KEY_NOTIFY_RESULT_EMAIL);
        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $session = QuizSession::with(['quiz', 'result'])->find($this->quizSessionId);
        if (! $session || ! $session->result) {
            return;
        }

        MailConfigService::applyFromSettings();

        Mail::to($to)->send(new QuizResultReadyNotification($session));
    }
}
