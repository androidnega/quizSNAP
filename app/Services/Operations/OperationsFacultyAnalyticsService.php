<?php

namespace App\Services\Operations;

use App\Models\AttendanceUploadLog;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsFacultyAnalyticsService
{
    public function snapshot(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'exams_created' => $this->examsCreated($since),
            'attendance_sessions' => $this->attendanceSessions($since),
            'student_engagement' => $this->studentEngagement($since),
            'course_activity' => $this->courseActivity($since),
            'usage_statistics' => $this->usageStatistics($since),
            'period_days' => $days,
        ];
    }

    protected function examsCreated($since): array
    {
        if (! Schema::hasTable('quizzes')) {
            return [];
        }

        return Quiz::query()
            ->select('users.name', DB::raw('COUNT(quizzes.id) as total'))
            ->join('users', 'users.id', '=', 'quizzes.examiner_id')
            ->where('quizzes.created_at', '>=', $since)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total])
            ->all();
    }

    protected function attendanceSessions($since): array
    {
        if (! Schema::hasTable('attendance_upload_logs')) {
            return [];
        }

        return AttendanceUploadLog::query()
            ->select('users.name', DB::raw('COUNT(*) as uploads'))
            ->join('users', 'users.id', '=', 'attendance_upload_logs.uploaded_by')
            ->where('attendance_upload_logs.uploaded_at', '>=', $since)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('uploads')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'uploads' => (int) $r->uploads])
            ->all();
    }

    protected function studentEngagement($since): array
    {
        if (! Schema::hasTable('quiz_sessions')) {
            return [];
        }

        return QuizSession::query()
            ->select('users.name', DB::raw('COUNT(quiz_sessions.id) as sessions'))
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('users', 'users.id', '=', 'quizzes.examiner_id')
            ->whereNotNull('quiz_sessions.start_time')
            ->where('quiz_sessions.start_time', '>=', $since)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('sessions')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'sessions' => (int) $r->sessions])
            ->all();
    }

    protected function courseActivity($since): array
    {
        if (! Schema::hasTable('quizzes')) {
            return [];
        }

        return Quiz::query()
            ->select('courses.name', DB::raw('COUNT(quizzes.id) as activity'))
            ->join('courses', 'courses.id', '=', 'quizzes.course_id')
            ->where('quizzes.updated_at', '>=', $since)
            ->groupBy('courses.id', 'courses.name')
            ->orderByDesc('activity')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'activity' => (int) $r->activity])
            ->all();
    }

    protected function usageStatistics($since): array
    {
        $examiners = User::query()->where('role', User::ROLE_EXAMINER)->count();
        $activeExaminers = Schema::hasTable('quizzes')
            ? Quiz::query()->where('created_at', '>=', $since)->distinct('examiner_id')->count('examiner_id')
            : 0;

        return [
            'total_examiners' => $examiners,
            'active_examiners' => $activeExaminers,
            'response_time_estimate_hours' => 24,
        ];
    }
}
