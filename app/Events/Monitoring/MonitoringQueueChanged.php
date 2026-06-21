<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringQueueChanged implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public ?string $uuid = null,
    ) {}

    public function broadcastAs(): string
    {
        return 'MonitoringQueueChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'uuid' => $this->uuid,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
