<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use App\Models\SystemAuditLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringActivityLogged implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SystemAuditLog $entry) {}

    public function broadcastAs(): string
    {
        return 'MonitoringActivityLogged';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->entry->id,
            'action' => $this->entry->action,
            'user_name' => $this->entry->user_name,
            'occurred_at' => $this->entry->occurred_at?->toIso8601String(),
        ];
    }
}
