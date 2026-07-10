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
            Broadcast::event($event);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
