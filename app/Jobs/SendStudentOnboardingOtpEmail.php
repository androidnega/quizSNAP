<?php

namespace App\Jobs;

use App\Mail\StudentOnboardingOtpMail;
use App\Models\Student;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendStudentOnboardingOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Allow a few retries since transient SMTP failures are common. */
    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $studentId,
        public string $email,
        public string $code,
        public int $expiresMinutes,
    ) {}

    public function handle(): void
    {
        $student = Student::find($this->studentId);
        if (! $student) {
            return;
        }

        MailConfigService::applyFromSettings();

        Mail::to($this->email)->send(
            new StudentOnboardingOtpMail($student, $this->code, $this->expiresMinutes)
        );
    }
}
