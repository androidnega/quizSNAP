<?php

namespace App\Services\Intelligence;

use App\Models\AttendanceUploadLog;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IntelligenceLecturerService
{
    public function snapshot(int $days = 90): array
    {
        $since = now()->subDays($days);

        if (! Schema::hasTable('users')) {
            return ['lecturers' => [], 'period_days' => $days];
        }

        $lecturers = User::query()
            ->where('role', User::ROLE_EXAMINER)
            ->limit(100)
            ->get()
            ->map(fn (User $user) => $this->profile($user, $since))
            ->sortByDesc('effectiveness_score')
            ->values()
            ->all();

        return [
            'lecturers' => $lecturers,
            'top_performers' => array_slice($lecturers, 0, 10),
            'period_days' => $days,
        ];
    }

    protected function profile(User $user, $since): array
    {
        $examsCreated = Schema::hasTable('quizzes')
            ? Quiz::query()->where('examiner_id', $user->id)->where('created_at', '>=', $since)->count()
            : 0;

        $attendanceUploads = Schema::hasTable('attendance_upload_logs')
            ? AttendanceUploadLog::query()->where('uploaded_by', $user->id)->where('uploaded_at', '>=', $since)->count()
            : 0;

        $studentSessions = Schema::hasTable('quiz_sessions')
            ? QuizSession::query()->whereHas('quiz', fn ($q) => $q->where('examiner_id', $user->id))->whereNotNull('start_time')->where('start_time', '>=', $since)->count()
            : 0;

        $avgPerformance = Schema::hasTable('results')
            ? (float) Result::query()
                ->whereHas('quizSession.quiz', fn ($q) => $q->where('examiner_id', $user->id))
                ->where('submitted_at', '>=', $since)
                ->avg('score')
            : 0;

        $courseActivity = Schema::hasTable('quizzes')
            ? Quiz::query()->where('examiner_id', $user->id)->where('updated_at', '>=', $since)->distinct('course_id')->count('course_id')
            : 0;

        $participationRate = $examsCreated > 0 ? min(100, ($studentSessions / max(1, $examsCreated * 10)) * 100) : 0;
        $assessmentFrequency = round($examsCreated / max(1, now()->diffInWeeks($since)), 1);

        $effectiveness = (int) round(
            min(100, ($avgPerformance * 0.35) + min(30, $examsCreated * 2) + min(20, $attendanceUploads * 3) + min(15, $participationRate * 0.15))
        );

        return [
            'lecturer_id' => $user->id,
            'name' => $user->name,
            'course_activity' => $courseActivity,
            'exams_created' => $examsCreated,
            'attendance_activity' => $attendanceUploads,
            'student_engagement' => $studentSessions,
            'assessment_frequency' => $assessmentFrequency,
            'average_student_performance' => round($avgPerformance, 1),
            'participation_rate' => round($participationRate, 1),
            'effectiveness_score' => $effectiveness,
        ];
    }
}
