<?php

namespace App\Events\Intelligence;

use App\Events\Intelligence\Concerns\BroadcastsOnPrivateIntelligenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntelligenceRiskChanged implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateIntelligenceChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $payload) {}

    public function broadcastAs(): string
    {
        return 'IntelligenceRiskChanged';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
