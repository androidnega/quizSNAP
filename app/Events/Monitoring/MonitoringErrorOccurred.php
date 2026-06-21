<?php

namespace App\Events\Monitoring;

use App\Events\Monitoring\Concerns\BroadcastsOnPrivateMonitoringChannel;
use App\Models\SystemError;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringErrorOccurred implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateMonitoringChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SystemError $error,
        public string $severity,
    ) {}

    public function broadcastAs(): string
    {
        return 'MonitoringErrorOccurred';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->error->id,
            'severity' => $this->severity,
            'message' => $this->error->message,
            'file' => $this->error->file,
            'line' => $this->error->line,
            'user_name' => null,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
