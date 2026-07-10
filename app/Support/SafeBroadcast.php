<?php

namespace App\Support;

use Illuminate\Support\Facades\Broadcast;

/** Broadcast without failing HTTP requests when Reverb/Pusher is down. */
final class SafeBroadcast
{
    public static function event(object $event): void
    {
        if ((string) config('broadcasting.default') === 'null') {
            return;
        }

        try {
            // queue() runs ShouldBroadcastNow events synchronously (catchable).
            // event() returns PendingBroadcast and dispatches in __destruct (not catchable).
            Broadcast::queue($event);
        } catch (\Throwable $e) {
            try {
                report($e);
            } catch (\Throwable) {
                // ignore
            }
        }
    }
}
