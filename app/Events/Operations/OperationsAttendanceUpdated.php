<?php

namespace App\Events\Operations;

use App\Events\Operations\Concerns\BroadcastsOnPrivateOperationsChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationsAttendanceUpdated implements ShouldBroadcastNow
{
    use BroadcastsOnPrivateOperationsChannel, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $payload) {}

    public function broadcastAs(): string
    {
        return 'OperationsAttendanceUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
