<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use App\Models\SecurityEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringSecurityEventOccurred implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SecurityEvent $event) {}

    public function broadcastAs(): string
    {
        return 'MonitoringSecurityEventOccurred';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'severity' => $this->event->severity,
            'risk_score' => $this->event->risk_score ?? null,
            'description' => $this->event->description,
            'occurred_at' => $this->event->occurred_at?->toIso8601String(),
        ];
    }
}
