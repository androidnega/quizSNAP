<?php

namespace App\Events\Operations\Concerns;

use Illuminate\Broadcasting\PrivateChannel;

trait BroadcastsOnPrivateOperationsChannel
{
    public function broadcastOn(): array
    {
        return [new PrivateChannel('quizsnap-operations')];
    }
}
