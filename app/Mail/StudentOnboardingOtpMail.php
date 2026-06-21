<?php

namespace App\Mail;

use App\Mail\Concerns\BuildsQuizSnapEnvelope;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentOnboardingOtpMail extends Mailable
{
    use BuildsQuizSnapEnvelope, Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $code,
        public int $expiresMinutes = 15,
    ) {}

    public function envelope(): Envelope
    {
        $appName = (string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));

        return $this->quizSnapEnvelope(
            "Your {$appName} verification code",
            "Your verification code is {$this->code}. It expires in {$this->expiresMinutes} minutes.",
        );
    }

    public function content(): Content
    {
        return $this->quizSnapContent(
            'emails.student-onboarding-otp',
            'emails.text.student-onboarding-otp',
            [
                'expiresMinutes' => $this->expiresMinutes,
                'badge' => 'Account setup',
                'heading' => 'Your verification code',
                'preheader' => "Your verification code is {$this->code}.",
            ],
        );
    }
}
