<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ClassGroupStudentUploadProgress
{
    private const TTL_SECONDS = 86400;

    public static function queueConnection(): string
    {
        if (self::redisAvailable()) {
            return 'redis';
        }

        return config('queue.default', 'database');
    }

    public static function redisAvailable(): bool
    {
        try {
            Redis::connection()->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function cacheStore()
    {
        if (self::redisAvailable()) {
            return Cache::store('redis');
        }

        return Cache::store(config('cache.default'));
    }

    private static function key(string $uploadId): string
    {
        return 'student_index_upload:' . $uploadId;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function put(string $uploadId, array $data): void
    {
        self::cacheStore()->put(self::key($uploadId), $data, self::TTL_SECONDS);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $uploadId): ?array
    {
        $data = self::cacheStore()->get(self::key($uploadId));

        return is_array($data) ? $data : null;
    }

    public static function init(string $uploadId, int $totalRows, int $classGroupId, ?int $uploadedBy, string $mode): void
    {
        self::put($uploadId, [
            'upload_id' => $uploadId,
            'class_group_id' => $classGroupId,
            'uploaded_by' => $uploadedBy,
            'mode' => $mode,
            'status' => 'queued',
            'progress' => 0,
            'total' => $totalRows,
            'processed' => 0,
            'rows_added' => 0,
            'rows_updated' => 0,
            'rows_deleted' => 0,
            'rows_skipped_duplicate' => 0,
            'duplicates' => [],
            'message' => 'Queued for processing…',
            'error' => null,
            'finished_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $patch
     */
    public static function merge(string $uploadId, array $patch): void
    {
        $current = self::get($uploadId) ?? [];
        self::put($uploadId, array_merge($current, $patch));
    }

    public static function forget(string $uploadId): void
    {
        self::cacheStore()->forget(self::key($uploadId));
    }
}
