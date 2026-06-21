<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringLiveAttendanceUpdated;
use App\Models\AttendanceUploadLog;
use App\Models\AuthAuditLog;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveAttendanceMonitorService
{
    public function snapshot(): array
    {
        return Cache::remember('monitoring:live-attendance:snapshot', 5, fn () => $this->buildSnapshot());
    }

    public function broadcastUpdate(): void
    {
        broadcast(new MonitoringLiveAttendanceUpdated($this->buildSnapshot()))->toOthers();
    }

    protected function buildSnapshot(): array
    {
        $activeSessions = $this->activeClassGroups();
        $recentUploads = $this->recentUploadActivity();
        $studentLogins = $this->recentStudentLogins();

        $presentEstimate = Schema::hasTable('class_group_students')
            ? ClassGroupStudent::query()->count()
            : 0;

        $checkinsLastMinute = AttendanceUploadLog::query()
            ->where('uploaded_at', '>=', now()->subMinute())
            ->sum('rows_added');

        if (Schema::hasTable('auth_audit_logs')) {
            $checkinsLastMinute += AuthAuditLog::query()
                ->where('event', 'login_password')
                ->where('actor_type', 'student')
                ->where('created_at', '>=', now()->subMinute())
                ->count();
        }

        return [
            'active_sessions' => count($activeSessions),
            'active_class_groups' => $activeSessions,
            'current_checkins' => $recentUploads->sum('rows_added') + $studentLogins->count(),
            'students_present' => $presentEstimate,
            'students_absent' => max(0, $this->estimatedRoster() - $presentEstimate),
            'checkins_per_minute' => (int) $checkinsLastMinute,
            'activity_feed' => $this->buildFeed($recentUploads, $studentLogins),
            'by_course' => $this->groupByCourse($recentUploads),
            'by_department' => $this->groupByDepartment($recentUploads),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function activeClassGroups(): array
    {
        if (! Schema::hasTable('class_groups')) {
            return [];
        }

        return ClassGroup::query()
            ->withCount('students')
            ->where(function ($q) {
                $q->whereHas('quizzes', fn ($q2) => $q2->where('status', 'published'))
                    ->orWhereHas('students');
            })
            ->limit(20)
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->display_name ?? $g->name ?? 'Class Group #'.$g->id,
                'students' => $g->students_count ?? 0,
            ])
            ->all();
    }

    protected function recentUploadActivity()
    {
        if (! Schema::hasTable('attendance_upload_logs')) {
            return collect();
        }

        return AttendanceUploadLog::query()
            ->with(['course:id,name', 'classGroup:id,name'])
            ->where('uploaded_at', '>=', now()->subHours(24))
            ->orderByDesc('uploaded_at')
            ->limit(30)
            ->get();
    }

    protected function recentStudentLogins()
    {
        if (! Schema::hasTable('auth_audit_logs')) {
            return collect();
        }

        return AuthAuditLog::query()
            ->where('actor_type', 'student')
            ->whereIn('event', ['login_password', 'otp_verify_success'])
            ->where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }

    protected function buildFeed($uploads, $logins): array
    {
        $feed = collect();

        foreach ($uploads as $upload) {
            $feed->push([
                'type' => 'upload',
                'message' => sprintf('%d students added to %s', $upload->rows_added, $upload->classGroup?->name ?? 'class group'),
                'time' => $upload->uploaded_at?->toIso8601String(),
            ]);
        }

        foreach ($logins as $login) {
            $feed->push([
                'type' => 'login',
                'message' => 'Student login activity recorded',
                'time' => $login->created_at?->toIso8601String(),
            ]);
        }

        return $feed->sortByDesc('time')->take(25)->values()->all();
    }

    protected function groupByCourse($uploads): array
    {
        return $uploads->groupBy('course_id')->map(function ($group, $courseId) {
            $course = $courseId ? Course::find($courseId) : null;

            return [
                'name' => $course?->name ?? 'Unknown',
                'checkins' => $group->sum('rows_added'),
            ];
        })->values()->all();
    }

    protected function groupByDepartment($uploads): array
    {
        if (! Schema::hasTable('departments')) {
            return [];
        }

        return $uploads->load('course.department')->groupBy(fn ($u) => $u->course?->department_id)->map(function ($group, $deptId) {
            $dept = $deptId ? Department::find($deptId) : null;

            return [
                'name' => $dept?->name ?? 'Unknown',
                'checkins' => $group->sum('rows_added'),
            ];
        })->values()->all();
    }

    protected function estimatedRoster(): int
    {
        if (Schema::hasTable('students')) {
            return (int) DB::table('students')->count();
        }

        return ClassGroupStudent::query()->count();
    }
}
