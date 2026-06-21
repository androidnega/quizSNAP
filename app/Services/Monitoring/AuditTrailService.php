<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringActivityLogged;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AuditTrailService
{
    public function log(
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ?SystemAuditLog {
        if (! Schema::hasTable('system_audit_logs')) {
            return null;
        }

        try {
            $user = auth()->user();
            $entry = SystemAuditLog::query()->create([
                'user_id' => $user instanceof User ? $user->id : null,
                'user_name' => $user instanceof User ? $user->name : 'System',
                'user_role' => $user instanceof User ? $user->role : null,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'occurred_at' => now(),
            ]);

            broadcast(new MonitoringActivityLogged($entry))->toOthers();

            return $entry;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
