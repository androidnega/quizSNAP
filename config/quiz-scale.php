<?php

return [

    /*
    |--------------------------------------------------------------------------
    | High-concurrency quiz settings (VPS / 1000+ simultaneous takers)
    |--------------------------------------------------------------------------
    |
    | Requires CACHE_STORE=redis (or SESSION_DRIVER=redis) for deferred writes.
    |
    */

  /** Defer last_heartbeat_at DB writes from proctor-feed (Redis flush job). */
    'defer_heartbeat_writes' => env('QUIZ_DEFER_HEARTBEAT_WRITES', true),

    /** How often the scheduler flushes Redis heartbeats to MySQL (seconds). */
    'heartbeat_flush_seconds' => (int) env('QUIZ_HEARTBEAT_FLUSH_SECONDS', 45),

    /** Proctor websocket notify throttle (seconds) — matches client frame rate cap. */
    'proctor_broadcast_throttle_seconds' => (int) env('QUIZ_PROCTOR_BROADCAST_THROTTLE', 3),

    /** Cache approved question pool per quiz during exam starts (0 = disabled, local dev default). */
    'question_pool_cache_seconds' => (int) env('QUIZ_QUESTION_POOL_CACHE_SECONDS', 0),

];
