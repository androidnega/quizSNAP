<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringLiveAttendanceUpdated implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $payload) {}

    public function broadcastAs(): string
    {
        return 'MonitoringLiveAttendanceUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
