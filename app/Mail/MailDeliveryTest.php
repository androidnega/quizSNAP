<?php

namespace App\Mail;

use App\Mail\Concerns\BuildsQuizSnapEnvelope;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailDeliveryTest extends Mailable
{
    use BuildsQuizSnapEnvelope, Queueable, SerializesModels;

    public function __construct(
        public string $host,
        public string $port,
        public string $encryption,
        public string $fromAddress,
        public string $fromName,
    ) {}

    public function envelope(): Envelope
    {
        $appName = (string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));

        return $this->quizSnapEnvelope(
            "{$appName} mail delivery test",
            'Your QuizSnap SMTP settings are working correctly.',
        );
    }

    public function content(): Content
    {
        return $this->quizSnapContent(
            'emails.mail-delivery-test',
            'emails.text.mail-delivery-test',
            [
                'badge' => 'Mail test',
                'heading' => 'Mail delivery test',
                'preheader' => 'Your SMTP settings are working correctly.',
                'sentAt' => now()->toDayDateTimeString(),
            ],
        );
    }
}
