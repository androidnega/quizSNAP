<?php

namespace App\Observers;

use App\Models\AttendanceUploadLog;
use App\Services\Monitoring\AuditTrailService;

class AttendanceUploadAuditObserver
{
    public function created(AttendanceUploadLog $log): void
    {
        app(AuditTrailService::class)->log(
            'Attendance Upload',
            AttendanceUploadLog::class,
            $log->getKey(),
            null,
            [
                'rows_added' => $log->rows_added,
                'class_group_id' => $log->class_group_id,
            ]
        );
    }
}
