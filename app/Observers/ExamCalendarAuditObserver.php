<?php

namespace App\Observers;

class ExamCalendarAuditObserver extends MonitoringAuditObserver
{
    protected string $subjectLabel = 'Exam Schedule';
}
