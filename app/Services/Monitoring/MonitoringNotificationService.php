<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringNotificationCreated;
use App\Models\MonitoringNotification;
use App\Models\SecurityEvent;
use App\Models\ServerHealthSnapshot;
use App\Models\SystemError;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class MonitoringNotificationService
{
    public function notify(string $type, string $severity, string $title, string $message, ?array $meta = null, ?int $userId = null): void
    {
        if (! Schema::hasTable('monitoring_notifications')) {
            return;
        }

        try {
            $notification = MonitoringNotification::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'meta' => $meta,
                'created_at' => now(),
            ]);

            broadcast(new MonitoringNotificationCreated($notification))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function notifyForError(SystemError $error, string $severity): void
    {
        if (! in_array($severity, [SystemError::SEVERITY_CRITICAL, SystemError::SEVERITY_FATAL], true)) {
            return;
        }

        $this->notify(
            'critical_error',
            $severity,
            'Critical application error',
            $error->message,
            ['system_error_id' => $error->id]
        );
    }

    public function notifyForSecurityEvent(SecurityEvent $event): void
    {
        if ($event->severity !== 'critical') {
            return;
        }

        $this->notify(
            'security_alert',
            $event->severity,
            'Security alert: '.$event->event_type,
            $event->description ?? '',
            ['security_event_id' => $event->id]
        );
    }

    public function notifyForHealth(ServerHealthSnapshot $snapshot): void
    {
        $this->notify(
            'server_health',
            $snapshot->status,
            'Server health '.$snapshot->status,
            sprintf('CPU: %s%% RAM: %s%% Disk: %s%%', $snapshot->cpu_usage ?? '—', $snapshot->ram_usage ?? '—', $snapshot->disk_usage ?? '—'),
            ['snapshot_id' => $snapshot->id]
        );
    }

    public function unreadCount(?User $user = null): int
    {
        if (! Schema::hasTable('monitoring_notifications')) {
            return 0;
        }

        $query = MonitoringNotification::query()->whereNull('read_at');
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        }

        return $query->count();
    }

    public function recent(int $limit = 20, ?User $user = null)
    {
        if (! Schema::hasTable('monitoring_notifications')) {
            return collect();
        }

        $query = MonitoringNotification::query()->orderByDesc('created_at');
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        }

        return $query->limit($limit)->get();
    }

    public function markRead(int $id, ?User $user = null): void
    {
        $query = MonitoringNotification::query()->where('id', $id);
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        }
        $query->update(['read_at' => now()]);
    }

    public function markAllRead(?User $user = null): void
    {
        $query = MonitoringNotification::query()->whereNull('read_at');
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        }
        $query->update(['read_at' => now()]);
    }
}
