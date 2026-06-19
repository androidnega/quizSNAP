<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks background AI question generation for a quiz (cache-backed).
 */
class AiQuizGenerationProgress
{
    private static function key(int $quizId): string
    {
        return 'quiz_ai_generation:' . $quizId;
    }

    private static function safeCachePut(string $key, array $data, \DateTimeInterface|\DateInterval|int|null $ttl): bool
    {
        try {
            Cache::put($key, $data, $ttl);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private static function safeCacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private static function safeCacheForget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function start(int $quizId, int $target): void
    {
        self::safeCachePut(self::key($quizId), [
            'status' => 'running',
            'generated' => 0,
            'target' => max(1, $target),
            'message' => 'Generating questions…',
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }

    public static function update(int $quizId, int $generated, ?string $message = null): void
    {
        $data = self::safeCacheGet(self::key($quizId)) ?? [];
        $data['status'] = 'running';
        $data['generated'] = $generated;
        if ($message !== null) {
            $data['message'] = $message;
        }
        self::safeCachePut(self::key($quizId), $data, now()->addHours(24));
    }

    public static function complete(int $quizId, int $generated): void
    {
        $existing = self::safeCacheGet(self::key($quizId)) ?? [];
        self::safeCachePut(self::key($quizId), [
            'status' => 'completed',
            'generated' => $generated,
            'target' => (int) ($existing['target'] ?? $generated),
            'message' => 'Generation complete.',
            'completed_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }

    public static function fail(int $quizId, string $message): void
    {
        $existing = self::safeCacheGet(self::key($quizId)) ?? [];
        self::safeCachePut(self::key($quizId), [
            'status' => 'failed',
            'generated' => (int) ($existing['generated'] ?? 0),
            'target' => (int) ($existing['target'] ?? 0),
            'message' => $message,
            'failed_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }

    /** @return array<string, mixed>|null */
    public static function get(int $quizId): ?array
    {
        $data = self::safeCacheGet(self::key($quizId));

        return is_array($data) ? $data : null;
    }

    public static function isRunning(int $quizId): bool
    {
        $data = self::get($quizId);

        return ($data['status'] ?? null) === 'running';
    }

    public static function clear(int $quizId): void
    {
        self::safeCacheForget(self::key($quizId));
    }
}
