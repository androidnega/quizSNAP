<?php

namespace App\Http\Controllers\Concerns;

use App\Events\DataUpdated;
use App\Support\SafeBroadcast;

trait BroadcastsDataUpdatesSafely
{
    protected function broadcastDataUpdatedSafe(string $type): void
    {
        SafeBroadcast::event(new DataUpdated($type));
    }
}
