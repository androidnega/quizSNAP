<?php

namespace App\Services\Operations;

use App\Models\AttendanceUploadLog;
use App\Models\AuthAuditLog;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsAcademicIntelligenceService
{
    public function snapshot(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'most_active_courses' => $this->mostActiveCourses($since),
            'most_active_departments' => $this->mostActiveDepartments($since),
            'attendance_trends' => $this->attendanceTrends($since),
            'exam_trends' => $this->examTrends($since),
            'student_engagement' => $this->studentEngagement($since),
            'faculty_engagement' => $this->facultyEngagement($since),
            'course_participation' => $this->courseParticipation($since),
            'period_days' => $days,
        ];
    }

    protected function mostActiveCourses($since): array
    {
        if (! Schema::hasTable('quizzes')) {
            return [];
        }

        return Quiz::query()
            ->select('courses.name', DB::raw('COUNT(quizzes.id) as quiz_count'))
            ->join('courses', 'courses.id', '=', 'quizzes.course_id')
            ->where('quizzes.created_at', '>=', $since)
            ->groupBy('courses.id', 'courses.name')
            ->orderByDesc('quiz_count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->quiz_count])
            ->all();
    }

    protected function mostActiveDepartments($since): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('departments')) {
            return [];
        }

        return User::query()
            ->select('departments.name', DB::raw('COUNT(users.id) as faculty_count'))
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->where('users.updated_at', '>=', $since)
            ->whereIn('users.role', [User::ROLE_EXAMINER, User::ROLE_COORDINATOR])
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('faculty_count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->faculty_count])
            ->all();
    }

    protected function attendanceTrends($since): array
    {
        if (! Schema::hasTable('attendance_upload_logs')) {
            return [];
        }

        return AttendanceUploadLog::query()
            ->selectRaw('DATE(uploaded_at) as day, SUM(rows_added) as total')
            ->where('uploaded_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => $r->day, 'total' => (int) $r->total])
            ->all();
    }

    protected function examTrends($since): array
    {
        if (! Schema::hasTable('quiz_sessions')) {
            return [];
        }

        return QuizSession::query()
            ->selectRaw('DATE(start_time) as day, COUNT(*) as total')
            ->whereNotNull('start_time')
            ->where('start_time', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => $r->day, 'total' => (int) $r->total])
            ->all();
    }

    protected function studentEngagement($since): array
    {
        $sessions = Schema::hasTable('quiz_sessions')
            ? QuizSession::query()->whereNotNull('start_time')->where('start_time', '>=', $since)->count()
            : 0;

        $logins = Schema::hasTable('auth_audit_logs')
            ? AuthAuditLog::query()->where('actor_type', 'student')->where('created_at', '>=', $since)->count()
            : 0;

        return [
            'exam_sessions' => $sessions,
            'student_logins' => $logins,
            'roster_size' => Schema::hasTable('class_group_students') ? ClassGroupStudent::query()->count() : 0,
        ];
    }

    protected function facultyEngagement($since): array
    {
        if (! Schema::hasTable('quizzes')) {
            return [];
        }

        return Quiz::query()
            ->select('users.name', DB::raw('COUNT(quizzes.id) as exams_created'))
            ->join('users', 'users.id', '=', 'quizzes.examiner_id')
            ->where('quizzes.created_at', '>=', $since)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('exams_created')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'exams_created' => (int) $r->exams_created])
            ->all();
    }

    protected function courseParticipation($since): array
    {
        if (! Schema::hasTable('quiz_sessions') || ! Schema::hasTable('quizzes')) {
            return [];
        }

        return QuizSession::query()
            ->select('courses.name', DB::raw('COUNT(quiz_sessions.id) as attempts'))
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('courses', 'courses.id', '=', 'quizzes.course_id')
            ->whereNotNull('quiz_sessions.start_time')
            ->where('quiz_sessions.start_time', '>=', $since)
            ->groupBy('courses.id', 'courses.name')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'attempts' => (int) $r->attempts])
            ->all();
    }
}
