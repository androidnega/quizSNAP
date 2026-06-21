<?php

namespace App\Observers;

class UserAuditObserver extends MonitoringAuditObserver
{
    protected string $subjectLabel = 'User';
}
