<?php

namespace App\Mail;

use App\Mail\Concerns\BuildsQuizSnapEnvelope;
use App\Models\QuizSession;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizResultReadyNotification extends Mailable
{
    use BuildsQuizSnapEnvelope, Queueable, SerializesModels;

    public function __construct(
        public QuizSession $session
    ) {}

    public function envelope(): Envelope
    {
        $quizTitle = $this->session->quiz?->title ?? 'Quiz';
        $appName = (string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name'));

        return $this->quizSnapEnvelope(
            "{$appName} quiz result ready: {$quizTitle}",
            "A student submitted {$quizTitle}. Open your dashboard for full results.",
        );
    }

    public function content(): Content
    {
        return $this->quizSnapContent(
            'emails.quiz-result-ready',
            'emails.text.quiz-result-ready',
            [
                'badge' => 'Quiz notification',
                'heading' => 'Quiz result ready',
                'preheader' => 'A student submitted a quiz. View the summary inside.',
            ],
        );
    }
}
