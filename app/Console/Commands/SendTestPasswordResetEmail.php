<?php

namespace App\Console\Commands;

use App\Mail\PasswordResetMail;
use App\Services\MailConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestPasswordResetEmail extends Command
{
    protected $signature = 'mail:send-test-password-reset {email : Recipient email address}';

    protected $description = 'Send a preview password reset email using current SMTP settings';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');

            return self::FAILURE;
        }

        if (! MailConfigService::isConfigured()) {
            $this->error('Mail is not configured. Set SMTP host and username in Admin Settings first.');

            return self::FAILURE;
        }

        MailConfigService::applyFromSettings();

        try {
            Mail::to($email)->send(new PasswordResetMail(
                recipientName: 'Preview User',
                resetUrl: url('/password/reset/preview-not-valid'),
                audience: 'staff',
                accountLabel: 'preview.user',
                isPreview: true,
            ));
        } catch (\Throwable $e) {
            $this->error('Failed to send: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Password reset preview sent to {$email}.");

        return self::SUCCESS;
    }
}
