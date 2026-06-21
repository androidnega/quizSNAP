<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use App\Models\ServerHealthSnapshot;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringHealthChanged implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServerHealthSnapshot $snapshot) {}

    public function broadcastAs(): string
    {
        return 'MonitoringHealthChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => $this->snapshot->status,
            'cpu_usage' => $this->snapshot->cpu_usage,
            'ram_usage' => $this->snapshot->ram_usage,
            'disk_usage' => $this->snapshot->disk_usage,
            'recorded_at' => $this->snapshot->recorded_at?->toIso8601String(),
        ];
    }
}
