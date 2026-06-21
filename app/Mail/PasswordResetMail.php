<?php

namespace App\Mail;

use App\Mail\Concerns\BuildsQuizSnapEnvelope;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use BuildsQuizSnapEnvelope, Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $resetUrl,
        public string $audience = 'staff',
        public ?string $accountLabel = null,
        public int $expiresMinutes = 60,
        public bool $isPreview = false,
    ) {}

    public function envelope(): Envelope
    {
        $appName = (string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
        $subject = $this->isPreview
            ? "{$appName} password reset preview"
            : "Reset your {$appName} password";

        $preheader = $this->isPreview
            ? 'Preview of the password reset email your users will receive.'
            : "Use this secure link to reset your {$appName} password. Expires in {$this->expiresMinutes} minutes.";

        return $this->quizSnapEnvelope($subject, $preheader);
    }

    public function content(): Content
    {
        return $this->quizSnapContent(
            'emails.password-reset',
            'emails.text.password-reset',
            [
                'recipientName' => $this->recipientName,
                'resetUrl' => $this->resetUrl,
                'audience' => $this->audience,
                'accountLabel' => $this->accountLabel,
                'expiresMinutes' => $this->expiresMinutes,
                'isPreview' => $this->isPreview,
                'badge' => $this->audience === 'student' ? 'Student account' : 'Staff account',
                'heading' => 'Reset your password',
                'preheader' => $this->isPreview
                    ? 'Preview of the password reset email your users will receive.'
                    : "Use this secure link to reset your password. Expires in {$this->expiresMinutes} minutes.",
            ],
        );
    }
}
