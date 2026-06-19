<?php

namespace App\Jobs;

use App\Models\ClassGroup;
use App\Models\Otp;
use App\Models\Student;
use App\Models\User;
use App\Services\ArkeselService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendClassGroupStudentLoginOtpsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public int $classGroupId
    ) {}

    public function handle(): void
    {
        if (! ArkeselService::hasApiKey()) {
            return;
        }

        $classGroup = ClassGroup::with('examiner')->find($this->classGroupId);
        if (! $classGroup) {
            return;
        }

        $smsOwner = User::coordinatorWithSmsBalanceForClassGroup($classGroup);
        if (! $smsOwner && $classGroup->examiner && $classGroup->examiner->isExaminer() && $classGroup->examiner->sms_remaining > 0) {
            $smsOwner = $classGroup->examiner;
        }
        if (! $smsOwner) {
            return;
        }

        $smsOwner->refresh();
        $remaining = $smsOwner->sms_remaining;
        if ($remaining <= 0) {
            return;
        }

        foreach ($classGroup->students()->cursor() as $cgStudent) {
            if ($remaining <= 0) {
                break;
            }
            $indexNumber = strtoupper(trim($cgStudent->index_number));
            $indexHash = Student::hashIndexNumber($indexNumber);
            $studentAccount = Student::where('index_number_hash', $indexHash)->first();
            if (! $studentAccount || ! $studentAccount->hasPhone()) {
                continue;
            }
            $code = (string) random_int(100000, 999999);
            Otp::deleteStudentLoginOtpsForIndex($indexHash);
            Otp::create([
                'index_number_hash' => $indexHash,
                'type' => Otp::TYPE_STUDENT_LOGIN,
                'code' => $code,
                'expires_at' => null,
            ]);
            $smsMessage = 'Your QuizSnap login code is: ' . $code . '. Do not share. Stays valid until you get a new code.';
            $result = ArkeselService::sendSms($studentAccount->phone_contact, $smsMessage);
            if ($result['success']) {
                $smsOwner->increment('sms_used');
                $remaining--;
            }
        }
    }
}
