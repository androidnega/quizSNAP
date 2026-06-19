<?php

namespace App\Http\Controllers\Concerns;

use App\Events\DataUpdated;

trait BroadcastsDataUpdatesSafely
{
    protected function broadcastDataUpdatedSafe(string $type): void
    {
        try {
            broadcast(new DataUpdated($type))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
