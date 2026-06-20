<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class SitePresenceService
{
    private const PRESENCE_SECONDS = 90;

    private const REDIS_KEY = 'quizsnap:presence:visitors';

    private const CACHE_BUCKET_KEY = 'site_presence_bucket';

    public function touch(string $visitorId): void
    {
        $visitorId = trim($visitorId);
        if ($visitorId === '') {
            return;
        }

        if ($this->redisAvailable()) {
            $now = time();
            $redis = Redis::connection();
            $redis->zadd(self::REDIS_KEY, $now, $visitorId);
            $redis->zremrangebyscore(self::REDIS_KEY, '-inf', $now - self::PRESENCE_SECONDS);
            $redis->expire(self::REDIS_KEY, 300);

            return;
        }

        $bucket = Cache::get(self::CACHE_BUCKET_KEY, []);
        if (! is_array($bucket)) {
            $bucket = [];
        }
        $cutoff = time() - self::PRESENCE_SECONDS;
        foreach ($bucket as $id => $ts) {
            if ((int) $ts < $cutoff) {
                unset($bucket[$id]);
            }
        }
        $bucket[$visitorId] = time();
        Cache::put(self::CACHE_BUCKET_KEY, $bucket, now()->addMinutes(5));
    }

    public function countActive(): int
    {
        if ($this->redisAvailable()) {
            $now = time();
            $redis = Redis::connection();
            $redis->zremrangebyscore(self::REDIS_KEY, '-inf', $now - self::PRESENCE_SECONDS);

            return (int) $redis->zcard(self::REDIS_KEY);
        }

        $bucket = Cache::get(self::CACHE_BUCKET_KEY, []);
        if (! is_array($bucket)) {
            return 0;
        }
        $cutoff = time() - self::PRESENCE_SECONDS;
        $active = 0;
        foreach ($bucket as $ts) {
            if ((int) $ts >= $cutoff) {
                $active++;
            }
        }

        return $active;
    }

    private function redisAvailable(): bool
    {
        if (! in_array(config('cache.default'), ['redis'], true)
            && config('session.driver') !== 'redis') {
            return false;
        }
        try {
            Redis::connection()->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
