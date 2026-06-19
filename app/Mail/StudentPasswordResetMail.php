<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $resetUrl,
        public int $expiresMinutes = 60,
    ) {}

    public function envelope(): Envelope
    {
        $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));

        return new Envelope(
            subject: 'Reset your ' . $appName . ' password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-password-reset',
            with: [
                'expiresMinutes' => $this->expiresMinutes,
            ],
        );
    }
}
