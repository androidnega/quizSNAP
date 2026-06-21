<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use App\Models\DatabaseQueryLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringSlowQueryDetected implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DatabaseQueryLog $queryLog) {}

    public function broadcastAs(): string
    {
        return 'MonitoringSlowQueryDetected';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->queryLog->id,
            'execution_time_ms' => $this->queryLog->execution_time_ms,
            'route' => $this->queryLog->route,
            'occurred_at' => $this->queryLog->occurred_at?->toIso8601String(),
        ];
    }
}
