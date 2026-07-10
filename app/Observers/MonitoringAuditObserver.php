<?php

namespace App\Observers;

abstract class MonitoringAuditObserver
{
    protected string $subjectLabel;

    public function created($model): void
    {
        try {
            app(\App\Services\Monitoring\AuditTrailService::class)->log(
                "{$this->subjectLabel} Created",
                $model::class,
                $model->getKey(),
                null,
                $this->auditPayload($model)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function updated($model): void
    {
        if (! $model->wasChanged()) {
            return;
        }

        try {
            app(\App\Services\Monitoring\AuditTrailService::class)->log(
                "{$this->subjectLabel} Updated",
                $model::class,
                $model->getKey(),
                array_intersect_key($model->getOriginal(), $model->getChanges()),
                $model->getChanges()
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function deleted($model): void
    {
        try {
            app(\App\Services\Monitoring\AuditTrailService::class)->log(
                "{$this->subjectLabel} Deleted",
                $model::class,
                $model->getKey(),
                $this->auditPayload($model)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function auditPayload($model): array
    {
        return collect($model->getAttributes())
            ->except(['password', 'remember_token'])
            ->take(20)
            ->all();
    }
}
