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

    public static function start(int $quizId, int $target): void
    {
        Cache::put(self::key($quizId), [
            'status' => 'running',
            'generated' => 0,
            'target' => max(1, $target),
            'message' => 'Generating questions…',
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }

    public static function update(int $quizId, int $generated, ?string $message = null): void
    {
        $data = Cache::get(self::key($quizId), []);
        $data['status'] = 'running';
        $data['generated'] = $generated;
        if ($message !== null) {
            $data['message'] = $message;
        }
        Cache::put(self::key($quizId), $data, now()->addHours(24));
    }

    public static function complete(int $quizId, int $generated): void
    {
        $existing = Cache::get(self::key($quizId), []);
        Cache::put(self::key($quizId), [
            'status' => 'completed',
            'generated' => $generated,
            'target' => (int) ($existing['target'] ?? $generated),
            'message' => 'Generation complete.',
            'completed_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }

    public static function fail(int $quizId, string $message): void
    {
        $existing = Cache::get(self::key($quizId), []);
        Cache::put(self::key($quizId), [
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
        $data = Cache::get(self::key($quizId));

        return is_array($data) ? $data : null;
    }

    public static function isRunning(int $quizId): bool
    {
        $data = self::get($quizId);

        return ($data['status'] ?? null) === 'running';
    }

    public static function clear(int $quizId): void
    {
        Cache::forget(self::key($quizId));
    }
}
