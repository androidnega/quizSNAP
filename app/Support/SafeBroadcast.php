<?php

namespace App\Support;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Cache;

/** Broadcast without failing requests or flooding logs when Reverb/Pusher is offline. */
final class SafeBroadcast
{
    private const SKIP_CACHE_KEY = 'quizsnap:broadcast:offline';

    public static function event(object $event): void
    {
        if (self::shouldSkip()) {
            return;
        }

        if (! $event instanceof ShouldBroadcast) {
            return;
        }

        try {
            $immediate = $event instanceof ShouldBroadcastNow
                || (method_exists($event, 'shouldBroadcastNow') && $event->shouldBroadcastNow());

            if ($immediate) {
                app(Dispatcher::class)->dispatchSync(new BroadcastEvent(clone $event));

                return;
            }

            app(BroadcastManager::class)->queue($event);
        } catch (BroadcastException) {
            self::markOffline();
        } catch (\Throwable $e) {
            if (self::isBroadcastFailure($e)) {
                self::markOffline();

                return;
            }

            try {
                report($e);
            } catch (\Throwable) {
            }
        }
    }

    public static function reportUnlessBroadcastFailure(\Throwable $e): void
    {
        if (self::isBroadcastFailure($e)) {
            self::markOffline();

            return;
        }

        try {
            report($e);
        } catch (\Throwable) {
        }
    }

    private static function shouldSkip(): bool
    {
        if ((string) config('broadcasting.default') === 'null') {
            return true;
        }

        return (bool) Cache::get(self::SKIP_CACHE_KEY, false);
    }

    private static function markOffline(): void
    {
        Cache::put(self::SKIP_CACHE_KEY, true, now()->addMinutes(10));
    }

    private static function isBroadcastFailure(\Throwable $e): bool
    {
        if ($e instanceof BroadcastException) {
            return true;
        }

        $message = $e->getMessage();

        return str_contains($message, 'Pusher error')
            || str_contains($message, 'BroadcastException');
    }
}
