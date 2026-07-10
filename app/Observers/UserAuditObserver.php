<?php

namespace App\Observers;

class UserAuditObserver extends MonitoringAuditObserver
{
    protected string $subjectLabel = 'User';

    public function created($model): void
    {
        try {
            parent::created($model);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function updated($model): void
    {
        try {
            parent::updated($model);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function deleted($model): void
    {
        try {
            parent::deleted($model);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
