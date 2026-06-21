<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringLiveQuizUpdated implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $payload) {}

    public function broadcastAs(): string
    {
        return 'MonitoringLiveQuizUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
