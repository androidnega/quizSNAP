<?php

return [
    /*
    | Secret for public/quizsnap-otp-diagnose.php (GET ?key=...). Empty = script disabled.
    */
    'otp_diagnose_key' => env('QUIZSNAP_OTP_DIAGNOSE_KEY', ''),

    /*
    | Secret for public/quizsnap-run-migrations.php (GET ?key=...). Empty = script disabled.
    | Use a long random value; never commit it. Example: QuizSnapMigrate2026Xp9k3m7
    */
    'migrate_key' => env('QUIZSNAP_MIGRATE_KEY', ''),

    /*
    | Login token (OTP) validity in seconds. Tokens expire after this period (e.g. 14 days).
    */
    'otp_ttl_seconds' => (int) env('QUIZSNAP_OTP_TTL_SECONDS', 14 * 86400),

    /*
    | Comma-separated 6-digit institution codes accepted only after SMS delivery fails.
    | Settings → OTP overrides this when non-empty. Leave empty to disable.
    */
    'universal_student_otp_codes' => env('QUIZSNAP_UNIVERSAL_OTP_CODES', ''),

    /*
    | Staff (admin/examiner) credentials are stored in the `users` table only.
    | To create the first accounts, set ADMIN_USERNAME, ADMIN_PASSWORD (and optionally
    | EXAMINER_*) in .env and run: php artisan db:seed
    | Set ADMIN_* and EXAMINER_* in .env, then run: php artisan db:seed
    */

    'ai' => [
        'max_generation_per_quiz' => (int) env('QUIZSNAP_AI_MAX_PER_QUIZ', 250),
        'request_timeout_seconds' => (int) env('QUIZSNAP_AI_REQUEST_TIMEOUT', 120),
        /** Run one queue worker after dispatching AI jobs (works without a dedicated worker process). */
        'process_queue_after_dispatch' => filter_var(env('QUIZSNAP_AI_PROCESS_QUEUE_AFTER_DISPATCH', true), FILTER_VALIDATE_BOOL),
    ],
];
