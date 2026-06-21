<?php

namespace App\Events\Monitoring\Concerns;

use Illuminate\Broadcasting\PrivateChannel;

trait BroadcastsOnPrivateMonitoringChannel
{
    public function broadcastOn(): array
    {
        return [new PrivateChannel('quizsnap-monitoring')];
    }
}
