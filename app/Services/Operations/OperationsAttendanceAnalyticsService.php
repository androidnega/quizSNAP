<?php

namespace App\Services\Operations;

use App\Models\AttendanceUploadLog;
use App\Models\AuthAuditLog;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsAttendanceAnalyticsService
{
    public function snapshot(int $days = 30): array
    {
        $since = now()->subDays($days);
        $roster = Schema::hasTable('class_group_students') ? ClassGroupStudent::query()->count() : 0;
        $uploaded = Schema::hasTable('attendance_upload_logs')
            ? (int) AttendanceUploadLog::query()->where('uploaded_at', '>=', $since)->sum('rows_added')
            : 0;

        $rate = $roster > 0 ? round(min(100, ($uploaded / $roster) * 100), 1) : 0;

        return [
            'attendance_rate' => $rate,
            'absenteeism_rate' => round(100 - $rate, 1),
            'late_arrivals' => $this->lateArrivals($since),
            'attendance_trends' => $this->trends($since),
            'department_attendance' => $this->byDepartment($since),
            'course_attendance' => $this->byCourse($since),
            'student_history' => $this->studentHistory($since),
            'period_days' => $days,
        ];
    }

    protected function trends($since): array
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

    protected function lateArrivals($since): int
    {
        if (! Schema::hasTable('auth_audit_logs')) {
            return 0;
        }

        return AuthAuditLog::query()
            ->where('actor_type', 'student')
            ->where('created_at', '>=', $since)
            ->whereRaw('HOUR(created_at) >= 9')
            ->count();
    }

    protected function byDepartment($since): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('departments') || ! Schema::hasTable('attendance_upload_logs')) {
            return [];
        }

        return AttendanceUploadLog::query()
            ->select('departments.name', DB::raw('SUM(attendance_upload_logs.rows_added) as total'))
            ->join('users', 'users.id', '=', 'attendance_upload_logs.uploaded_by')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->where('attendance_upload_logs.uploaded_at', '>=', $since)
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total])
            ->all();
    }

    protected function byCourse($since): array
    {
        if (! Schema::hasTable('attendance_upload_logs')) {
            return [];
        }

        return AttendanceUploadLog::query()
            ->select('courses.name', DB::raw('SUM(attendance_upload_logs.rows_added) as total'))
            ->join('courses', 'courses.id', '=', 'attendance_upload_logs.course_id')
            ->where('attendance_upload_logs.uploaded_at', '>=', $since)
            ->groupBy('courses.id', 'courses.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total])
            ->all();
    }

    protected function studentHistory($since): array
    {
        if (! Schema::hasTable('auth_audit_logs')) {
            return [];
        }

        return AuthAuditLog::query()
            ->select('index_number_hash', DB::raw('COUNT(*) as logins'))
            ->where('actor_type', 'student')
            ->where('created_at', '>=', $since)
            ->groupBy('index_number_hash')
            ->orderByDesc('logins')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['student' => $r->index_number_hash, 'logins' => (int) $r->logins])
            ->all();
    }
}
