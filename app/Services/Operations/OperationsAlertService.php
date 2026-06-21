<?php

namespace App\Services\Operations;

use App\Events\Operations\OperationsAlertCreated;
use App\Models\OperationsAlert;
use App\Services\Monitoring\MonitoringNotificationService;
use Illuminate\Support\Facades\Schema;

class OperationsAlertService
{
    public function raise(string $type, string $severity, string $title, string $message, ?array $meta = null): ?OperationsAlert
    {
        if (! Schema::hasTable('operations_alerts')) {
            return null;
        }

        $alert = OperationsAlert::query()->create([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'meta' => $meta,
        ]);

        $payload = [
            'id' => $alert->id,
            'type' => $alert->type,
            'severity' => $alert->severity,
            'title' => $alert->title,
            'message' => $alert->message,
            'created_at' => $alert->created_at?->toIso8601String(),
        ];

        broadcast(new OperationsAlertCreated($payload))->toOthers();

        if (in_array($severity, ['critical', 'fatal', 'warning'], true)) {
            try {
                app(MonitoringNotificationService::class)->notify(
                    'operations_'.$type,
                    $severity === 'critical' ? 'critical' : $severity,
                    $title,
                    $message,
                    $meta
                );
            } catch (\Throwable) {
                // ignore
            }
        }

        return $alert;
    }

    public function recent(int $limit = 20)
    {
        if (! Schema::hasTable('operations_alerts')) {
            return collect();
        }

        return OperationsAlert::query()->orderByDesc('created_at')->limit($limit)->get();
    }

    public function unreadCount(): int
    {
        if (! Schema::hasTable('operations_alerts')) {
            return 0;
        }

        return OperationsAlert::query()->whereNull('read_at')->count();
    }

    public function markAllRead(): void
    {
        if (! Schema::hasTable('operations_alerts')) {
            return;
        }

        OperationsAlert::query()->whereNull('read_at')->update(['read_at' => now()]);
    }
}
