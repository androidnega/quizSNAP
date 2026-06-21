<?php

namespace App\Observers;

class CourseAuditObserver extends MonitoringAuditObserver
{
    protected string $subjectLabel = 'Course';
}
