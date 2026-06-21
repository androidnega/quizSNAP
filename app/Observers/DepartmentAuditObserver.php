<?php

namespace App\Observers;

class DepartmentAuditObserver extends MonitoringAuditObserver
{
    protected string $subjectLabel = 'Department';
}
