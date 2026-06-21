<?php

namespace App\Services\Operations;

use App\Events\Operations\OperationsAttendanceUpdated;
use App\Services\Monitoring\LiveAttendanceMonitorService;
use Illuminate\Support\Facades\Cache;

class OperationsAttendanceService
{
    public function snapshot(): array
    {
        return Cache::remember('operations:attendance', 5, fn () => $this->build());
    }

    public function broadcastUpdate(): void
    {
        broadcast(new OperationsAttendanceUpdated($this->build()))->toOthers();
    }

    protected function build(): array
    {
        $base = app(LiveAttendanceMonitorService::class)->snapshot();
        $present = $base['students_present'] ?? 0;
        $absent = $base['students_absent'] ?? 0;
        $total = max(1, $present + $absent);

        return array_merge($base, [
            'attendance_rate' => round(($present / $total) * 100, 1),
            'late_arrivals' => collect($base['activity_feed'] ?? [])
                ->filter(fn ($item) => str_contains(strtolower($item['action'] ?? ''), 'late'))
                ->count(),
            'department_breakdown' => $base['by_department'] ?? [],
            'course_breakdown' => $base['by_course'] ?? [],
        ]);
    }
}
