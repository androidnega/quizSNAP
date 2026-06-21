<?php

namespace App\Events\Intelligence\Concerns;

use Illuminate\Broadcasting\PrivateChannel;

trait BroadcastsOnPrivateIntelligenceChannel
{
    public function broadcastOn(): array
    {
        return [new PrivateChannel('quizsnap-intelligence')];
    }
}
