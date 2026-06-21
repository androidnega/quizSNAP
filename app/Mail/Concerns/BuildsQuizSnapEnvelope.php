<?php

namespace App\Mail\Concerns;

use App\Services\EmailBrandingService;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

trait BuildsQuizSnapEnvelope
{
    protected function quizSnapEnvelope(string $subject, string $preheader = ''): Envelope
    {
        $branding = EmailBrandingService::context();
        $fromAddress = $branding['fromAddress'] !== '' ? $branding['fromAddress'] : 'noreply@example.com';
        $fromName = $branding['fromName'] !== '' ? $branding['fromName'] : $branding['appName'];

        return new Envelope(
            subject: $subject,
            replyTo: [
                new Address($fromAddress, $fromName),
            ],
            using: [
                function (Email $email): void {
                    $email->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
                },
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $with
     */
    protected function quizSnapContent(string $htmlView, string $textView, array $with = []): Content
    {
        return new Content(
            view: $htmlView,
            text: $textView,
            with: array_merge(EmailBrandingService::context(), $with),
        );
    }
}
